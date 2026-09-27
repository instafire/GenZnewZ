<div class="row">
    <div class="col-auto">
        @if (class_exists($history->user_type) && $history->user)
            <img
                src="{{ $history->user->avatar_url }}"
                class="avatar"
                alt="{{ $history->user_name }}"
            />
        @else
            <img
                src="{{ AdminHelper::getAdminFaviconUrl() }}"
                class="avatar"
                alt="{{ trans('plugins/audit-log::history.system') }}"
            />
        @endif
    </div>
    <div class="col">
        <div class="text-truncate">
            <strong>
                @if (class_exists($history->user_type) && $user = $history->user)
                    <a href="{{ $user->url ?? '#' }}">{{ $history->user_name }}</a>
                    <span class="badge bg-primary text-white">{{ $history->user_type_label }}</span>
                @else
                    {{ trans('plugins/audit-log::history.system') }}
                @endif
            </strong>

            @if (Lang::has("plugins/audit-log::history.$history->action"))
                {{ trans("plugins/audit-log::history.$history->action") }}
            @else
                {{ $history->action }}
            @endif

            @if ($history->module != 'user' || (class_exists($history->user_type) && empty($history->user)) || (class_exists($history->user_type) && $history->user->id != Auth::guard()->id()))
                @if (Lang::has("plugins/audit-log::history.$history->module"))
                    {{ trans("plugins/audit-log::history.$history->module") }}
                @else
                    {{ $history->module }}
                @endif
            @endif

            @if ($history->reference_name && $history->user_name != $history->reference_name)
                <span title="{{ $history->reference_name }}">"{{ Str::limit($history->reference_name, 40) }}"</span>
            @endif
        </div>
        <div class="text-muted">
            {{ $history->created_at->diffForHumans() }}
            (<a
                href="https://ipinfo.io/{{ $history->ip_address }}"
                target="_blank"
                title="{{ $history->ip_address }}"
                rel="nofollow"
            >{{ $history->ip_address }}</a>)
        </div>
    </div>
</div>
