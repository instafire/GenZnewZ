@php
    $sitemapUrl = url('sitemap.xml');
@endphp

<div class="card border-info mb-3">
    <div class="card-body">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <div class="avatar avatar-lg bg-info-lt">
                    <x-core::icon name="ti ti-sitemap" class="icon-lg" />
                </div>
            </div>
            <div class="flex-fill">
                <h4 class="card-title mb-2">{{ trans('packages/sitemap::sitemap.settings.sitemap_info_title') }}</h4>
                <p class="text-muted mb-3">{{ trans('packages/sitemap::sitemap.settings.sitemap_info_description') }}</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-2 bg-light rounded">
                            <div class="me-3">
                                <x-core::icon name="ti ti-link" class="text-primary" />
                            </div>
                            <div class="flex-fill">
                                <div class="fw-medium small">{{ trans('packages/sitemap::sitemap.settings.sitemap_url') }}</div>
                                <div class="mt-1">
                                    <a href="{{ $sitemapUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <x-core::icon name="ti ti-external-link" class="me-1" />
                                        {{ trans('packages/sitemap::sitemap.settings.view_sitemap') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-2 bg-light rounded">
                            <div class="me-3">
                                <x-core::icon name="ti ti-refresh" class="text-success" />
                            </div>
                            <div class="flex-fill">
                                <div class="fw-medium small">{{ trans('packages/sitemap::sitemap.settings.automatic_generation') }}</div>
                                <small class="text-muted">{{ trans('packages/sitemap::sitemap.settings.automatic_generation_desc') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-2 bg-success-lt rounded">
                    <div class="d-flex align-items-center">
                        <x-core::icon name="ti ti-info-circle" class="text-success me-2" />
                        <small class="text-success fw-medium">
                            {{ trans('packages/sitemap::sitemap.settings.automatic_update_note') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
