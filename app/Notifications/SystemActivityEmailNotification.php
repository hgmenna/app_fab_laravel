<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemActivityEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title.' | Federación Argentina de Billar')
            ->greeting('Hola '.$notifiable->name)
            ->line($this->message)
            ->line('Este correo fue generado automáticamente por el sistema administrativo de la Federación Argentina de Billar.');
    }
}
