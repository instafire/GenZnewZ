<?php

namespace Botble\Note\Models;

use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends BaseModel
{
    protected $table = 'notes';

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }
}
