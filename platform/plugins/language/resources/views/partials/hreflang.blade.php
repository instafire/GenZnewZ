@php
    $normalizeHreflang = static function (?string $code): string {
        $code = trim((string) $code);

        if ($code === '') {
            return 'en';
        }

        $code = str_replace('_', '-', $code);
        $parts = explode('-', $code);
        $lang = strtolower($parts[0] ?? 'en');

        if (! empty($parts[1])) {
            return $lang . '-' . strtoupper($parts[1]);
        }

        return $lang;
    };
@endphp

@if (!empty($urls))
    @foreach ($urls as $item)
        <link
            href="{{ $item['url'] }}"
            hreflang="{{ $normalizeHreflang($item['lang_code'] ?? '') }}"
            rel="alternate"
        />
    @endforeach
@else
    @foreach (Language::getSupportedLocales() as $localeCode => $properties)
        <link
            href="{{ Language::getLocalizedURL($localeCode, url()->current(), [], false) }}"
            hreflang="{{ $normalizeHreflang($localeCode) }}"
            rel="alternate"
        />
    @endforeach
@endif
