<?php

namespace App\Jobs;

use App\Events\LobbyStatusChanged;
use App\Models\MatchLobby;
use App\Models\Payment;
use App\Models\PlayerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LobbyExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lock = Cache::lock('lobby_expiry_job', 900); // 15-min TTL
        if (!$lock->get()) {
            Log::info('LobbyExpiryJob skipped: lock already held');
            return;
        }

        try {
            MatchLobby::whereIn('status', ['forming', 'payment_pending'])
                ->where('expires_at', '<', now())
                ->where('match_date', '>=', today())
                ->chunkById(50, function ($lobbies) {
                    foreach ($lobbies as $lobby) {
                        $lobbyLock = Cache::lock("lobby_expiry_{$lobby->id}", 60);
                        if (!$lobbyLock->get()) continue;

                        try {
                            DB::transaction(function () use ($lobby) {
                                $fresh = MatchLobby::lockForUpdate()->find($lobby->id);
                                if (!in_array($fresh->status, ['forming', 'payment_pending'])) {
                                    return; // already processed
                                }

                                $fresh->update([
                                    'status' => 'cancelled',
                                    'cancellation_reason' => 'expired',
                                    'cancelled_at' => now(),
                                ]);

                                $paidPayments = Payment::where('lobby_id', $fresh->id)
                                    ->where('status', 'completed')
                                    ->get();

                                foreach ($paidPayments as $payment) {
                                    ProcessRefundJob::dispatch($payment->id);
                                    $p = PlayerProfile::find($payment->player_id);
                                    if ($p) {
                                        SendNotificationJob::dispatch($p->user_id, 'booking_cancelled');
                                    }
                                }

                                $c = PlayerProfile::find($fresh->creator_id);
                                if ($c) {
                                    SendNotificationJob::dispatch($c->user_id, 'booking_cancelled');
                                }

                                event(new LobbyStatusChanged($fresh->fresh()));
                            });
                        } finally {
                            $lobbyLock->release();
                        }
                    }
                });
        } finally {
            $lock->release();
        }
    }
}
