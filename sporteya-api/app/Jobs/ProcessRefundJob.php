<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\PlayerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $paymentId;

    public function __construct(string $paymentId)
    {
        $this->paymentId = $paymentId;
    }

    public function handle(): void
    {
        $payment = Payment::find($this->paymentId);

        if (!$payment || $payment->status === 'refunded') {
            return;
        }

        // Call Paymob refund API if configured
        if ($payment->paymob_txn_id && config('services.paymob.secret_key')) {
            try {
                // Call Paymob refund endpoint
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.paymob.secret_key'),
                ])->post('https://accept.paymob.com/api/acceptance/void_refund/refund', [
                    'transaction_id' => $payment->paymob_txn_id,
                    'amount_cents' => (int) round($payment->amount * 100),
                ]);
            } catch (\Throwable $e) {
                Log::error("Paymob refund failed for payment {$payment->id}: " . $e->getMessage());
            }
        }

        // Correction L2: atomically update payments and match_participants
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            DB::table('match_participants')
                ->where('lobby_id', $payment->lobby_id)
                ->where('player_id', $payment->player_id)
                ->update(['payment_status' => 'refunded']);
        });

        // Notify player of refund
        $profile = PlayerProfile::find($payment->player_id);
        if ($profile) {
            SendNotificationJob::dispatch(
                $profile->user_id,
                'refund_processed',
                ['amount' => $payment->amount],
                'تم استرداد المبلغ',
                "تمت معالجة استرداد مبلغ {$payment->amount} {$payment->currency} بنجاح إلى حسابك."
            );
        }
    }
}
