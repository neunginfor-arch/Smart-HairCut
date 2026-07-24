<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\LineMessagingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request, LineMessagingService $line): Response
    {
        $body = $request->getContent();

        if (!$line->hasValidSignature($body, $request->header('x-line-signature'))) {
            return response('Invalid LINE signature.', 400);
        }

        foreach ($request->input('events', []) as $event) {
            $userId = data_get($event, 'source.userId');
            $replyToken = data_get($event, 'replyToken');

            if (!$userId || !$replyToken) {
                continue;
            }

            if (data_get($event, 'type') === 'follow') {
                $line->reply($replyToken, 'ยินดีต้อนรับสู่ SM HAIR DESIGN กรุณาส่งเบอร์โทรศัพท์ที่ใช้สมัครสมาชิก เพื่อรับการแจ้งเตือนคิว');
                continue;
            }

            if (data_get($event, 'type') !== 'message' || data_get($event, 'message.type') !== 'text') {
                continue;
            }

            $phone = preg_replace('/\D+/', '', (string) data_get($event, 'message.text'));
            $member = Member::query()
                ->get()
                ->first(fn (Member $item) => preg_replace('/\D+/', '', $item->phone) === $phone);

            if (!$member || strlen($phone) < 9) {
                $line->reply($replyToken, 'ไม่พบข้อมูลสมาชิก กรุณาส่งเบอร์โทรศัพท์ที่ใช้สมัครสมาชิกอีกครั้ง');
                continue;
            }

            Member::where('line_user_id', $userId)
                ->where('id', '!=', $member->id)
                ->update(['line_user_id' => null]);

            $member->update(['line_user_id' => $userId]);
            $line->reply($replyToken, 'เชื่อม LINE สำเร็จแล้ว คุณจะได้รับการแจ้งเตือนเมื่อมีการจองคิว');
        }

        return response()->noContent();
    }
}
