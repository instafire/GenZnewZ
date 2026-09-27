<?php

namespace Theme\Newspaper\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Hash;

class Member extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    protected $table = 'members';
    
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'avatar',
        'phone',
        'status',
        'verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function comments()
    {
        return $this->morphMany(\Botble\FobComment\Models\Comment::class, 'author');
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return \Botble\Media\Facades\RvMedia::getImageUrl($this->avatar, 'thumb');
        }
        return 'https://www.gravatar.com/avatar/' . md5(strtolower($this->email)) . '?d=mp&s=100';
    }
}
