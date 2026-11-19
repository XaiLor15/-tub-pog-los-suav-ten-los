<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Lao email with the one-time password reset link. */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
        $minutes = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('ຕັ້ງລະຫັດຜ່ານໃໝ່ — Kwv Tij Hmoob Xyooj')
            ->greeting('ສະບາຍດີ '.$notifiable->display_name)
            ->line('ພວກເຮົາໄດ້ຮັບຄຳຂໍຕັ້ງລະຫັດຜ່ານໃໝ່ສຳລັບບັນຊີຂອງທ່ານ.')
            ->action('ຕັ້ງລະຫັດຜ່ານໃໝ່', $url)
            ->line('ລິ້ງນີ້ໃຊ້ໄດ້ພາຍໃນ '.$minutes.' ນາທີ ແລະ ໃຊ້ໄດ້ຄັ້ງດຽວ.')
            ->line('ຖ້າທ່ານບໍ່ໄດ້ຂໍ ບໍ່ຕ້ອງເຮັດຫຍັງ — ລະຫັດຜ່ານເດີມຍັງໃຊ້ໄດ້.')
            ->salutation('Kwv Tij Hmoob Xyooj');
    }
}
