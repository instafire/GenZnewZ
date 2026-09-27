<?php

namespace Botble\LanguageAdvanced\Exporters;

use Botble\DataSynchronize\Exporter\Exporter;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class TranslationExporterManager
{
    /**
     * @var array<string, string>
     */
    protected array $exporters = [];

    /**
     * Register a new exporter
     */
    public function register(string $type, string $exporterClass): self
    {
        $this->exporters[$type] = $exporterClass;

        return $this;
    }

    /**
     * Get an exporter by type
     */
    public function getExporter(string $type): Exporter
    {
        if (! Arr::has($this->exporters, $type)) {
            throw new Exception(sprintf('Exporter type [%s] is not registered', $type));
        }

        $exporterClass = $this->exporters[$type];

        return App::make($exporterClass);
    }

    /**
     * Get all registered exporters
     */
    public function getExporters(): array
    {
        return $this->exporters;
    }
}
