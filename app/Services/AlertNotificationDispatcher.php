<?php

namespace App\Services;

use App\Models\Alert;

/**
 * Placeholder notification dispatcher for future external channels.
 *
 * Current behavior is intentionally no-op to keep the architecture ready
 * without introducing email/WhatsApp integrations at this stage.
 */
class AlertNotificationDispatcher
{
    public function dispatchForNewAlert(Alert $alert): void
    {
        // Intentionally empty for Phase 11.
    }
}
