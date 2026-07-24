<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Member;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;

class LineMessagingService
{
    public function isConfigured(): bool
    {
        return filled(config('line.channel_access_token'));
    }

    public function hasValidSignature(string $body, ?string $signature): bool
    {
        if (!filled($signature) || !filled(config('line.channel_secret'))) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $body, config('line.channel_secret'), true));

        return hash_equals($expected, $signature);
    }

    public function sendBookingConfirmation(Booking $booking): void
    {
        $booking->loadMissing(['member', 'branch', 'employee', 'service']);
        $message = "SM HAIR DESIGN\nยืนยันการจองคิว\nเลขที่: {$booking->booking_no}\n"
            ."วันที่: {$booking->booking_date->format('d/m/Y')} เวลา: ".substr($booking->start_time, 0, 5)." น.\n"
            ."สาขา: {$booking->branch->name}\nช่าง: {$booking->employee->name}\n"
            ."บริการ: {$booking->service->name}";

        if ($booking->member->line_user_id) {
            $this->push($booking->member->line_user_id, $message, $booking->member, $booking, 'booking_confirmation', $this->bookingFlex($booking));
        }

        foreach ($this->adminUserIds() as $adminUserId) {
            $this->push($adminUserId, "[แจ้งเตือนผู้ดูแล]\n{$message}", null, $booking, 'booking_confirmation', $this->bookingFlex($booking, true));
        }
    }

    public function sendReminder(Booking $booking, string $type): void
    {
        if (!in_array($type, ['reminder_24h', 'reminder_1h'], true)) {
            throw new \InvalidArgumentException('Unsupported reminder type.');
        }

        $booking->loadMissing(['member', 'branch', 'employee', 'service']);
        $label = $type === 'reminder_24h' ? 'อีก 24 ชั่วโมง' : 'อีก 1 ชั่วโมง';
        $message = "SM HAIR DESIGN\nแจ้งเตือนนัดหมาย {$label}\n"
            ."วันที่: {$booking->booking_date->format('d/m/Y')} เวลา: ".substr($booking->start_time, 0, 5)." น.\n"
            ."สาขา: {$booking->branch->name}\nช่าง: {$booking->employee->name}\n"
            ."บริการ: {$booking->service->name}";

        if (!$booking->member->line_user_id) {
            NotificationLog::create([
                'member_id' => $booking->member->id,
                'booking_id' => $booking->id,
                'channel' => 'line',
                'type' => $type,
                'recipient' => $booking->member->phone,
                'message' => $message,
                'status' => 'failed',
                'response' => 'Member has not linked a LINE account.',
            ]);
            return;
        }

        $this->push($booking->member->line_user_id, $message, $booking->member, $booking, $type, $this->reminderFlex($booking, $type));
    }

    public function reply(string $replyToken, string $message): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        Http::withToken(config('line.channel_access_token'))
            ->acceptJson()
            ->post('https://api.line.me/v2/bot/message/reply', [
                'replyToken' => $replyToken,
                'messages' => [['type' => 'text', 'text' => $message]],
            ]);
    }

    private function push(string $userId, string $message, ?Member $member, Booking $booking, string $type, ?array $lineMessage = null): void
    {
        $log = NotificationLog::create([
            'member_id' => $member?->id,
            'booking_id' => $booking->id,
            'channel' => 'line',
            'type' => $type,
            'recipient' => $userId,
            'message' => $message,
            'status' => 'pending',
        ]);

        if (!$this->isConfigured()) {
            $log->update(['status' => 'failed', 'response' => 'LINE Messaging API is not configured.']);
            return;
        }

        try {
            $response = Http::withToken(config('line.channel_access_token'))
                ->acceptJson()
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $userId,
                    'messages' => [$lineMessage ?? ['type' => 'text', 'text' => $message]],
                ]);

            $log->update([
                'status' => $response->successful() ? 'sent' : 'failed',
                'response' => $response->body(),
                'sent_at' => $response->successful() ? now() : null,
            ]);
        } catch (\Throwable $exception) {
            $log->update(['status' => 'failed', 'response' => $exception->getMessage()]);
        }
    }

    private function adminUserIds(): array
    {
        return collect(explode(',', (string) config('line.admin_user_ids')))
            ->map(fn (string $userId) => trim($userId))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function bookingFlex(Booking $booking, bool $isAdmin = false): array
    {
        $time = substr($booking->start_time, 0, 5).' น.';
        $heading = $isAdmin ? 'มีรายการจองใหม่' : 'ยืนยันการจองสำเร็จ';

        return [
            'type' => 'flex',
            'altText' => "{$heading} · {$booking->booking_no}",
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'backgroundColor' => '#111111',
                    'paddingAll' => '20px',
                    'contents' => [
                        ['type' => 'text', 'text' => 'SM HAIR DESIGN', 'color' => '#FCA5A5', 'size' => 'xs', 'weight' => 'bold'],
                        ['type' => 'text', 'text' => $heading, 'color' => '#FFFFFF', 'size' => 'xl', 'weight' => 'bold', 'margin' => 'md'],
                        ['type' => 'text', 'text' => 'Booking No. '.$booking->booking_no, 'color' => '#FFFFFF99', 'size' => 'sm', 'margin' => 'sm'],
                    ],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => [
                        ['type' => 'text', 'text' => $booking->booking_date->format('d/m/Y').' · '.$time, 'weight' => 'bold', 'size' => 'md', 'color' => '#111111'],
                        ['type' => 'separator', 'margin' => 'md'],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'margin' => 'md',
                            'contents' => [
                                ['type' => 'text', 'text' => $time, 'size' => 'sm', 'color' => '#6B7280', 'flex' => 2],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'flex' => 0,
                                    'width' => '20px',
                                    'contents' => [
                                        ['type' => 'box', 'layout' => 'vertical', 'width' => '12px', 'height' => '12px', 'backgroundColor' => '#C1121F', 'cornerRadius' => '100px', 'contents' => []],
                                        ['type' => 'box', 'layout' => 'vertical', 'width' => '2px', 'height' => '34px', 'backgroundColor' => '#E5E7EB', 'margin' => 'sm', 'contents' => []],
                                    ],
                                ],
                                ['type' => 'text', 'text' => $booking->service->name, 'size' => 'sm', 'color' => '#111111', 'weight' => 'bold', 'flex' => 5, 'wrap' => true],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                ['type' => 'text', 'text' => 'สาขา', 'size' => 'sm', 'color' => '#6B7280', 'flex' => 2],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'flex' => 0,
                                    'width' => '20px',
                                    'contents' => [
                                        ['type' => 'box', 'layout' => 'vertical', 'width' => '12px', 'height' => '12px', 'borderColor' => '#C1121F', 'borderWidth' => '2px', 'cornerRadius' => '100px', 'contents' => []],
                                        ['type' => 'box', 'layout' => 'vertical', 'width' => '2px', 'height' => '34px', 'backgroundColor' => '#E5E7EB', 'margin' => 'sm', 'contents' => []],
                                    ],
                                ],
                                ['type' => 'text', 'text' => $booking->branch->name, 'size' => 'sm', 'color' => '#111111', 'flex' => 5, 'wrap' => true],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                ['type' => 'text', 'text' => 'ช่าง', 'size' => 'sm', 'color' => '#6B7280', 'flex' => 2],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'flex' => 0,
                                    'width' => '20px',
                                    'contents' => [
                                        ['type' => 'box', 'layout' => 'vertical', 'width' => '12px', 'height' => '12px', 'borderColor' => '#C1121F', 'borderWidth' => '2px', 'cornerRadius' => '100px', 'contents' => []],
                                    ],
                                ],
                                ['type' => 'text', 'text' => $booking->employee->name, 'size' => 'sm', 'color' => '#111111', 'flex' => 5, 'wrap' => true],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        ['type' => 'button', 'style' => 'primary', 'color' => '#C1121F', 'height' => 'sm', 'action' => ['type' => 'uri', 'label' => 'โทรหาร้าน', 'uri' => 'tel:0932125164']],
                        ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'uri', 'label' => 'Facebook · SM HAIR DESIGN', 'uri' => 'https://www.facebook.com/share/1BXXqxYu38/?mibextid=wwXIfr']],
                    ],
                ],
            ],
        ];
    }

    private function reminderFlex(Booking $booking, string $type): array
    {
        $isOneHour = $type === 'reminder_1h';
        $time = substr($booking->start_time, 0, 5).' น.';
        $remaining = $isOneHour ? 'อีก 1 ชั่วโมง' : 'อีก 24 ชั่วโมง';
        $accent = $isOneHour ? '#C1121F' : '#111111';

        return [
            'type' => 'flex',
            'altText' => "แจ้งเตือนนัดหมาย {$remaining} · {$booking->booking_no}",
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'backgroundColor' => $accent,
                    'paddingAll' => '20px',
                    'contents' => [
                        ['type' => 'text', 'text' => 'SM HAIR DESIGN · REMINDER', 'color' => '#FFFFFFB3', 'size' => 'xs', 'weight' => 'bold'],
                        ['type' => 'text', 'text' => 'นัดหมายของคุณใกล้ถึงแล้ว', 'color' => '#FFFFFF', 'size' => 'xl', 'weight' => 'bold', 'margin' => 'md', 'wrap' => true],
                        ['type' => 'text', 'text' => $remaining, 'color' => '#FCA5A5', 'size' => 'sm', 'weight' => 'bold', 'margin' => 'sm'],
                    ],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => [
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'backgroundColor' => '#FFF1F2',
                            'cornerRadius' => '12px',
                            'paddingAll' => '14px',
                            'contents' => [
                                ['type' => 'text', 'text' => $booking->booking_date->format('d/m/Y'), 'size' => 'sm', 'color' => '#991B1B', 'weight' => 'bold'],
                                ['type' => 'text', 'text' => $time, 'size' => 'xxl', 'color' => '#111111', 'weight' => 'bold', 'margin' => 'xs'],
                            ],
                        ],
                        ['type' => 'separator'],
                        [
                            'type' => 'box',
                            'layout' => 'baseline',
                            'contents' => [
                                ['type' => 'text', 'text' => 'บริการ', 'size' => 'sm', 'color' => '#6B7280', 'flex' => 2],
                                ['type' => 'text', 'text' => $booking->service->name, 'size' => 'sm', 'color' => '#111111', 'weight' => 'bold', 'wrap' => true, 'flex' => 5],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'baseline',
                            'contents' => [
                                ['type' => 'text', 'text' => 'สาขา', 'size' => 'sm', 'color' => '#6B7280', 'flex' => 2],
                                ['type' => 'text', 'text' => $booking->branch->name, 'size' => 'sm', 'color' => '#111111', 'wrap' => true, 'flex' => 5],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'baseline',
                            'contents' => [
                                ['type' => 'text', 'text' => 'ช่าง', 'size' => 'sm', 'color' => '#6B7280', 'flex' => 2],
                                ['type' => 'text', 'text' => $booking->employee->name, 'size' => 'sm', 'color' => '#111111', 'wrap' => true, 'flex' => 5],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        ['type' => 'button', 'style' => 'primary', 'color' => '#C1121F', 'height' => 'sm', 'action' => ['type' => 'uri', 'label' => 'โทรหาร้าน', 'uri' => 'tel:0932125164']],
                        ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'uri', 'label' => 'Facebook · SM HAIR DESIGN', 'uri' => 'https://www.facebook.com/share/1BXXqxYu38/?mibextid=wwXIfr']],
                    ],
                ],
            ],
        ];
    }
}
