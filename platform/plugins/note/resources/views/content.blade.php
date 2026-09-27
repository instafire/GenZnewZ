<x-core::form-group class="row">
    <x-core::form.label class="col-sm-2" for="note">
        {{ trans('plugins/note::note.note') }}
    </x-core::form.label>
    <div class="col-sm-10">
        <x-core::form.textarea name="note" rows="4" />
    </div>
</x-core::form-group>

@if ($notes->isNotEmpty())
    <x-core::table>
        <x-core::table.header>
            <x-core::table.header.cell>
                {{ trans('core/base::tables.author') }}
            </x-core::table.header.cell>
            <x-core::table.header.cell>
                {{ trans('core/base::tables.notes') }}
            </x-core::table.header.cell>
            <x-core::table.header.cell>
                {{ trans('core/base::tables.created_at') }}
            </x-core::table.header.cell>
        </x-core::table.header>
        <x-core::table.body>
            @foreach ($notes as $note)
                <x-core::table.body.row>
                    <x-core::table.body.cell style="min-width: 145px;">
                        {{ $note->createdBy->name }}
                    </x-core::table.body.cell>
                    <x-core::table.body.cell>
                        {{ $note->note }}
                    </x-core::table.body.cell>
                    <x-core::table.body.cell style="min-width: 145px;">
                        {{ BaseHelper::formatDate($note->created_at) }}
                    </x-core::table.body.cell>
                </x-core::table.body.row>
            @endforeach
        </x-core::table.body>
    </x-core::table>
@endif

