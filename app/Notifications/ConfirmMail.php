<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmMail extends Notification
{
        use Queueable;

        public $user;

        /**
         * Create a new notification instance.
         */
        public function __construct($user)
        {
                $this->user = $user;
        }

        /**
         * Get the notification's delivery channels.
         */
        public function via(object $notifiable): array
        {
                return ['mail'];
        }

        /**
         * Get the mail representation of the notification.
         */
        public function toMail(object $notifiable): MailMessage
        {
                //🔗 Verification URL (token based)
                $url = url('/api/auth/verify-email/' . $this->user->mail_token);
                return (new MailMessage)
                        ->subject('Confirm your email address')
                        ->greeting('Hello ' . $this->user->name . ' 👋')
                        ->line('Thanks for signing up.')
                        ->line('Please click the button below to verify your email address.')
                        ->action('Verify Email', $url)
                        ->line('If you did not create an account, no further action is required.');
        }

        /**
         * Get the array representation of the notification.
         */
        public function toArray(object $notifiable): array
        {
                return [];
        }
}