<?php

namespace Botble\Sitemap\Http\Controllers;

use Botble\Base\Services\ClearCacheService;
use Botble\Setting\Http\Controllers\SettingController;
use Botble\Sitemap\Events\SitemapUpdatedEvent;
use Botble\Sitemap\Forms\Settings\SitemapSettingForm;
use Botble\Sitemap\Http\Requests\SitemapSettingRequest;
use Botble\Sitemap\Services\IndexNowService;
use Illuminate\Http\JsonResponse;

class SitemapSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle(trans('packages/sitemap::sitemap.settings.title'));

        return SitemapSettingForm::create()->renderForm();
    }

    public function update(SitemapSettingRequest $request)
    {
        // Check if sitemap_items_per_page has changed
        $oldItemsPerPage = setting('sitemap_items_per_page');
        $newItemsPerPage = $request->input('sitemap_items_per_page');

        $response = $this->performUpdate($request->validated());

        // Handle IndexNow API key file creation if key was provided
        if ($request->has('indexnow_api_key') && $request->input('indexnow_api_key')) {
            $indexNowService = app(IndexNowService::class);
            $apiKey = $request->input('indexnow_api_key');

            // Create key file if it doesn't exist or is invalid
            if (! $indexNowService->keyFileExists() || ! $indexNowService->validateKeyFile()) {
                $indexNowService->createKeyFile($apiKey);
            }
        }

        // Clear sitemap cache if sitemap_enabled or sitemap_items_per_page has changed
        if ($request->has('sitemap_enabled') || ($oldItemsPerPage != $newItemsPerPage && $newItemsPerPage)) {
            // Use the new centralized method to clear all sitemap caches
            ClearCacheService::make()->clearFrameworkCache();

            // Fire sitemap updated event to trigger IndexNow submission
            event(new SitemapUpdatedEvent());
        }

        return $response->withUpdatedSuccessMessage();
    }

    public function generateKey(IndexNowService $indexNowService): JsonResponse
    {
        try {
            $apiKey = $indexNowService->generateApiKey();
            $keyFileExists = $indexNowService->keyFileExists();
            $keyFileValid = $keyFileExists ? $indexNowService->validateKeyFile() : false;

            $message = trans('packages/sitemap::sitemap.settings.api_key_generated');

            if ($keyFileExists && $keyFileValid) {
                $message .= ' ' . trans('packages/sitemap::sitemap.settings.key_file_created');
            } elseif ($keyFileExists && ! $keyFileValid) {
                $message .= ' ' . trans('packages/sitemap::sitemap.settings.key_file_created_invalid');
            } else {
                $message .= ' ' . trans('packages/sitemap::sitemap.settings.key_file_creation_failed');
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'api_key' => $apiKey,
                'key_file_name' => $indexNowService->getApiKeyFileName(),
                'key_file_url' => url($indexNowService->getApiKeyFileName()),
                'key_file_exists' => $keyFileExists,
                'key_file_valid' => $keyFileValid,
                'key_file_path' => $indexNowService->getKeyFilePath(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('packages/sitemap::sitemap.settings.generate_key_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createKeyFile(IndexNowService $indexNowService): JsonResponse
    {
        try {
            $apiKey = $indexNowService->getApiKey();

            if (! $apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => trans('packages/sitemap::sitemap.settings.no_api_key_to_create_file'),
                ], 400);
            }

            $success = $indexNowService->createKeyFile($apiKey);
            $keyFileExists = $indexNowService->keyFileExists();
            $keyFileValid = $keyFileExists ? $indexNowService->validateKeyFile() : false;

            if ($success && $keyFileValid) {
                $message = trans('packages/sitemap::sitemap.settings.key_file_created_successfully');
            } elseif ($keyFileExists && ! $keyFileValid) {
                $message = trans('packages/sitemap::sitemap.settings.key_file_created_but_invalid');
            } else {
                $message = trans('packages/sitemap::sitemap.settings.key_file_creation_failed_detailed');
            }

            return response()->json([
                'success' => $success && $keyFileValid,
                'message' => $message,
                'api_key' => $apiKey,
                'key_file_name' => $indexNowService->getApiKeyFileName(),
                'key_file_url' => url($indexNowService->getApiKeyFileName()),
                'key_file_exists' => $keyFileExists,
                'key_file_valid' => $keyFileValid,
                'key_file_path' => $indexNowService->getKeyFilePath(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('packages/sitemap::sitemap.settings.key_file_creation_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
