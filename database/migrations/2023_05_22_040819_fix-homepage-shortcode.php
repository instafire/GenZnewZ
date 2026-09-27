<?php

use Botble\Base\Facades\BaseHelper;
use Botble\Page\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        $homepageId = BaseHelper::getHomepageId();

        if ($homepageId) {
            $homepage = Page::query()->find($homepageId);

            if ($homepage) {
                $homepage->content = '<div>[featured-posts limit="5"][/featured-posts]</div>' . $homepage->content;

                $homepage->save();
            }
        }
    }
};
