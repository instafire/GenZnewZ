<?php

namespace Botble\LanguageAdvanced\Importers;

use Botble\DataSynchronize\Contracts\Importer\WithMapping;
use Botble\DataSynchronize\Importer\ImportColumn;
use Botble\DataSynchronize\Importer\Importer;
use Botble\Language\Facades\Language as LanguageFacade;
use Botble\LanguageAdvanced\Supports\LanguageAdvancedManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ModelTranslationImporter extends Importer implements WithMapping
{
    protected string $modelClass;

    public function __construct(?string $modelClass = null)
    {
        $this->modelClass = $modelClass ?: request()->input('class');
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getLabel(): string
    {
        $modelName = class_basename($this->modelClass);

        return trans('plugins/language-advanced::language-advanced.import_model_translations', ['model' => $modelName]);
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
        $translatableColumns = LanguageAdvancedManager::getTranslatableColumns($this->modelClass);

        foreach ($supportedLocales as $properties) {
            if ($properties['lang_code'] != $defaultLanguage) {
                $langCode = $properties['lang_code'];

                foreach ($translatableColumns as $column) {
                    if (! Schema::hasColumn($this->modelClass::query()->getModel()->getTable() . '_translations', $column)) {
                        continue;
                    }

                    $maxLength = $column === 'content' ? 300000 : ($column === 'description' ? 400 : 300000);

                    $columns[] = ImportColumn::make($column . '_(' . $langCode . ')')
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
        return route('tools.data-synchronize.import.translations.validate', ['type' => 'model', 'class' => $this->modelClass]);
    }

    public function getImportUrl(): string
    {
        return route('tools.data-synchronize.import.translations.store', ['type' => 'model', 'class' => $this->modelClass]);
    }

    public function getDownloadExampleUrl(): ?string
    {
        return route('tools.data-synchronize.import.translations.download-example', ['type' => 'model' , 'class' => $this->modelClass]);
    }

    public function getExportUrl(): ?string
    {
        return Auth::user()->hasPermission('translations.export')
            ? route('tools.data-synchronize.export.translations.store', ['type' => 'model', 'class' => $this->modelClass])
            : null;
    }

    public function map(mixed $row): array
    {
        return $row;
    }

    public function examples(): array
    {
        $defaultLanguage = LanguageFacade::getDefaultLanguage(['lang_code'])?->lang_code;
        $supportedLocales = LanguageFacade::getSupportedLocales();
        $translatableColumns = LanguageAdvancedManager::getTranslatableColumns($this->modelClass);

        $tableName = app($this->modelClass)->getTable();
        $translationTable = $tableName . '_translations';
        $foreignKey = $tableName . '_id';

        $items = $this->modelClass::query()
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
                        $example[$column . '_(' . $langCode . ')'] = '';
                    }
                }
            }
        }

        return $examples;
    }

    public function handle(array $data): int
    {
        $count = 0;
        $defaultLanguage = LanguageFacade::getDefaultLanguage(['lang_code'])?->lang_code;
        $supportedLocales = LanguageFacade::getSupportedLocales();
        $translatableColumns = LanguageAdvancedManager::getTranslatableColumns($this->modelClass);

        $tableName = app($this->modelClass)->getTable();
        $translationTable = $tableName . '_translations';
        $foreignKey = $tableName . '_id';

        foreach ($data as $row) {
            $itemId = $row['id'];
            $item = $this->modelClass::query()->find($itemId);

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
                        $columnKey = $column . '_(' . $langCode . ')';
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

    public static function make(?string $modelClass = null): static
    {
        return app(static::class, ['modelClass' => $modelClass]);
    }
}
