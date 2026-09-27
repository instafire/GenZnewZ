<?php

namespace Botble\LanguageAdvanced\Http\Controllers;

use Botble\DataSynchronize\Http\Controllers\ImportController;
use Botble\DataSynchronize\Http\Requests\DownloadTemplateRequest;
use Botble\DataSynchronize\Http\Requests\ImportRequest;
use Botble\DataSynchronize\Importer\Importer;
use Botble\LanguageAdvanced\Importers\TranslationImporterManager;

class TranslationImportController extends ImportController
{
    protected string $type;

    public function __construct(protected TranslationImporterManager $importerManager)
    {
    }

    protected function getImporter(): Importer
    {
        // If the type is 'model', we need to get the actual model class from the request
        if ($this->type === 'model') {
            $modelClass = request()->input('class');
            if ($modelClass) {
                return $this->importerManager->getImporter($this->type)->make($modelClass);
            }
        }

        return $this->importerManager->getImporter($this->type);
    }

    public function index()
    {
        $this->type = request()->route('type');

        return parent::index();
    }

    public function validateData(ImportRequest $request)
    {
        $this->type = request()->route('type');

        return parent::validateData($request);
    }

    public function import(ImportRequest $request)
    {
        $this->type = request()->route('type');

        return parent::import($request);
    }

    public function downloadExample(DownloadTemplateRequest $request)
    {
        $this->type = request()->route('type');

        return parent::downloadExample($request);
    }
}
