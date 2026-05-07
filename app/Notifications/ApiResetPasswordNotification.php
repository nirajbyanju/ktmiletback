<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ApiResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Password')
            ->view('emails.reset-password', [
                'user' => $notifiable,
                'url' => $this->resetUrl($notifiable),
                'expiresInMinutes' => (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60),
            ]);
    }

    protected function resetUrl($notifiable): string
    {
        $configuredUrl = config('app.frontend_reset_password_url');
        $baseUrl = is_string($configuredUrl) && $configuredUrl !== ''
            ? rtrim($configuredUrl, '/')
            : rtrim((string) config('app.frontend_url', config('app.url')), '/') . '/reset-password';

        return $baseUrl
            . '?token=' . urlencode($this->token)
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
    }
}
