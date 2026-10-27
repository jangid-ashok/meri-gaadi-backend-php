<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class RecordAdminLogin
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof User && $event->user->is_admin) {
            app(AuditLogger::class)->record('login', 'Authentication', 'Admin logged in', actor: $event->user);
        }
    }
}
