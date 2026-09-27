<x-core::layouts.base :body-attributes="['data-bs-theme' => 'dark']">
    <div class="row g-0 flex-fill vh-100">
        <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
            <div class="container container-tight my-5 px-lg-5">
                <div class="text-center mb-4">
                    @include('core/base::partials.logo', ['defaultLogoHeight' => 50])
                </div>

                @yield('content')
            </div>
        </div>
        <div class="position-relative col-12 col-lg-6 col-xl-8 d-none d-lg-block">
            <div
                class="bg-cover bg-white h-100 min-vh-100"
                style="background-image: url({{ $backgroundUrl }})"
            ></div>
            <div class="end-0 bottom-0 position-absolute">
                <div class="text-white me-5 mb-4">
                    <h1 class="mb-1">{{ setting('admin_title', config('core.base.general.base_name')) }}</h1>
                    <p>@include('core/base::partials.copyright')</p>
                </div>
            </div>
        </div>
    </div>
</x-core::layouts.base>
