<?php

namespace Botble\ACL\Listeners;

use Botble\ACL\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;

class LoginListener
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->update(['last_login' => Carbon::now()]);
    }
}
