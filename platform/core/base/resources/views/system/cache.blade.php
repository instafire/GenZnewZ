@extends(BaseHelper::getAdminMasterLayoutTemplate())
@section('content')
    <div class="row">
        <div class="col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        <x-core::icon name="ti ti-refresh" />
                        {{ trans('core/base::cache.cache_management') }}
                    </x-core::card.title>
                </x-core::card.header>

                <div class="card-body">
                    <p class="text-secondary mb-3">
                        {{ trans('core/base::cache.cache_management_description') }}
                    </p>

                    @if ($cacheSize > 50 * 1024 * 1024)
                        <x-core::alert type="warning" class="mb-3">
                            <x-core::icon name="ti ti-alert-triangle" class="me-1" />
                            {{ trans('core/base::cache.cache_size_warning') }}
                        </x-core::alert>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th width="50">Type</th>
                                    <th>Description</th>
                                    <th width="200" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="avatar bg-primary-lt">
                                            <x-core::icon name="ti ti-database" />
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate">
                                            <strong>{{ trans('core/base::cache.commands.clear_cms_cache.title') }}</strong>
                                            <div class="text-secondary text-truncate mt-n1">
                                                {{ trans('core/base::cache.commands.clear_cms_cache.description') }}
                                            </div>
                                            <div class="mt-2">
                                                <span class="status status-primary">
                                                    <span class="status-dot status-dot-animated"></span>
                                                    <strong>{{ trans('core/base::cache.current_size') }}:</strong> {{ $formattedCacheSize }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-core::button
                                            type="button"
                                            color="primary"
                                            class="btn-clear-cache"
                                            data-type="clear_cms_cache"
                                            data-url="{{ route('system.cache.clear') }}"
                                        >
                                            <x-core::icon name="ti ti-trash" class="me-1" />
                                            {{ trans('core/base::cache.clear_button') }}
                                        </x-core::button>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="avatar bg-warning-lt">
                                            <x-core::icon name="ti ti-file-code" />
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate">
                                            <strong>{{ trans('core/base::cache.commands.refresh_compiled_views.title') }}</strong>
                                            <div class="text-secondary text-truncate mt-n1">
                                                {{ trans('core/base::cache.commands.refresh_compiled_views.description') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-core::button
                                            type="button"
                                            color="warning"
                                            class="btn-clear-cache"
                                            data-type="refresh_compiled_views"
                                            data-url="{{ route('system.cache.clear') }}"
                                        >
                                            <x-core::icon name="ti ti-refresh" class="me-1" />
                                            {{ trans('core/base::cache.refresh_button') }}
                                        </x-core::button>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="avatar bg-info-lt">
                                            <x-core::icon name="ti ti-settings" />
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate">
                                            <strong>{{ trans('core/base::cache.commands.clear_config_cache.title') }}</strong>
                                            <div class="text-secondary text-truncate mt-n1">
                                                {{ trans('core/base::cache.commands.clear_config_cache.description') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-core::button
                                            type="button"
                                            color="info"
                                            class="btn-clear-cache"
                                            data-type="clear_config_cache"
                                            data-url="{{ route('system.cache.clear') }}"
                                        >
                                            <x-core::icon name="ti ti-refresh" class="me-1" />
                                            {{ trans('core/base::cache.clear_button') }}
                                        </x-core::button>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="avatar bg-success-lt">
                                            <x-core::icon name="ti ti-route" />
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate">
                                            <strong>{{ trans('core/base::cache.commands.clear_route_cache.title') }}</strong>
                                            <div class="text-secondary text-truncate mt-n1">
                                                {{ trans('core/base::cache.commands.clear_route_cache.description') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-core::button
                                            type="button"
                                            color="success"
                                            class="btn-clear-cache"
                                            data-type="clear_route_cache"
                                            data-url="{{ route('system.cache.clear') }}"
                                        >
                                            <x-core::icon name="ti ti-refresh" class="me-1" />
                                            {{ trans('core/base::cache.clear_button') }}
                                        </x-core::button>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="avatar bg-danger-lt">
                                            <x-core::icon name="ti ti-file-text" />
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate">
                                            <strong>{{ trans('core/base::cache.commands.clear_log.title') }}</strong>
                                            <div class="text-secondary text-truncate mt-n1">
                                                {{ trans('core/base::cache.commands.clear_log.description') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-core::button
                                            type="button"
                                            color="danger"
                                            class="btn-clear-cache"
                                            data-type="clear_log"
                                            data-url="{{ route('system.cache.clear') }}"
                                        >
                                            <x-core::icon name="ti ti-trash" class="me-1" />
                                            {{ trans('core/base::cache.clear_button') }}
                                        </x-core::button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-flex align-items-center">
                        <x-core::icon name="ti ti-info-circle" class="me-2 text-info" />
                        <small class="text-secondary">{{ trans('core/base::cache.footer_note') }}</small>
                    </div>
                </div>
            </x-core::card>
        </div>
    </div>
@endsection
