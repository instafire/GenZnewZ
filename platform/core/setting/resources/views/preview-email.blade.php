<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    >
    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >
    <title>{{ trans('core/setting::setting.preview') }}</title>

    @php
        $faviconUrl = AdminHelper::getAdminFaviconUrl();
        $faviconType = rescue(fn () => RvMedia::getMimeType(AdminHelper::getAdminFavicon()), 'image/x-icon', false);
    @endphp
    <link
        href="{{ $faviconUrl }}"
        rel="icon shortcut"
        type="{{ $faviconType }}"
    >
    <meta
        property="og:image"
        content="{{ $faviconUrl }}"
    >

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
            color: #374151;
            line-height: 1.6;
        }

        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .preview-section {
            flex: 1;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

        .preview-wrapper {
            width: 100%;
            max-width: 800px;
            height: 100%;
            max-height: 900px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            position: relative;
        }

        .device-header {
            height: 40px;
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            padding: 0 1rem;
            gap: 0.5rem;
        }

        .device-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #d1d5db;
        }

        .device-dot:first-child {
            background-color: #ef4444;
        }

        .device-dot:nth-child(2) {
            background-color: #f59e0b;
        }

        .device-dot:nth-child(3) {
            background-color: #10b981;
        }

        .iframe-container {
            height: calc(100% - 40px);
            width: 100%;
            overflow: hidden;
        }

        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        .controls-section {
            width: 420px;
            background-color: #ffffff;
            padding: 2rem;
            overflow-y: auto;
            border-left: 1px solid #e5e7eb;
        }

        .controls-header {
            margin-bottom: 2rem;
        }

        .controls-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .controls-header p {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #374151;
            background-color: #ffffff;
            background-clip: padding-box;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 0.875rem;
            line-height: 1.25rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
            cursor: pointer;
            border: none;
            outline: none;
            white-space: nowrap;
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background-color: #3b82f6;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .btn-primary:active {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #6b7280;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
        }

        .btn-secondary:active {
            background-color: #374151;
        }

        @media (max-width: 1024px) {
            .controls-section {
                width: 360px;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .preview-section {
                height: 60vh;
                padding: 1rem;
            }

            .controls-section {
                width: 100%;
                height: 40vh;
                border-left: none;
                border-top: 1px solid #e5e7eb;
            }

            .device-header {
                display: none;
            }

            .iframe-container {
                height: 100%;
            }

            .preview-wrapper {
                border-radius: 8px;
            }
        }

        @media (max-width: 480px) {
            .controls-section {
                padding: 1.5rem;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="preview-section">
            <div class="preview-wrapper">
                <div class="device-header">
                    <div class="device-dot"></div>
                    <div class="device-dot"></div>
                    <div class="device-dot"></div>
                </div>
                <div class="iframe-container">
                    <iframe
                        src="{{ $iframeUrl . ($inputData ? '?' . http_build_query($inputData) : '') }}"
                        width="100%"
                        height="100%"
                    ></iframe>
                </div>
            </div>
        </div>
        <div class="controls-section">
            <div class="controls-header">
                <h2>{{ trans('core/setting::setting.preview') }}</h2>
                <p>{{ trans('core/setting::setting.enter_sample_value') }}</p>
            </div>
            <form method="POST">
                @csrf
                @foreach ($variables as $key => $variable)
                    <div class="form-group">
                        <label
                            class="form-label"
                            for="txt-{{ $key }}"
                        >{{ trans($variable) }}</label>
                        <input
                            class="form-control"
                            id="txt-{{ $key }}"
                            name="{{ $key }}"
                            type="text"
                            value="{{ Arr::get($inputData, $key) }}"
                        >
                    </div>
                @endforeach
                <div class="btn-group">
                    <button
                        class="btn btn-primary"
                        type="submit"
                    >{{ trans('core/setting::setting.submit') }}</button>
                    <a
                        class="btn btn-secondary"
                        href="{{ $backUrl }}"
                    >{{ trans('core/setting::setting.back') }}</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
