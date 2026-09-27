<?php

namespace Botble\Note\Providers;

use Botble\Base\Facades\AdminHelper;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Forms\FormTab;
use Botble\Base\Models\BaseModel;
use Botble\Base\Supports\ServiceProvider;
use Botble\Blog\Models\Post;
use Botble\Note\Models\Note;
use Botble\Note\Note as NoteSupport;
use Illuminate\Database\Eloquent\Model;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (is_plugin_active('blog')) {
            NoteSupport::registerModule(Post::class);
        }

        FormAbstract::extend(function (FormAbstract $form): void {
            $model = $form->getModel();

            if (
                ! $model instanceof BaseModel
                || ! $model->exists
                || ! in_array($model::class, NoteSupport::getSupportedModels())
                || ! AdminHelper::isInAdmin(true)
            ) {
                return;
            }

            $notes = Note::query()
                ->where([
                    'reference_id' => $model->getKey(),
                    'reference_type' => $model::class,
                ])
                ->get();

            $form->addTab(
                FormTab::make()
                    ->id('notes')
                    ->label(trans('plugins/note::note.record_note', ['count' => $notes->count()]))
                    ->content(view('plugins/note::content', compact('notes')))
            );
        }, 998);
    }

    protected function isSupported(?Model $model): bool
    {
        if (! $model) {
            return false;
        }

        return in_array($model::class, config('plugins.note.general.supported', []));
    }
}
