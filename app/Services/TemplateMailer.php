<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends automated emails from database templates.
 *  - Placeholders like [StudentName] are replaced with real data
 *  - Wrapped in the branded layout (emails/branded)
 *  - From: noreply@…  Reply-To: the real mailbox
 *  - Every attempt is logged (email_logs); duplicate sends can be prevented
 *  - NEVER throws — an email failure must not break the website action
 */
class TemplateMailer
{
    /**
     * @param  string  $key  template key, e.g. 'payment_verified_course'
     * @param  string  $to  recipient email
     * @param  array  $data  placeholder values ['StudentName' => '…']
     * @param  array  $opts  ['user_id' => ?, 'related' => ['invoice', 12],
     *                       'attachments' => [['data'=>bytes,'name'=>'x.pdf','mime'=>'application/pdf']]]
     *                       'related' enables duplicate protection:
     *                       same template+related never sends twice.
     */
    public function send(string $key, string $to, array $data = [], array $opts = []): void
    {
        try {
            if (! $to) {
                return;
            }

            $template = MessageTemplate::byKey($key);
            if (! $template || $template->category !== 'email_auto' || ! $template->is_enabled) {
                return;
            }

            [$relatedType, $relatedId] = ($opts['related'] ?? null) ? $opts['related'] : [null, null];

            // Duplicate-send protection
            if ($relatedType && EmailLog::where('template_key', $key)
                ->where('related_type', $relatedType)
                ->where('related_id', $relatedId)
                ->where('status', 'sent')
                ->exists()) {
                return;
            }

            $rendered = $this->render($template, $data);
            $html = view('emails.branded', $rendered['view'])->render();

            $attachments = $opts['attachments'] ?? [];

            Mail::send('emails.branded', $rendered['view'], function ($message) use ($to, $rendered, $attachments) {
                $message->to($to)
                    ->subject($rendered['subject'])
                    ->replyTo(config('mail.reply_to.address', 'ktmtestpreparation@ktmeducational.edu.np'));

                foreach ($attachments as $attachment) {
                    $message->attachData(
                        $attachment['data'],
                        $attachment['name'],
                        ['mime' => $attachment['mime'] ?? 'application/octet-stream']
                    );
                }
            });

            EmailLog::create([
                'user_id' => $opts['user_id'] ?? null,
                'recipient' => $to,
                'template_key' => $key,
                'subject' => $rendered['subject'],
                'body_html' => $html,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            Log::warning("TemplateMailer [{$key}] to {$to} failed: ".$e->getMessage());
            try {
                EmailLog::create([
                    'user_id' => $opts['user_id'] ?? null,
                    'recipient' => $to,
                    'template_key' => $key,
                    'related_type' => $opts['related'][0] ?? null,
                    'related_id' => $opts['related'][1] ?? null,
                    'status' => 'failed',
                    'error' => mb_substr($e->getMessage(), 0, 1000),
                ]);
            } catch (\Throwable) {
                // logging must never break the caller
            }
        }
    }

    /** Convenience: derive recipient + name placeholders from a User. */
    public function sendToUser(string $key, ?User $user, array $data = [], array $opts = []): void
    {
        if (! $user || ! $user->email) {
            return;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? $user->email);
        $first = $user->first_name ?: explode(' ', $name)[0];

        $this->send($key, $user->email, [
            'StudentName' => $name,
            'FirstName' => $first,
            ...$data,
        ], ['user_id' => $user->id, ...$opts]);
    }

    /** Render a template with data (also used by admin Preview/Test). */
    public function render(MessageTemplate $template, array $data): array
    {
        $fill = function (?string $text) use ($data): string {
            if ($text === null) {
                return '';
            }
            foreach ($data as $k => $v) {
                $text = str_replace('['.$k.']', (string) ($v ?? ''), $text);
            }
            // Unfilled placeholders disappear rather than reaching students
            $text = preg_replace('/\[[A-Za-z][A-Za-z0-9]*\]/', '', $text);
            // Drop now-empty "Label:" lines left behind by removed placeholders
            $text = preg_replace('/^[^\S\n]*[A-Za-z][^:\n]{0,40}:[ \t]*$\n?/m', '', $text);

            return trim($text);
        };

        $siteUrl = rtrim((string) config('app.frontend_url', 'https://www.ktmtestpreparation.com'), '/');

        return [
            'subject' => $fill($template->subject) ?: $template->name,
            'view' => [
                'bodyText' => $fill($template->body),
                'ctaText' => $template->cta_text,
                'ctaUrl' => $template->cta_path ? $siteUrl.$template->cta_path : $siteUrl,
                'siteUrl' => $siteUrl,
            ],
        ];
    }
}
