<?php

namespace App\Services;

use App\Models\BookingPayment;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SlipVerificationService
{
    public function verify(string $qrData, BookingPayment $payment): array
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(config('ghostx.timeout'))
                ->connectTimeout(10)
                ->retry(
                    2,
                    300,
                    fn (\Throwable $exception) => $exception instanceof ConnectionException,
                    throw: false
                )
                ->post(config('ghostx.endpoint'), ['qrData' => $qrData]);
        } catch (\Throwable $exception) {
            Log::warning('GhostX slip verification connection failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'slip' => 'ไม่สามารถเชื่อมต่อระบบตรวจสอบสลิปได้ กรุณาลองใหม่อีกครั้ง',
            ]);
        }

        if (!$response->successful()) {
            Log::warning('GhostX slip verification rejected the request.', [
                'status' => $response->status(),
                'response' => Str::limit($response->body(), 1000),
            ]);

            $apiMessage = trim((string) (
                data_get($response->json(), 'message')
                ?: data_get($response->json(), 'error')
            ));

            throw ValidationException::withMessages([
                'slip' => $apiMessage !== ''
                    ? 'ระบบตรวจสอบสลิปไม่ยอมรับรายการนี้: '.$apiMessage
                    : 'ระบบตรวจสอบสลิปไม่ยอมรับรายการนี้ กรุณาตรวจสอบรูปสลิปแล้วลองใหม่',
            ]);
        }

        $payload = $response->json();
        $transfer = data_get($payload, 'slipVerification.transfer');
        $responseType = strtoupper(trim((string) data_get($payload, 'type', 'UNKNOWN')));
        $acceptedTypes = ['SLIP', 'SLIP_VERIFICATION'];

        if (!in_array($responseType, $acceptedTypes, true) || !is_array($transfer)) {
            $verificationMessage = trim((string) (
                data_get($payload, 'slipVerification.message')
                ?: data_get($payload, 'slipVerification.error')
                ?: data_get($payload, 'message')
                ?: data_get($payload, 'error')
            ));

            Log::warning('GhostX response was not a verified transfer slip.', [
                'type' => $responseType,
                'has_slip_verification' => is_array(data_get($payload, 'slipVerification')),
                'has_transfer' => is_array($transfer),
                'message' => $verificationMessage ?: null,
            ]);

            throw ValidationException::withMessages([
                'slip' => $verificationMessage !== ''
                    ? 'ระบบตรวจสอบสลิปไม่สามารถยืนยันรายการนี้ได้: '.$verificationMessage
                    : 'ระบบตรวจสอบสลิปไม่สามารถยืนยันรายการนี้ได้ (ประเภทผลลัพธ์: '.$responseType.') กรุณาใช้สลิปฉบับใหม่จากแอปธนาคาร',
            ]);
        }

        $amount = (float) data_get($transfer, 'amount.amount', 0);
        $currency = strtoupper((string) data_get($transfer, 'amount.currency.code'));
        if ($currency !== 'THB' || abs($amount - (float) $payment->amount) > 0.009) {
            throw ValidationException::withMessages([
                'slip' => 'ยอดเงินในสลิปไม่ตรงกับยอดที่ต้องชำระ ฿'.number_format((float) $payment->amount, 2),
            ]);
        }

        $transactionRef = trim((string) data_get($transfer, 'transactionRef'));
        if ($transactionRef === '') {
            throw ValidationException::withMessages([
                'slip' => 'ไม่พบหมายเลขอ้างอิงธุรกรรมในสลิป',
            ]);
        }

        if (BookingPayment::where('transaction_ref', $transactionRef)->whereKeyNot($payment->id)->exists()) {
            throw ValidationException::withMessages([
                'slip' => 'สลิปนี้ถูกใช้ยืนยันการจองอื่นแล้ว',
            ]);
        }

        $this->validateReceiver($transfer);

        $transferredAt = null;
        if ($dateTime = data_get($transfer, 'transactionDateTime')) {
            try {
                $transferredAt = Carbon::parse($dateTime);
            } catch (\Throwable) {
                $transferredAt = null;
            }
        }

        return [
            'transaction_ref' => $transactionRef,
            'transferred_at' => $transferredAt,
            'payer_name' => Str::limit((string) data_get($transfer, 'fromAccountName'), 255, ''),
            'from_bank' => Str::limit((string) data_get($transfer, 'fromBankName'), 255, ''),
            'to_bank' => Str::limit((string) data_get($transfer, 'toBankName'), 255, ''),
            'to_account_no' => Str::limit((string) data_get($transfer, 'toAccountNo'), 255, ''),
            'verified_payload' => $payload,
        ];
    }

    private function validateReceiver(array $transfer): void
    {
        $expectedAccount = trim((string) Setting::valueFor('payment_receiver_account'));
        $actualAccount = trim((string) data_get($transfer, 'toAccountNo'));
        $expectedName = Str::lower(trim((string) Setting::valueFor('payment_receiver_name')));
        $expectedDigits = preg_replace('/\D+/', '', $expectedAccount);
        $maskedAccountWithOnlyLastDigitsConfigured = strlen($expectedDigits) <= 4
            && preg_match('/[xX*]/', $actualAccount)
            && $expectedName !== '';

        if ($expectedAccount !== ''
            && !$this->accountMatches($expectedAccount, $actualAccount)
            && !$maskedAccountWithOnlyLastDigitsConfigured) {
            Log::warning('Slip receiver account did not match the shop setting.', [
                'configured_digit_count' => strlen($expectedDigits),
                'configured_last4' => substr($expectedDigits, -4),
                'actual_masked_account' => $actualAccount ?: null,
            ]);

            throw ValidationException::withMessages([
                'slip' => 'บัญชีผู้รับเงินในสลิปไม่ตรงกับบัญชีของร้าน',
            ]);
        }

        $actualName = Str::lower(trim((string) data_get($transfer, 'toAccountName')));
        if ($expectedName && (!$actualName || !Str::contains($actualName, $expectedName))) {
            throw ValidationException::withMessages([
                'slip' => 'ชื่อบัญชีผู้รับเงินในสลิปไม่ตรงกับข้อมูลของร้าน',
            ]);
        }
    }

    private function accountMatches(string $expected, string $actual): bool
    {
        $expectedDigits = preg_replace('/\D+/', '', $expected);
        $actualDigits = preg_replace('/\D+/', '', $actual);

        if ($expectedDigits === '' || $actualDigits === '') {
            return false;
        }

        if (hash_equals($expectedDigits, $actualDigits)) {
            return true;
        }

        // GhostX may return a masked account such as xxx-x-x7190-x.
        if (preg_match('/[xX*]/', $actual)) {
            $actualPattern = strtolower((string) preg_replace('/[^0-9xX*]/', '', $actual));
            $offset = strlen($actualPattern) - strlen($expectedDigits);

            // Align a full account or its last digits against the masked account.
            // Example: expected 1903 aligns with xxx-x-x7190-x and compares 1,9,0.
            if ($offset >= 0) {
                $visibleMatches = 0;
                $hasConflict = false;

                foreach (str_split($expectedDigits) as $index => $digit) {
                    $actualCharacter = $actualPattern[$offset + $index] ?? 'x';
                    if (ctype_digit($actualCharacter)) {
                        $visibleMatches++;
                        if ($actualCharacter !== $digit) {
                            $hasConflict = true;
                            break;
                        }
                    }
                }

                if (!$hasConflict && $visibleMatches >= 3) {
                    return true;
                }
            }

            preg_match_all('/\d{4,}/', $actual, $visibleGroups);

            foreach ($visibleGroups[0] as $visibleDigits) {
                if (str_contains($expectedDigits, $visibleDigits)) {
                    return true;
                }
            }
        }

        // Some banks return only the last 4–6 account digits without mask marks.
        if (strlen($actualDigits) >= 4 && strlen($actualDigits) < strlen($expectedDigits)) {
            return str_ends_with($expectedDigits, $actualDigits);
        }

        return false;
    }
}
