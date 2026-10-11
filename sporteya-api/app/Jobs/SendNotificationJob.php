<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $userIds;
    public string $type;
    public array $data;
    public ?string $customTitle;
    public ?string $customBody;

    /**
     * Pre-defined Arabic title & body mappings for notification types.
     */
    protected static array $templates = [
        'venue_verified' => [
            'title' => 'تم تفعيل ملعبك',
            'body' => 'تم التحقق من ملعبك! يمكنك الآن استقبال الحجوزات.',
        ],
        'group_invite' => [
            'title' => 'دعوة للانضمام لمجموعة',
            'body' => 'تمت دعوتك للانضمام إلى مجموعة حجز جديدة.',
        ],
        'lobby_invite' => [
            'title' => 'دعوة لمباراة جديدة',
            'body' => 'تمت دعوتك للمشاركة في مباراة جديدة.',
        ],
        'booking_confirmed' => [
            'title' => 'تأكيد الحجز',
            'body' => 'تم تأكيد حجز المباراة بنجاح!',
        ],
        'booking_cancelled' => [
            'title' => 'إلغاء الحجز',
            'body' => 'تم إلغاء حجز المباراة.',
        ],
        'checkin_reminder' => [
            'title' => 'تذكير بموعد المباراة',
            'body' => 'موعد مباراتك يقترب، يرجى التوجه للملعب لتسجيل الحضور عبر QR.',
        ],
        'review_reminder' => [
            'title' => 'تقييم تجربتك',
            'body' => 'كيف كانت مباراتك؟ شاركنا رأيك وقيم الملعب الآن.',
        ],
        'match_result' => [
            'title' => 'نتيجة المباراة',
            'body' => 'تم تسجيل نتيجة مباراتك الأخيرة.',
        ],
        'refund_processed' => [
            'title' => 'استرداد المبلغ',
            'body' => 'تمت معالجة استرداد المبلغ بنجاح إلى وسيلة الدفع الخاصة بك.',
        ],
        'slot_warning' => [
            'title' => 'تنبيه منافسة على الموعد',
            'body' => 'يوجد حجز آخر قيد الاكتمال على نفس الموعد في هذا الملعب.',
        ],
        'player_joined' => [
            'title' => 'انضمام لاعب جديد',
            'body' => 'انضم لاعب جديد إلى غرفتك.',
        ],
        'match_full' => [
            'title' => 'اكتملت الغرفة',
            'body' => 'اكتمل عدد اللاعبين المطلوب، الغرفة جاهزة لإتمام الدفع والحجز.',
        ],
    ];

    /**
     * Create a new job instance.
     *
     * @param string|array $userIds
     */
    public function __construct(
        string|array $userIds,
        string $type,
        array $data = [],
        ?string $customTitle = null,
        ?string $customBody = null
    ) {
        $this->userIds = is_array($userIds) ? array_values(array_unique($userIds)) : [$userIds];
        $this->type = $type;
        $this->data = $data;
        $this->customTitle = $customTitle;
        $this->customBody = $customBody;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $template = self::$templates[$this->type] ?? [
            'title' => 'إشعار جديد',
            'body' => 'لديك إشعار جديد في تطبيق سبورتيا.',
        ];

        $title = $this->customTitle ?: $template['title'];
        $body = $this->customBody ?: $template['body'];

        foreach ($this->userIds as $userId) {
            try {
                $notification = Notification::create([
                    'user_id' => $userId,
                    'type' => $this->type,
                    'title' => $title,
                    'body' => $body,
                    'data' => $this->data,
                    'read_at' => null,
                ]);

                event(new \App\Events\NewNotification($notification));

                // If user has fcm_token or push integration is present, trigger here
            } catch (\Throwable $e) {
                Log::error("Failed to store notification for user {$userId}: " . $e->getMessage());
            }
        }
    }
}
