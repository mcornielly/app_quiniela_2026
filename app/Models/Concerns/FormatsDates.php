<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

trait FormatsDates
{
    public function formatDateForInput($date)
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    public function formatTimeForInput($time)
    {
        if (!$time) {
            return null;
        }

        return Carbon::parse($time)->format('H:i');
    }
}
