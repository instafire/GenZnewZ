<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\CookieConsent\Database\Traits\HasCookieConsentSeeder;
use Botble\Page\Database\Traits\HasPageSeeder;
use Botble\Page\Models\Page;
use Botble\Slug\Models\Slug;

class PageSeeder extends BaseSeeder
{
    use HasPageSeeder;
    use HasCookieConsentSeeder;

    public function run(): void
    {
        $this->truncatePages();

        Slug::query()->where('reference_type', Page::class)->delete();

        $pages = [
            [
                'name' => 'Homepage',
                'content' => '<div>[featured-posts limit="5" enable_lazy_loading="yes"][/featured-posts]</div><div>[simple-slider key="home-slider" enable_lazy_loading="yes"][/simple-slider]</div><div>[category-posts enable_lazy_loading="yes"][/category-posts]</div><div>[all-galleries enable_lazy_loading="yes"][/all-galleries]</div>',
                'template' => 'homepage',
            ],
            [
                'name' => 'Introduction',
                'content' => $this->fake()->text(1000),
            ],
            [
                'name' => 'About us',
                'content' => $this->fake()->text(1000),
            ],
            [
                'name' => 'Contact',
                'content' => '<p>' . $this->fake()->address() . '</p><p>' . $this->fake()->phoneNumber() . ' (4 lines) - ' . $this->fake()->phoneNumber() . '</p><p>' . $this->fake()->phoneNumber() . '</p><p>' . $this->fake()->companyEmail() . '&nbsp;&nbsp;</p><div>[contact-form][/contact-form]</div>',
            ],
            [
                'name' => $this->getCookieConsentPageName(),
                'content' => $this->getCookieConsentPageContent(),
            ],
        ];

        $this->createPages($pages);
    }
}
