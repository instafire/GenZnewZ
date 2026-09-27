<?php

namespace Botble\LanguageAdvanced\Http\Controllers;

use Botble\DataSynchronize\Exporter\Exporter;
use Botble\DataSynchronize\Http\Controllers\ExportController;
use Botble\LanguageAdvanced\Exporters\TranslationExporterManager;

class TranslationExportController extends ExportController
{
    protected TranslationExporterManager $exporterManager;

    protected ?string $type;

    public function __construct(TranslationExporterManager $exporterManager)
    {
        $this->exporterManager = $exporterManager;
        $this->type = request()->route('type');
    }

    protected function getExporter(): Exporter
    {
        return $this->exporterManager->getExporter($this->type);
    }
}
