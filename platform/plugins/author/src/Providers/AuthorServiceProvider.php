<?php

namespace Botble\Author\Providers;

use Botble\Author\Http\Requests\CustomPostRequest;
use Botble\Author\Models\Author;
use Botble\Author\Repositories\Eloquent\AuthorRepository;
use Botble\Author\Repositories\Interfaces\AuthorInterface;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Blog\Forms\PostForm;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\SeoHelper\SeoOpenGraph;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Botble\Table\CollectionDataTable;
use Botble\Table\EloquentDataTable;
use Botble\Theme\Facades\Theme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AuthorServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->bind(AuthorInterface::class, function () {
            return new AuthorRepository(new Author());
        });
    }

    public function boot(): void
    {
        SlugHelper::registerModule(Author::class, __('Authors'));
        SlugHelper::setPrefix(Author::class, 'author', true);

        $this
            ->setNamespace('plugins/author')
            ->loadAndPublishConfigurations(['permissions'])
            ->loadHelpers()
            ->loadMigrations()
            ->loadAndPublishTranslations()
            ->loadRoutes()
            ->loadAndPublishViews();

        DashboardMenu::beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-plugins-author',
                    'priority' => 5,
                    'name' => 'plugins/author::author.name',
                    'icon' => 'ti ti-user-circle',
                    'url' => route('author.index'),
                    'permissions' => ['author.index'],
                ]);
        });

        $this->app->booted(function (): void {
            SeoHelper::registerModule(Author::class);

            if (is_plugin_active('blog')) {
                PostForm::extend(function (PostForm $form) {
                    return $form
                        ->setValidatorClass(CustomPostRequest::class)
                        ->addAfter(
                            'status',
                            'author_id',
                            SelectField::class,
                            SelectFieldOption::make()
                                ->label(__('Author'))
                                ->choices(
                                    Author::query()
                                        ->wherePublished()
                                        ->pluck('name', 'id')
                                        ->all()
                                )
                                ->required()
                                ->emptyValue(__('Select an author...'))
                                ->toArray()
                        );
                }, 127);

                PostForm::beforeSaving(function (PostForm $form) {
                    $form->getModel()->author_id = $form->getRequest()->input('author_id');
                    $form->getModel()->author_type = Author::class;

                    return $form;
                }, 127);

                add_filter(
                    BASE_FILTER_GET_LIST_DATA,
                    function (EloquentDataTable|CollectionDataTable $data, Model|string|null $model) {
                        if (! $model instanceof Post) {
                            return $data;
                        }

                        return $data
                            ->filter(function ($query) use ($data) {
                                if ($keyword = $data->request->input('search.value')) {
                                    return $query
                                        ->where('name', 'LIKE', '%' . $keyword . '%')
                                        ->orWhereHas('categories', function ($subQuery) use ($keyword) {
                                            return $subQuery->where('name', 'LIKE', '%' . $keyword . '%');
                                        })
                                        ->orWhereHas('author', function ($subQuery) use ($keyword) {
                                            return $subQuery->where('name', 'LIKE', '%' . $keyword . '%');
                                        });
                                }

                                return $query;
                            });
                    },
                    123,
                    2
                );
            }
        });

        $this->app->register(EventServiceProvider::class);

        add_filter(BASE_FILTER_PUBLIC_SINGLE_DATA, function (Slug|array $slug): Slug|array {
            if (! $slug instanceof Slug || $slug->reference_type != Author::class) {
                return $slug;
            }

            $condition = [
                'id' => $slug->reference_id,
                'status' => BaseStatusEnum::PUBLISHED,
            ];

            if (Auth::guard()->check() && request()->input('preview')) {
                Arr::forget($condition, 'status');
            }

            $author = Author::query()
                ->where($condition)
                ->with(['slugable'])
                ->firstOrFail();

            SeoHelper::setTitle($author->name)->setDescription($author->description);

            $meta = new SeoOpenGraph();
            if ($author->avatar) {
                $meta->setImage(RvMedia::getImageUrl($author->avatar));
            }
            $meta->setDescription($author->description);
            $meta->setUrl($author->url);
            $meta->setTitle($author->name);
            $meta->setType('article');

            SeoHelper::setSeoOpenGraph($meta);

            Theme::breadcrumb()->add($author->name);

            do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, AUTHOR_MODULE_SCREEN_NAME, $author);

            $posts = Post::query()
                ->wherePublished()
                ->where([
                    'author_id' => $author->getKey(),
                    'author_type' => Author::class,
                ])
                ->orderByDesc('created_at')
                ->paginate(12);

            return [
                'view' => 'author',
                'default_view' => 'plugins/author::themes.author',
                'data' => compact('author', 'posts'),
                'slug' => $author->slug,
            ];
        }, 2);
    }
}
