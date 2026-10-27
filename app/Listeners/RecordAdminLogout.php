<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class RecordAdminLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user instanceof User && $event->user->is_admin) {
            app(AuditLogger::class)->record('logout', 'Authentication', 'Admin logged out', actor: $event->user);
        }
    }
}
