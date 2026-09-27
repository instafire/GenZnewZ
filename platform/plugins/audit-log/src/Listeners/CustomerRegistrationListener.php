<?php

namespace Botble\AuditLog\Listeners;

use Botble\AuditLog\Models\AuditHistory;
use Botble\Base\Facades\BaseHelper;
use Botble\Ecommerce\Models\Customer;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;

class CustomerRegistrationListener
{
    public function __construct(protected Request $request)
    {
    }

    public function handle(Registered $event): void
    {
        if (! is_plugin_active('ecommerce')) {
            return;
        }

        $user = $event->user;

        if (! $user instanceof Customer) {
            return;
        }

        try {
            AuditHistory::query()->create([
                'user_agent' => $this->request->userAgent(),
                'ip_address' => $this->request->ip(),
                'module' => trans('plugins/audit-log::history.register_an_account'),
                'action' => trans('plugins/audit-log::history.registered'),
                'user_id' => $user->getKey(),
                'user_type' => $user::class,
                'actor_id' => $user->getKey(),
                'actor_type' => $user::class,
                'reference_id' => $user->getKey(),
                'reference_name' => $user->name,
                'type' => 'info',
            ]);
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
