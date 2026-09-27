<?php

namespace Tests\Feature;

use App\Models\AIReporter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;
use Theme\Newspaper\Http\Controllers\API\AutomationController;

/**
 * Keeps public/openapi.json in step with the real automation API.
 *
 * The spec is a promise made to external agents: if it drifts, agents send
 * payloads the API rejects, or never learn about fields it accepts. These checks
 * compare the spec against the running application rather than against a second
 * hand-maintained list, so drift fails the suite instead of shipping.
 *
 * Covered:
 *  - the spec is valid JSON and every internal $ref resolves
 *  - every registered route under the automation prefix is documented, and nothing
 *    extra is documented that is not a route (routes, paths and HTTP methods)
 *  - every endpoint advertised in GET /status exists and is documented
 *  - `security` matches real behaviour: protected operations reject a tokenless call
 *  - `required` matches the validator: an empty body must fail on exactly those fields
 *  - minLength / maxLength / enum match the validator rules at their boundaries
 *  - every field the controller validates is documented, and every documented field
 *    is either validated or explicitly declared as read-but-unvalidated below
 *  - the documented quality gate matches the controller constants and GET /status
 */
class OpenApiSpecSyncTest extends TestCase
{
    use RefreshDatabaseWithPlugins;

    private const PREFIX = 'api/v1/automation';

    private const CONTROLLER = 'platform/themes/newspaper/src/Http/Controllers/API/AutomationController.php';

    /**
     * Inputs the controller reads without validating them.
     *
     * validateSeo() feeds image_search_query and image_description straight into
     * buildImageGuidance() and never puts them through the validator, so they are
     * legitimately absent from the rule set. Anything added here needs the same
     * justification: it is genuinely read, but genuinely unvalidated.
     */
    private const READ_BUT_UNVALIDATED = [
        'validateSeo' => ['image_search_query', 'image_description'],
    ];

    /** @var array<string, mixed>|null */
    private static ?array $specCache = null;

    private ?string $token = null;

    // -----------------------------------------------------------------
    // Checks
    // -----------------------------------------------------------------

    public function test_spec_is_valid_json_and_every_ref_resolves(): void
    {
        $spec = $this->spec();

        $this->assertArrayHasKey('openapi', $spec);
        $this->assertStringStartsWith('3.', $spec['openapi'], 'The spec must declare an OpenAPI 3.x version.');
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertNotEmpty($spec['paths'], 'The spec documents no operations.');

        $unresolved = [];
        $this->walkRefs($spec, $spec, $unresolved);

        $this->assertSame([], array_values(array_unique($unresolved)), 'The spec contains $ref values that do not resolve.');
    }

    public function test_documented_operations_match_registered_routes_in_both_directions(): void
    {
        $real = array_keys($this->routeOperations());
        $documented = array_keys($this->specOperations());

        sort($real);
        sort($documented);

        $this->assertSame(
            $real,
            $documented,
            "The spec and the registered routes disagree.\n"
            . 'Documented but not routed: ' . $this->describe(array_diff($documented, $real)) . "\n"
            . 'Routed but not documented: ' . $this->describe(array_diff($real, $documented))
        );
    }

    public function test_every_endpoint_advertised_by_status_exists_and_is_documented(): void
    {
        $status = $this->getJson('/' . self::PREFIX . '/status')->assertOk()->json();
        $documented = $this->specOperations();

        $this->assertNotEmpty($status['endpoints'], 'GET /status advertises no endpoints.');

        foreach ($status['endpoints'] as $name => $value) {
            $value = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', trim((string) $value)));
            $this->assertMatchesRegularExpression('#^(GET|POST|PUT|PATCH|DELETE) /#', $value, "GET /status advertises '{$name}' as '{$value}', which is not a METHOD /path pair.");

            [$method, $fullPath] = explode(' ', $value, 2);
            $relative = Str::start(Str::after($fullPath, '/' . self::PREFIX), '/');
            $operation = $method . ' ' . $relative;

            $this->assertArrayHasKey($operation, $documented, "GET /status advertises {$operation} but the spec does not document it.");
        }
    }

    public function test_security_declaration_matches_real_authentication_behaviour(): void
    {
        $operations = $this->specOperations();

        foreach ($this->probes() as $probe) {
            [$method, $path, , $expectsToken] = $probe;
            $operation = $method . ' ' . $path;

            $this->assertArrayHasKey($operation, $operations, "No documented operation for {$operation}.");

            $status = $this->call($method, $this->requestPath($path))->getStatusCode();

            if ($expectsToken) {
                $this->assertSame(
                    401,
                    $status,
                    "{$operation} is declared as token-protected but accepted a tokenless request."
                );

                continue;
            }

            $this->assertNotSame(
                401,
                $status,
                "{$operation} is declared public but rejected a tokenless request."
            );
        }
    }

    public function test_required_fields_match_the_validator(): void
    {
        $operations = $this->specOperations();

        foreach ($this->probes() as $probe) {
            [$method, $path, $payload, $expectsToken] = $probe;

            if ($method === 'GET') {
                continue;
            }

            $operation = $method . ' ' . $path;

            // A fresh reporter per probe: POST /token/refresh rotates the token it is
            // given, so sharing one would invalidate every later probe.
            $headers = $expectsToken ? ['X-API-Token' => $this->newToken()] : [];

            $response = $this->json($method, $this->requestPath($path), $payload, $headers);
            $body = json_decode($response->getContent(), true) ?: [];

            $required = $operations[$operation]['required'] ?? [];
            sort($required);

            if ($required === []) {
                // No required body fields: an empty body must clear validation. A 422
                // with field errors would mean the spec is missing a required field.
                $this->assertNotSame(
                    422,
                    $response->getStatusCode(),
                    "{$operation} documents no required fields but the validator rejected an empty body: " . $response->getContent()
                );

                continue;
            }

            $this->assertSame(
                422,
                $response->getStatusCode(),
                "{$operation} documents required fields but returned {$response->getStatusCode()} for an empty body: " . $response->getContent()
            );

            $failed = array_keys($body['errors'] ?? []);
            sort($failed);

            $this->assertSame(
                $required,
                $failed,
                "{$operation} requires a different field set than the spec documents."
            );
        }
    }

    public function test_string_length_and_enum_constraints_match_the_validator(): void
    {
        $operations = $this->specOperations();
        $properties = $operations['POST /seo/validate']['properties'];

        $valid = [
            'title' => str_repeat('t', 30),
            'description' => str_repeat('d', 120),
            'content' => str_repeat('c', 300),
            'focus_keyword' => 'focus keyword',
        ];

        foreach ([
            ['title', 29, true],
            ['title', 30, false],
            ['title', 70, false],
            ['title', 71, true],
            ['description', 119, true],
            ['description', 120, false],
            ['description', 165, false],
            ['description', 166, true],
            ['content', 299, true],
            ['content', 300, false],
            ['focus_keyword', 99, false],
            ['focus_keyword', 100, false],
            ['focus_keyword', 101, true],
        ] as [$field, $length, $shouldFail]) {
            $payload = $valid;
            $payload[$field] = str_repeat('x', $length);

            $failed = $this->validationErrors('POST', '/seo/validate', $payload);

            $this->assertSame(
                $shouldFail,
                in_array($field, $failed, true),
                sprintf(
                    '%s at %d characters %s be rejected by the validator, but the spec says minLength=%s maxLength=%s.',
                    $field,
                    $length,
                    $shouldFail ? 'should' : 'should not',
                    $properties[$field]['minLength'] ?? 'none',
                    $properties[$field]['maxLength'] ?? 'none'
                )
            );
        }

        // Enum: the validator must reject a value the spec does not list, and the
        // documented list must match the rule exactly.
        $this->assertContains(
            'format_type',
            $this->validationErrors('POST', '/posts/create', ['format_type' => 'not-a-format']),
            'format_type accepted a value outside its enum.'
        );

        // Every enum documented in the spec must match its validator rule.
        foreach ($operations as $operation => $definition) {
            foreach ($definition['properties'] as $field => $schema) {
                if (! isset($schema['enum'])) {
                    continue;
                }

                $method = $this->controllerMethodFor($operation);
                $fromRule = $this->controllerEnum($method, $field);

                $this->assertEqualsCanonicalizing(
                    $fromRule,
                    $schema['enum'],
                    "{$operation}: the documented enum for {$field} does not match the validator rule."
                );
            }
        }
    }

    public function test_every_validated_controller_field_is_documented(): void
    {
        foreach ($this->specOperations() as $operation => $definition) {
            $method = $this->controllerMethodFor($operation);
            $rules = $this->controllerRules($method);

            $validated = array_values(array_filter(
                array_keys($rules),
                fn (string $field): bool => ! str_ends_with($field, '.*')
            ));

            $documented = array_keys($definition['properties']);

            $undocumented = array_values(array_diff($validated, $documented));
            $this->assertSame(
                [],
                $undocumented,
                "{$operation} validates " . $this->describe($undocumented) . ' but the spec does not document them.'
            );

            $allowed = array_merge($validated, self::READ_BUT_UNVALIDATED[$method] ?? []);
            $unexplained = array_values(array_diff($documented, $allowed));
            $this->assertSame(
                [],
                $unexplained,
                "{$operation} documents " . $this->describe($unexplained) . ' that the controller neither validates nor reads.'
            );

            // Every field the spec marks required must actually be required.
            foreach ($definition['required'] as $field) {
                $rule = $rules[$field] ?? '';
                $this->assertMatchesRegularExpression(
                    '/(^|\|)(required|accepted)(\||$)/',
                    $rule,
                    "{$operation} documents {$field} as required but its rule is '{$rule}'."
                );
            }

            // minLength / maxLength must match the rule.
            foreach ($definition['properties'] as $field => $schema) {
                $rule = $rules[$field] ?? '';
                if ($rule === '') {
                    continue;
                }

                if (preg_match('/max:(\d+)/', $rule, $m)) {
                    $this->assertSame(
                        (int) $m[1],
                        $schema['maxLength'] ?? null,
                        "{$operation}.{$field}: the spec maxLength does not match the 'max:{$m[1]}' rule."
                    );
                }

                if (preg_match('/min:(\d+)/', $rule, $m)) {
                    $this->assertSame(
                        (int) $m[1],
                        $schema['minLength'] ?? null,
                        "{$operation}.{$field}: the spec minLength does not match the 'min:{$m[1]}' rule."
                    );
                }
            }
        }
    }

    public function test_documented_quality_gate_matches_the_constants_and_status_endpoint(): void
    {
        $spec = $this->spec();
        $status = $this->getJson('/' . self::PREFIX . '/status')->assertOk()->json();
        $gate = $spec['components']['schemas']['QualityGate']['properties'];

        $reflection = new \ReflectionClass(AutomationController::class);
        $minimumScore = $reflection->getConstant('MIN_SEO_SCORE');
        $minimumWords = $reflection->getConstant('MIN_AUTOMATION_WORDS');

        foreach (['seo_minimum_score', 'minimum_score'] as $key) {
            $this->assertSame(
                $minimumScore,
                $gate[$key]['const'] ?? null,
                "The spec documents QualityGate.{$key} differently from AutomationController::MIN_SEO_SCORE."
            );
        }

        $this->assertSame(
            $minimumWords,
            $gate['minimum_words']['const'] ?? null,
            'The spec documents QualityGate.minimum_words differently from AutomationController::MIN_AUTOMATION_WORDS.'
        );

        $this->assertSame($minimumScore, $status['quality_gate']['seo_minimum_score']);
        $this->assertSame($minimumWords, $status['quality_gate']['minimum_words']);

        // Any threshold the spec pins with const must match what /status reports.
        foreach ($gate as $key => $schema) {
            if (! array_key_exists('const', $schema) || ! array_key_exists($key, $status['quality_gate'])) {
                continue;
            }

            $this->assertSame(
                $status['quality_gate'][$key],
                $schema['const'],
                "The spec pins QualityGate.{$key} to a value GET /status does not report."
            );
        }
    }

    public function test_documented_version_matches_the_running_api(): void
    {
        $spec = $this->spec();
        $status = $this->getJson('/' . self::PREFIX . '/status')->assertOk()->json();

        $this->assertSame(
            $status['version'],
            $spec['info']['version'],
            'The spec info.version does not match the version GET /status reports.'
        );
    }

    public function test_spec_is_referenced_by_the_discovery_files(): void
    {
        foreach (['llms.txt', 'AI_INSTRUCTIONS.md'] as $file) {
            $path = public_path($file);
            $this->assertFileExists($path);
            $this->assertStringContainsString(
                'openapi.json',
                (string) file_get_contents($path),
                "{$file} does not point agents at the OpenAPI spec."
            );
        }

        $this->assertStringContainsString(
            'openapi.json',
            (string) file_get_contents(base_path('platform/themes/newspaper/partials/ai-agent-hub.blade.php')),
            'The site-wide AI agent hub does not link to the OpenAPI spec.'
        );
    }

    // -----------------------------------------------------------------
    // Probes
    // -----------------------------------------------------------------

    /**
     * One entry per registered operation: [method, relative path, payload, expects token].
     *
     * @return array<int, array{0: string, 1: string, 2: array<string, mixed>, 3: bool}>
     */
    private function probes(): array
    {
        return [
            ['GET', '/status', [], false],
            ['GET', '/instructions', [], false],
            ['POST', '/register', [], false],
            ['POST', '/register/batch', [], false],
            ['POST', '/login', [], false],
            ['GET', '/me', [], true],
            ['POST', '/token/refresh', [], true],
            ['GET', '/categories', [], true],
            ['GET', '/authors', [], true],
            ['GET', '/opportunities', [], true],
            ['POST', '/seo/validate', [], true],
            ['POST', '/posts/create', [], true],
            ['GET', '/posts/mine', [], true],
            ['GET', '/posts/{postId}', [], true],
            ['PATCH', '/posts/{postId}/update', [], true],
            ['PUT', '/posts/{postId}/update', [], true],
            ['POST', '/posts/{postId}/update', [], true],
        ];
    }

    /**
     * Turn a documented path template into a concrete request URI.
     */
    private function requestPath(string $path): string
    {
        return '/' . self::PREFIX . str_replace('{postId}', '999999', $path);
    }

    private function authHeaders(?string $token = null): array
    {
        return ['X-API-Token' => $token ?? $this->token()];
    }

    /**
     * A token that is created once per test and is never rotated.
     */
    private function token(): string
    {
        return $this->token ??= $this->newToken();
    }

    /**
     * Register an active reporter and return a token that is valid right now.
     */
    private function newToken(): string
    {
        $suffix = Str::lower(Str::random(10));
        $token = 'ai_' . Str::random(56);

        AIReporter::create([
            'name' => 'Spec Sync Probe',
            'email' => "spec-sync-{$suffix}@example.test",
            'username' => "spec-sync-{$suffix}",
            'password' => bcrypt('secret'),
            'api_token' => $token,
            'status' => 'active',
            'is_verified' => true,
        ]);

        return $token;
    }

    /**
     * Field names the validator rejected for the given payload.
     *
     * @return array<int, string>
     */
    private function validationErrors(string $method, string $path, array $payload): array
    {
        $response = $this->json($method, $this->requestPath($path), $payload, $this->authHeaders());

        $body = json_decode($response->getContent(), true) ?: [];

        return array_keys($body['errors'] ?? []);
    }

    // -----------------------------------------------------------------
    // Spec access
    // -----------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        if (self::$specCache !== null) {
            return self::$specCache;
        }

        $path = public_path('openapi.json');
        $this->assertFileExists($path, 'The OpenAPI spec must ship at public/openapi.json.');

        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded, 'public/openapi.json is not valid JSON.');

        return self::$specCache = $decoded;
    }

    /**
     * Documented operations keyed by "METHOD /path".
     *
     * @return array<string, array{required: array<int, string>, properties: array<string, array<string, mixed>>, security: array<int, mixed>|null, controllerMethod: string|null}>
     */
    private function specOperations(): array
    {
        $operations = [];
        $routes = $this->routeOperations();
        $spec = $this->spec();

        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                // A path item may also carry `parameters`, `summary` and `x-` extensions.
                if (! in_array(strtolower((string) $method), ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'], true)) {
                    continue;
                }

                $key = strtoupper($method) . ' ' . $path;
                $schema = $operation['requestBody']['content']['application/json']['schema'] ?? [];

                if (isset($schema['$ref'])) {
                    $schema = $this->resolvePointer($spec, $schema['$ref']) ?? [];
                }

                $operations[$key] = [
                    'required' => $schema['required'] ?? [],
                    'properties' => $schema['properties'] ?? [],
                    'security' => array_key_exists('security', $operation) ? $operation['security'] : null,
                    'controllerMethod' => $routes[$key]['controllerMethod'] ?? null,
                ];
            }
        }

        return $operations;
    }

    /**
     * Registered automation routes keyed by "METHOD /path".
     *
     * @return array<string, array{controllerMethod: string}>
     */
    private function routeOperations(): array
    {
        $operations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, self::PREFIX . '/')) {
                continue;
            }

            $relative = '/' . Str::after($uri, self::PREFIX . '/');
            $action = $route->getActionName();
            $controllerMethod = str_contains($action, '@') ? Str::after($action, '@') : $action;

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $operations[strtoupper($method) . ' ' . $relative] = [
                    'controllerMethod' => $controllerMethod,
                ];
            }
        }

        return $operations;
    }

    private function controllerMethodFor(string $operation): string
    {
        $routes = $this->routeOperations();

        $this->assertArrayHasKey($operation, $routes, "No registered route for {$operation}.");

        return $routes[$operation]['controllerMethod'];
    }

    // -----------------------------------------------------------------
    // Controller source
    // -----------------------------------------------------------------

    private function controllerSource(): string
    {
        $path = base_path(self::CONTROLLER);

        $this->assertFileExists($path, 'The automation controller moved; update OpenApiSpecSyncTest.');

        return (string) file_get_contents($path);
    }

    /**
     * The validator rules declared in a controller method, as "field" => "rule string".
     *
     * @return array<string, string>
     */
    private function controllerRules(string $method): array
    {
        if ($method === '' || str_contains($method, 'Closure')) {
            return [];
        }

        $source = $this->controllerSource();

        $this->assertMatchesRegularExpression(
            '/^    public function ' . preg_quote($method, '/') . '\s*\(/m',
            $source,
            "Controller method {$method} not found; update OpenApiSpecSyncTest."
        );

        preg_match('/^    public function ' . preg_quote($method, '/') . '\s*\(/m', $source, $match, PREG_OFFSET_CAPTURE);
        $rest = substr($source, $match[0][1]);

        // Trim the body at the next method so we never pick up a neighbour's rules.
        if (preg_match('/^    (?:public|protected|private) function /m', $rest, $next, PREG_OFFSET_CAPTURE, 1)) {
            $rest = substr($rest, 0, $next[0][1]);
        }

        if (! preg_match('/Validator::make\(\s*\$request->all\(\),\s*\[/', $rest, $validator, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $block = substr($rest, $validator[0][1] + strlen($validator[0][0]));
        $rulesBlock = $this->readArrayLiteral($block, $method);

        preg_match_all("/'([a-zA-Z0-9_.]+)'\s*=>\s*'([^']*)'/", $rulesBlock, $pairs, PREG_SET_ORDER);

        $rules = [];
        foreach ($pairs as $pair) {
            $rules[$pair[1]] = $pair[2];
        }

        $this->assertNotEmpty($rules, "No validation rules could be read from {$method}().");

        return $rules;
    }

    /**
     * Read an array literal from the point just after its opening '['.
     *
     * Stops at the bracket that closes it, so a trailing error-message array
     * (which uses dotted keys like 'username.regex') is not mistaken for rules.
     */
    private function readArrayLiteral(string $source, string $method): string
    {
        $depth = 1;
        $quote = '';
        $length = strlen($source);

        for ($i = 0; $i < $length; $i++) {
            $char = $source[$i];

            if ($quote !== '') {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    $quote = '';
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
            } elseif ($char === '[') {
                $depth++;
            } elseif ($char === ']' && --$depth === 0) {
                return substr($source, 0, $i);
            }
        }

        $this->fail("Could not find the end of the validation rules in {$method}().");

        return '';
    }

    /**
     * Enum values parsed from an `in:` rule.
     *
     * @return array<int, string>
     */
    private function controllerEnum(string $method, string $field): array
    {
        $rule = $this->controllerRules($method)[$field] ?? '';

        if (! preg_match('/(?:^|\|)in:([^|]+)/', $rule, $match)) {
            $this->fail("No 'in:' rule found for {$method}().{$field}.");
        }

        return explode(',', $match[1]);
    }

    // -----------------------------------------------------------------
    // Misc
    // -----------------------------------------------------------------

    /**
     * Collect every $ref that does not resolve inside the document.
     *
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $root
     * @param  array<int, string>  $unresolved
     */
    private function walkRefs(array $node, array $root, array &$unresolved): void
    {
        foreach ($node as $key => $value) {
            if ($key === '$ref' && is_string($value)) {
                if ($this->resolvePointer($root, $value) === null) {
                    $unresolved[] = $value;
                }

                continue;
            }

            if (is_array($value)) {
                $this->walkRefs($value, $root, $unresolved);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $root
     * @return mixed|null
     */
    private function resolvePointer(array $root, string $pointer): mixed
    {
        if (! str_starts_with($pointer, '#/')) {
            return null;
        }

        $current = $root;

        foreach (explode('/', substr($pointer, 2)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);

            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param  array<int, string>  $items
     */
    private function describe(array $items): string
    {
        return $items === [] ? 'none' : implode(', ', $items);
    }
}
