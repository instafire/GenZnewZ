<?php

namespace Botble\Theme;

use Botble\Base\Facades\BaseHelper;
use Botble\Theme\Facades\Theme as ThemeFacade;
use Botble\Theme\Services\ThemeService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Manager
{
    protected array $themes = [];

    public function __construct(protected ThemeService $themeService)
    {
        $this->registerTheme(self::getAllThemes());
    }

    public function registerTheme(string|array $theme): void
    {
        if (! is_array($theme)) {
            $theme = [$theme];
        }

        $this->themes = array_merge_recursive($this->themes, $theme);
    }

    public function getAllThemes(): array
    {
        $themes = [];
        foreach (BaseHelper::scanFolder(theme_path()) as $folder) {
            $jsonFile = $this->getThemeJsonPath($folder);

            $publicJsonFile = public_path('themes/' . ThemeFacade::getPublicThemeName() . '/theme.json');

            if (File::exists($publicJsonFile)) {
                $jsonFile = $publicJsonFile;
            }

            if (! File::exists($jsonFile)) {
                continue;
            }

            $theme = BaseHelper::getFileData($jsonFile);

            if (! empty($theme)) {
                $themeConfig = $this->themeService->getThemeConfig($folder);

                $themes[$folder] = $theme;
                $themes[$folder]['inherit'] = Arr::get($themeConfig, 'inherit');
            }
        }

        return $themes;
    }

    public function getThemePresets(string $theme): array
    {
        if (! $theme || ! File::exists($jsonFile = $this->getThemeJsonPath($theme))) {
            return [];
        }

        $theme = BaseHelper::getFileData($jsonFile);

        return $theme['presets'] ?? [];
    }

    public function getThemes(): array
    {
        return $this->themes;
    }

    protected function getThemeJsonPath(string $theme): string
    {
        return  theme_path() . '/' . $theme . '/theme.json';
    }
}
