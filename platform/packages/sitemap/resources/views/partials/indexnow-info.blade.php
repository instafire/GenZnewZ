@php
    $service = app(\Botble\Sitemap\Services\IndexNowService::class);
    $apiKey = $service->getApiKey();
@endphp

<div class="indexnow-info">
    <style>
        .indexnow-info .alert {
            border-radius: 8px;
            border: 1px solid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .indexnow-info .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        .indexnow-info .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        .indexnow-info .alert-light {
            background-color: #fefefe;
            border-color: #e9ecef;
            color: #495057;
        }
        .indexnow-info .input-group-sm .form-control {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            background-color: #f8f9fa;
        }
        .indexnow-info .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #fff;
        }
        .indexnow-info .form-label.small {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #6c757d;
        }
        .indexnow-info ol {
            padding-left: 1.25rem;
            margin-bottom: 0;
        }
        .indexnow-info ol li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        .indexnow-info .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }
        .indexnow-info code {
            background-color: #f8f9fa;
            color: #e83e8c;
            padding: 0.125rem 0.25rem;
            border-radius: 3px;
            font-size: 0.875em;
        }
        .indexnow-info .gap-2 {
            gap: 0.5rem !important;
        }
        .indexnow-info .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .indexnow-info .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .indexnow-info .card {
            transition: all 0.3s ease;
        }
        .indexnow-info .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        .indexnow-info .bg-light {
            transition: all 0.2s ease;
        }
        .indexnow-info .bg-light:hover {
            background-color: #e9ecef !important;
            transform: translateY(-1px);
        }
        .indexnow-info .step-number {
            width: 32px;
            height: 32px;
            min-width: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 0.875rem;
        }
        .indexnow-info .engine-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        .indexnow-info #indexnow-instructions {
            transition: all 0.3s ease;
            transform-origin: top;
        }
        .indexnow-info #indexnow-instructions.d-none {
            opacity: 0;
            transform: scaleY(0);
            max-height: 0;
            overflow: hidden;
        }
        .indexnow-info #indexnow-instructions:not(.d-none) {
            opacity: 1;
            transform: scaleY(1);
            max-height: none;
        }
        @media (max-width: 768px) {
            .indexnow-info .row.g-2 > .col-md-6 {
                margin-bottom: 0.75rem;
            }
            .indexnow-info .d-flex.gap-2 {
                flex-direction: column;
                align-items: stretch;
            }
            .indexnow-info .btn-sm {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>

@if (!$apiKey)
    <div class="alert alert-warning d-flex align-items-start mb-3">
        <x-core::icon name="ti ti-alert-triangle" class="me-2 mt-1 flex-shrink-0" />
        <div class="flex-grow-1">
            <strong>{{ trans('packages/sitemap::sitemap.settings.indexnow_no_key') }}</strong>
            <p class="mb-2 mt-1">{{ trans('packages/sitemap::sitemap.settings.indexnow_no_key_help') }}</p>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-warning" onclick="generateIndexNowKey()">
                    <x-core::icon name="ti ti-key" class="me-1" />
                    {{ trans('packages/sitemap::sitemap.settings.generate_api_key') }}
                </button>
                <small class="text-muted">
                    <x-core::icon name="ti ti-info-circle" class="me-1" />
                    {{ trans('packages/sitemap::sitemap.settings.generate_key_command') }}
                    <code>php artisan sitemap:indexnow --generate-key</code>
                    <x-core::copy copyable-state="php artisan sitemap:indexnow --generate-key" class="ms-1" />
                </small>
            </div>
        </div>
    </div>
@else
    @php
        $keyFileName = $service->getApiKeyFileName();
        $keyFileUrl = url($keyFileName);
        $keyFileExists = $service->keyFileExists();
        $keyFileValid = $keyFileExists ? $service->validateKeyFile() : false;
    @endphp

    @if ($keyFileExists && $keyFileValid)
        <div class="alert alert-success d-flex align-items-start mb-3">
            <x-core::icon name="ti ti-circle-check" class="me-2 mt-1 flex-shrink-0" />
            <div class="flex-grow-1">
                <strong>{{ trans('packages/sitemap::sitemap.settings.indexnow_ready') }}</strong>
                <p class="mb-2 mt-1">{{ trans('packages/sitemap::sitemap.settings.indexnow_ready_help') }}</p>
    @elseif ($keyFileExists && !$keyFileValid)
        <div class="alert alert-warning d-flex align-items-start mb-3">
            <x-core::icon name="ti ti-alert-triangle" class="me-2 mt-1 flex-shrink-0" />
            <div class="flex-grow-1">
                <strong>{{ trans('packages/sitemap::sitemap.settings.key_file_invalid') }}</strong>
                <p class="mb-2 mt-1">{{ trans('packages/sitemap::sitemap.settings.key_file_invalid_help') }}</p>
    @else
        <div class="alert alert-warning d-flex align-items-start mb-3">
            <x-core::icon name="ti ti-alert-triangle" class="me-2 mt-1 flex-shrink-0" />
            <div class="flex-grow-1">
                <strong>{{ trans('packages/sitemap::sitemap.settings.key_file_missing') }}</strong>
                <p class="mb-2 mt-1">{{ trans('packages/sitemap::sitemap.settings.key_file_missing_help') }}</p>
    @endif

            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small text-muted">{{ trans('packages/sitemap::sitemap.settings.key_file_name') }}</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" value="{{ $keyFileName }}" readonly>
                        <x-core::copy :value="$keyFileName" class="btn btn-outline-secondary" />
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">{{ trans('packages/sitemap::sitemap.settings.key_file_content') }}</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" value="{{ $apiKey }}" readonly>
                        <x-core::copy :value="$apiKey" class="btn btn-outline-secondary" />
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <a href="{{ $keyFileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <x-core::icon name="ti ti-external-link" class="me-1" />
                    {{ trans('packages/sitemap::sitemap.settings.test_key_file') }}
                </a>

                @if (!$keyFileExists)
                    <button type="button" class="btn btn-sm btn-warning" onclick="createKeyFile()">
                        <x-core::icon name="ti ti-file-plus" class="me-1" />
                        {{ trans('packages/sitemap::sitemap.settings.create_key_file') }}
                    </button>
                @endif

                <button type="button" class="btn btn-sm btn-outline-warning" onclick="regenerateIndexNowKey()">
                    <x-core::icon name="ti ti-refresh" class="me-1" />
                    {{ trans('packages/sitemap::sitemap.settings.regenerate_api_key') }}
                </button>

                <button type="button" class="btn btn-sm btn-outline-info" onclick="showIndexNowInstructions()">
                    <x-core::icon name="ti ti-help" class="me-1" />
                    {{ trans('packages/sitemap::sitemap.settings.setup_instructions') }}
                </button>
            </div>
        </div>
    </div>

    <div id="indexnow-instructions" class="d-none mt-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 d-flex align-items-center">
                    <x-core::icon name="ti ti-info-circle" class="me-2 text-primary" />
                    {{ trans('packages/sitemap::sitemap.settings.indexnow_info') }}
                </h6>
            </div>
            <div class="card-body">
                <!-- Automatic Setup Section -->
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success rounded-circle p-2 me-3">
                            <x-core::icon name="ti ti-robot" class="text-white" style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <h6 class="mb-1 text-success">{{ trans('packages/sitemap::sitemap.settings.automatic_setup') }}</h6>
                            <small class="text-muted">{{ trans('packages/sitemap::sitemap.settings.automatic_setup_desc') }}</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary step-number text-white me-3">
                                    <span>1</span>
                                </div>
                                <div>
                                    <div class="fw-medium mb-1">{{ trans('packages/sitemap::sitemap.settings.step_generate') }}</div>
                                    <small class="text-muted">{{ trans('packages/sitemap::sitemap.settings.auto_step_1') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary step-number text-white me-3">
                                    <span>2</span>
                                </div>
                                <div>
                                    <div class="fw-medium mb-1">{{ trans('packages/sitemap::sitemap.settings.step_create_file') }}</div>
                                    <small class="text-muted">{{ trans('packages/sitemap::sitemap.settings.auto_step_2', ['filename' => $keyFileName]) }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="bg-success step-number text-white me-3">
                                    <x-core::icon name="ti ti-check" style="width: 16px; height: 16px;" />
                                </div>
                                <div>
                                    <div class="fw-medium mb-1">{{ trans('packages/sitemap::sitemap.settings.step_ready') }}</div>
                                    <small class="text-muted">{{ trans('packages/sitemap::sitemap.settings.auto_step_3', ['url' => $keyFileUrl]) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supported Search Engines Section -->
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info rounded-circle p-2 me-3">
                            <x-core::icon name="ti ti-world" class="text-white" style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <h6 class="mb-1 text-info">{{ trans('packages/sitemap::sitemap.settings.supported_engines') }}</h6>
                            <small class="text-muted">{{ trans('packages/sitemap::sitemap.settings.supported_engines_desc') }}</small>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <div class="bg-primary engine-icon text-white me-3">
                                    <x-core::icon name="ti ti-brand-windows" style="width: 16px; height: 16px;" />
                                </div>
                                <div>
                                    <div class="fw-medium small">Bing</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Microsoft</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <div class="bg-danger engine-icon text-white me-3">
                                    <x-core::icon name="ti ti-letter-y" style="width: 16px; height: 16px;" />
                                </div>
                                <div>
                                    <div class="fw-medium small">Yandex</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Russia</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <div class="bg-warning engine-icon text-white me-3">
                                    <x-core::icon name="ti ti-letter-s" style="width: 16px; height: 16px;" />
                                </div>
                                <div>
                                    <div class="fw-medium small">Seznam</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Czech Republic</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <div class="bg-success engine-icon text-white me-3">
                                    <x-core::icon name="ti ti-letter-n" style="width: 16px; height: 16px;" />
                                </div>
                                <div>
                                    <div class="fw-medium small">Naver</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">South Korea</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Benefits Section -->
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning rounded-circle p-2 me-3">
                            <x-core::icon name="ti ti-sparkles" class="text-white" style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <h6 class="mb-1 text-warning">{{ trans('packages/sitemap::sitemap.settings.indexnow_benefits') }}</h6>
                            <small class="text-muted">{{ trans('packages/sitemap::sitemap.settings.indexnow_benefits_desc') }}</small>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <x-core::icon name="ti ti-bolt" class="text-warning me-2" style="width: 16px; height: 16px;" />
                                <small>{{ trans('packages/sitemap::sitemap.settings.benefit_instant') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <x-core::icon name="ti ti-robot" class="text-warning me-2" style="width: 16px; height: 16px;" />
                                <small>{{ trans('packages/sitemap::sitemap.settings.benefit_automatic') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <x-core::icon name="ti ti-world" class="text-warning me-2" style="width: 16px; height: 16px;" />
                                <small>{{ trans('packages/sitemap::sitemap.settings.benefit_multiple') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <x-core::icon name="ti ti-shield-check" class="text-warning me-2" style="width: 16px; height: 16px;" />
                                <small>{{ trans('packages/sitemap::sitemap.settings.benefit_reliable') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google Note -->
                <div class="alert alert-info border-0 mb-0">
                    <div class="d-flex align-items-start">
                        <x-core::icon name="ti ti-info-circle" class="me-2 mt-1 flex-shrink-0" />
                        <div>
                            <strong>{{ trans('packages/sitemap::sitemap.settings.about_google') }}</strong>
                            <div class="mt-1">{{ trans('packages/sitemap::sitemap.settings.google_note') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</div>

<script>
    function showIndexNowInstructions() {
        const instructions = document.getElementById('indexnow-instructions');
        const button = event.target.closest('button');
        const icon = button.querySelector('x-core\\:icon, i');

        if (instructions.classList.contains('d-none')) {
            // Show instructions
            instructions.classList.remove('d-none');
            if (icon) {
                icon.className = icon.className.replace('ti-help', 'ti-chevron-up');
            }
        } else {
            // Hide instructions
            instructions.classList.add('d-none');
            if (icon) {
                icon.className = icon.className.replace('ti-chevron-up', 'ti-help');
            }
        }
    }

    function generateIndexNowKey() {
        handleKeyGeneration('{{ trans('packages/sitemap::sitemap.settings.generating') }}');
    }

    function regenerateIndexNowKey() {
        if (!confirm('{{ trans('packages/sitemap::sitemap.settings.regenerate_confirm') }}')) {
            return;
        }
        handleKeyGeneration('{{ trans('packages/sitemap::sitemap.settings.regenerating') }}');
    }

    function createKeyFile() {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;

        // Show loading state
        button.disabled = true;
        button.innerHTML = `<x-core::icon name="ti ti-loader" class="me-1 animate-spin" />` + '{{ trans('packages/sitemap::sitemap.settings.creating_key_file') }}';

        // Make AJAX request to create a key file
        fetch('{{ route('sitemap.settings.create-key-file') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show the updated status
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to create key file');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('{{ trans('packages/sitemap::sitemap.settings.key_file_creation_error') }}');

            // Restore button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    function handleKeyGeneration(loadingText) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;

        // Show loading state
        button.disabled = true;
        button.innerHTML = `<x-core::icon name="ti ti-loader" class="me-1 animate-spin" /> ` + loadingText;

        // Make AJAX request to generate key
        fetch('{{ route('sitemap.settings.generate-key') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show the new key
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to generate API key');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('{{ trans('packages/sitemap::sitemap.settings.generate_key_error') }}');

            // Restore button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
</script>
