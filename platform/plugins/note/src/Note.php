<?php

namespace Botble\Note;

use Botble\Base\Contracts\BaseModel;
use Botble\Page\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Note
{
    protected static array $supportedModels = [
        Page::class,
    ];

    public static function registerModule(array|string $model): void
    {
        if (! is_array($model)) {
            $model = [$model];
        }

        static::$supportedModels = [
            ...static::$supportedModels,
            ...$model,
        ];
    }

    public static function saveNote(Request $request, Model|null|bool $object): void
    {
        if (
            ! $object instanceof BaseModel
            || ! in_array($object::class, static::$supportedModels)
            || $request->isNotFilled('note')
        ) {
            return;
        }

        /**
         * @var BaseModel $actor
         */
        $actor = Auth::guard()->user();

        $note = new Models\Note();
        $note->note = $request->input('note');
        $note->user_id = $actor->getKey();
        $note->created_by = $actor->getKey();
        $note->reference_type = $object::class;
        $note->reference_id = $object->getKey();
        $note->save();
    }

    public static function getSupportedModels(): array
    {
        return static::$supportedModels;
    }

    public static function deleteNote(Model|null|bool $data): bool
    {
        if (! $data instanceof BaseModel) {
            return false;
        }

        Models\Note::query()
            ->where([
                'reference_id' => $data->getKey(),
                'reference_type' => $data::class,
            ])->delete();

        return true;
    }
}
