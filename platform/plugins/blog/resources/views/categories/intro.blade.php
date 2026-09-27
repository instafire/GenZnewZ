@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.body class="text-center">
            <h3 class="mb-3">{{ trans('plugins/blog::categories.menu') }}</h3>
            <p class="text-muted mb-4">{{ trans('plugins/blog::categories.intro.description', ['default' => 'Organize your blog posts into categories for better content management.']) }}</p>
            
            <x-core::button
                tag="a"
                :href="route('categories.create')"
                color="primary"
                icon="ti ti-plus"
            >
                {{ trans('plugins/blog::categories.create') }}
            </x-core::button>
        </x-core::card.body>
    </x-core::card>
@endsection
