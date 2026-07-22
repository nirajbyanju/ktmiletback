<?php

namespace App\Notifications;

use App\Models\MessageTemplate;
use App\Services\TemplateMailer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ApiResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        // Branded, admin-editable template (falls back to the legacy view)
        $template = MessageTemplate::byKey('password_reset');

        if ($template && $template->is_enabled) {
            $name = trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? '')) ?: ($notifiable->name ?? $notifiable->email);
            $rendered = app(TemplateMailer::class)->render($template, [
                'StudentName' => $name,
                'ResetLink' => $this->resetUrl($notifiable),
            ]);
            $rendered['view']['ctaUrl'] = $this->resetUrl($notifiable);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->replyTo(config('mail.reply_to.address', 'ktmtestpreparation@ktmeducational.edu.np'))
                ->view('emails.branded', $rendered['view']);
        }

        return (new MailMessage)
            ->subject('Reset Password')
            ->view('emails.reset-password', [
                'user' => $notifiable,
                'url' => $this->resetUrl($notifiable),
                'expiresInMinutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
    }

    protected function resetUrl($notifiable): string
    {
        $configuredUrl = config('app.frontend_reset_password_url');
        $baseUrl = is_string($configuredUrl) && $configuredUrl !== ''
            ? rtrim($configuredUrl, '/')
            : rtrim((string) config('app.frontend_url', config('app.url')), '/').'/reset-password';

        return $baseUrl
            .'?token='.urlencode($this->token)
            .'&email='.urlencode($notifiable->getEmailForPasswordReset());
    }
}
