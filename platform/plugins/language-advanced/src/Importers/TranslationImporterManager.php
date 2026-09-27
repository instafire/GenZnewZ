<?php

namespace Botble\LanguageAdvanced\Importers;

use Botble\DataSynchronize\Importer\Importer;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class TranslationImporterManager
{
    /**
     * @var array<string, string>
     */
    protected array $importers = [];

    /**
     * Register a new importer
     */
    public function register(string $type, string $importerClass): self
    {
        $this->importers[$type] = $importerClass;

        return $this;
    }

    /**
     * Get an importer by type
     */
    public function getImporter(string $type): Importer
    {
        if (! Arr::has($this->importers, $type)) {
            throw new Exception(sprintf('Importer type [%s] is not registered', $type));
        }

        $importerClass = $this->importers[$type];

        return App::make($importerClass);
    }

    /**
     * Get all registered importers
     */
    public function getImporters(): array
    {
        return $this->importers;
    }
}
