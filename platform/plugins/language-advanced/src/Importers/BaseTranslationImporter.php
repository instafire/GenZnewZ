<?php

namespace Botble\LanguageAdvanced\Importers;

use Botble\DataSynchronize\Contracts\Importer\WithMapping;
use Botble\DataSynchronize\Importer\ImportColumn;
use Botble\DataSynchronize\Importer\Importer;
use Botble\Language\Facades\Language as LanguageFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class BaseTranslationImporter extends Importer implements WithMapping
{
    /**
     * Get the model class for the importer
     */
    abstract protected function getModel(): string;

    /**
     * Get the table name for translations
     */
    abstract protected function getTranslationTable(): string;

    /**
     * Get the foreign key column name in the translation table
     */
    abstract protected function getForeignKeyName(): string;

    /**
     * Get the translatable columns
     */
    abstract protected function getTranslatableColumns(): array;

    /**
     * Get the permission for export
     */
    abstract protected function getExportPermission(): string;

    /**
     * Get the route name prefix for import/export
     */
    abstract protected function getRouteNamePrefix(): string;

    /**
     * Get the translation label
     */
    abstract protected function getTranslationLabel(): string;

    public function chunkSize(): int
    {
        return 100;
    }

    public function getLabel(): string
    {
        return $this->getTranslationLabel();
    }

    public function columns(): array
    {
        $columns = [
            ImportColumn::make('id')
                ->label('ID')
                ->rules(['required', 'integer'], trans('plugins/language-advanced::language-advanced.import.rules.id')),
            ImportColumn::make('name')
                ->label('Name')
                ->rules(['required', 'string', 'max:255'], trans('plugins/language-advanced::language-advanced.import.rules.name')),
        ];

        $defaultLanguage = LanguageFacade::getDefaultLanguage(['lang_code'])?->lang_code;
        $supportedLocales = LanguageFacade::getSupportedLocales();
        $translatableColumns = $this->getTranslatableColumns();

        foreach ($supportedLocales as $properties) {
            if ($properties['lang_code'] != $defaultLanguage) {
                $langCode = $properties['lang_code'];

                foreach ($translatableColumns as $column) {
                    $maxLength = $column === 'content' ? 300000 : ($column === 'description' ? 400 : 255);

                    $columns[] = ImportColumn::make($column . '_' . $langCode)
                        ->label(Str::title($column) . ' (' . $langCode . ')')
                        ->rules(
                            ['nullable', 'string', 'max:' . $maxLength],
                            trans('plugins/language-advanced::language-advanced.import.rules.' . $column, ['max' => $maxLength])
                        );
                }
            }
        }

        return $columns;
    }

    public function getValidateUrl(): string
    {
        return route('tools.data-synchronize.import.' . $this->getRouteNamePrefix() . '.validate');
    }

    public function getImportUrl(): string
    {
        return route('tools.data-synchronize.import.' . $this->getRouteNamePrefix() . '.store');
    }

    public function getDownloadExampleUrl(): ?string
    {
        return route('tools.data-synchronize.import.' . $this->getRouteNamePrefix() . '.download-example');
    }

    public function getExportUrl(): ?string
    {
        return Auth::user()->hasPermission($this->getExportPermission())
            ? route('tools.data-synchronize.export.' . $this->getRouteNamePrefix() . '.store')
            : null;
    }

    public function map(mixed $row): array
    {
        return $row;
    }

    public function examples(): array
    {
        $modelClass = $this->getModel();
        $defaultLanguage = LanguageFacade::getDefaultLanguage(['lang_code'])?->lang_code;
        $supportedLocales = LanguageFacade::getSupportedLocales();
        $translatableColumns = $this->getTranslatableColumns();
        $translationTable = $this->getTranslationTable();
        $foreignKey = $this->getForeignKeyName();

        $items = $modelClass::query()
            ->take(5)
            ->get()
            ->map(function (Model $item) use ($defaultLanguage, $supportedLocales, $translatableColumns, $foreignKey, $translationTable) {
                $data = [
                    'id' => $item->id,
                    'name' => $item->name,
                ];

                foreach ($supportedLocales as $locale) {
                    if ($locale['lang_code'] != $defaultLanguage) {
                        $langCode = $locale['lang_code'];
                        $translation = DB::table($translationTable)
                            ->where($foreignKey, $item->id)
                            ->where('lang_code', $langCode)
                            ->first();

                        foreach ($translatableColumns as $column) {
                            $data[$column . '_' . $langCode] = $translation->{$column} ?? '';
                        }
                    }
                }

                return $data;
            });

        if ($items->isNotEmpty()) {
            return $items->all();
        }

        // Example data if no items exist
        $examples = [
            [
                'id' => 1,
                'name' => 'Example Item 1',
            ],
            [
                'id' => 2,
                'name' => 'Example Item 2',
            ],
            [
                'id' => 3,
                'name' => 'Example Item 3',
            ],
        ];

        foreach ($supportedLocales as $locale) {
            if ($locale['lang_code'] != $defaultLanguage) {
                $langCode = $locale['lang_code'];

                foreach ($examples as &$example) {
                    foreach ($translatableColumns as $column) {
                        $example[$column . '_' . $langCode] = '';
                    }
                }
            }
        }

        return $examples;
    }

    public function handle(array $data): int
    {
        $count = 0;
        $modelClass = $this->getModel();
        $defaultLanguage = LanguageFacade::getDefaultLanguage(['lang_code'])?->lang_code;
        $supportedLocales = LanguageFacade::getSupportedLocales();
        $translatableColumns = $this->getTranslatableColumns();
        $translationTable = $this->getTranslationTable();
        $foreignKey = $this->getForeignKeyName();

        foreach ($data as $row) {
            $itemId = $row['id'];
            $item = $modelClass::query()->find($itemId);

            if (! $item) {
                continue;
            }

            foreach ($supportedLocales as $locale) {
                if ($locale['lang_code'] != $defaultLanguage) {
                    $langCode = $locale['lang_code'];

                    $translationData = [
                        'lang_code' => $langCode,
                        $foreignKey => $itemId,
                    ];

                    foreach ($translatableColumns as $column) {
                        $columnKey = $column . '_' . $langCode;
                        if (isset($row[$columnKey])) {
                            $translationData[$column] = $row[$columnKey];
                        }
                    }

                    // Only update if we have at least one translatable column with data
                    if (count($translationData) > 2) {
                        DB::table($translationTable)
                            ->updateOrInsert(
                                [
                                    'lang_code' => $langCode,
                                    $foreignKey => $itemId,
                                ],
                                $translationData
                            );

                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
