<?php

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Services\LineMessagingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('bookings:send-reminders', function (LineMessagingService $line) {
    $now = now();
    $remindersSent = 0;

    $bookings = Booking::with(['member', 'branch', 'employee', 'service'])
        ->whereIn('status', ['pending', 'confirmed'])
        ->whereDate('booking_date', '>=', $now->toDateString())
        ->whereDate('booking_date', '<=', $now->copy()->addDay()->toDateString())
        ->get();

    foreach ($bookings as $booking) {
        $startAt = now()->parse($booking->booking_date->format('Y-m-d').' '.$booking->start_time);
        $untilStart = $now->diffInSeconds($startAt, false);

        foreach (['reminder_24h' => 86400, 'reminder_1h' => 3600] as $type => $seconds) {
            $alreadyLogged = NotificationLog::where('booking_id', $booking->id)->where('type', $type)->exists();
            $isDue = $untilStart > 0 && $untilStart <= $seconds
                && ($type !== 'reminder_24h' || $untilStart > 3600);

            if (!$alreadyLogged && $isDue) {
                $line->sendReminder($booking, $type);
                $remindersSent++;
            }
        }
    }

    $this->info("Processed {$remindersSent} booking reminder(s).");
})->purpose('Send LINE reminders 24 hours and 1 hour before a booking');

Schedule::command('bookings:send-reminders')
    ->everyMinute()
    ->withoutOverlapping();
