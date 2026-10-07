<?php

declare(strict_types=1);

namespace Omnest\Mail;

/** Transactional email copy. Voice: plain, calm, friendly (docs/specs.md §1). */
final class Emails
{
    public static function verifyEmail(string $to, string $name, string $url): Message
    {
        return self::build(
            $to,
            'Confirm your email for Omnest',
            "Hi $name,",
            'Tap the button to confirm this is your email. The link works for 24 hours.',
            'Confirm email',
            $url,
            "If you didn't create an Omnest account, you can ignore this email.",
        );
    }

    public static function resetPassword(string $to, string $name, string $url): Message
    {
        return self::build(
            $to,
            'Reset your Omnest password',
            "Hi $name,",
            'Someone asked to reset the password for your Omnest account. Tap the button to choose a new one. The link works for 1 hour.',
            'Choose a new password',
            $url,
            "If this wasn't you, ignore this email. Your password stays the same.",
        );
    }

    private static function build(
        string $to,
        string $subject,
        string $greeting,
        string $body,
        string $cta,
        string $url,
        string $footer,
    ): Message {
        $text = "$greeting\n\n$body\n\n$cta: $url\n\n$footer\n\nOmnest";

        $e = static fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
            <!doctype html>
            <html><body style="margin:0;background:#FBF6EA;font-family:Arial,Helvetica,sans-serif;color:#16211B">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px">
                <table role="presentation" width="100%" style="max-width:480px;background:#FFFDF7;border:2px solid #16211B;border-radius:20px">
                  <tr><td style="padding:24px">
                    <p style="margin:0 0 16px;font-size:20px;font-weight:bold;color:#1F5B3A">Omnest</p>
                    <p style="margin:0 0 12px;font-size:16px;line-height:1.6">{$e($greeting)}</p>
                    <p style="margin:0 0 24px;font-size:16px;line-height:1.6">{$e($body)}</p>
                    <a href="{$e($url)}" style="display:inline-block;background:#1F5B3A;color:#FFFDF7;text-decoration:none;font-weight:bold;font-size:14px;padding:14px 20px;border:2px solid #16211B;border-radius:12px">{$e($cta)}</a>
                    <p style="margin:24px 0 0;font-size:12px;line-height:1.4;color:#6B7A70">{$e($footer)}</p>
                  </td></tr>
                </table>
              </td></tr></table>
            </body></html>
            HTML;

        return new Message($to, $subject, $text, $html);
    }
}
