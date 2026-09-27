<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Page\Database\Traits\HasPageSeeder;
use Botble\Theme\Database\Traits\HasThemeOptionSeeder;
use Carbon\Carbon;

class ThemeOptionSeeder extends BaseSeeder
{
    use HasThemeOptionSeeder;
    use HasPageSeeder;

    public function run(): void
    {
        $this->createThemeOptions([
            'site_title' => 'LaraMag - Laravel News / Magazine Multilingual System',
            'seo_description' => ' Laravel News / Magazine Multilingual System',
            'copyright' => sprintf('©%s Your Company. All right reserved.', Carbon::now()->year),
            'cookie_consent_message' => 'Your experience on this site will be improved by allowing cookies ',
            'cookie_consent_learn_more_url' => '/cookie-policy',
            'cookie_consent_learn_more_text' => 'Cookie Policy',
            'homepage_id' => $this->getPageId('Homepage'),
        ]);
    }
}
