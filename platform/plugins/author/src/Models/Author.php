<?php

namespace Botble\Author\Models;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Author extends BaseModel
{
    protected $table = 'authors';

    protected $fillable = [
        'name',
        'email',
        'description',
        'avatar',
        'status',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
    ];

    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst((string) $this->splitName($this->name)[0])
        );
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst((string) $this->splitName($this->name)[1])
        );
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                return RvMedia::getImageUrl($this->avatar, 'thumb', false, RvMedia::getDefaultImage());
            }
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => ucfirst($this->name)
        );
    }

    public function posts(): MorphMany
    {
        return $this->morphMany(Post::class, 'author');
    }

    protected function splitName(?string $name): array
    {
        $name = trim($name);

        $lastName = (! str_contains($name, ' ')) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);

        $firstName = trim(preg_replace('#' . preg_quote($lastName, '#') . '#', '', $name));

        return [$firstName, $lastName];
    }
}
