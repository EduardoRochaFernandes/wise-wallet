<?php
/**
 * WiseWallet 2.0 — minimal mailer.
 *  • driver "log" (default): writes the message to storage/logs/mail.log so the
 *    reset flow is fully testable without an SMTP server.
 *  • driver "smtp": sends via a small fsockopen SMTP client (STARTTLS + AUTH LOGIN).
 * No external dependency (no PHPMailer/Composer).
 */

declare(strict_types=1);

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        // Always journal the message (auditable + dev-friendly).
        @file_put_contents(
            WW_STORAGE . '/logs/mail.log',
            '[' . date('c') . "] To: {$to}\nSubject: {$subject}\n{$body}\n" . str_repeat('-', 60) . "\n",
            FILE_APPEND
        );

        if (strtolower((string) ww_config('MAIL_DRIVER', 'log')) !== 'smtp') {
            return true;
        }
        try {
            return self::smtp($to, $subject, $body);
        } catch (Throwable $e) {
            error_log('SMTP send failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function smtp(string $to, string $subject, string $body): bool
    {
        $host = (string) ww_config('MAIL_HOST', 'localhost');
        $port = (int) ww_config('MAIL_PORT', 587);
        $user = (string) ww_config('MAIL_USER', '');
        $pass = (string) ww_config('MAIL_PASS', '');
        $from = (string) ww_config('MAIL_FROM', 'no-reply@wisewallet.local');
        $enc  = strtolower((string) ww_config('MAIL_ENCRYPTION', 'tls'));

        $fp = stream_socket_client(
            ($enc === 'ssl' ? 'ssl://' : '') . "{$host}:{$port}",
            $errno, $errstr, 10
        );
        if (!$fp) { throw new RuntimeException("connect: $errstr"); }

        $read = function () use ($fp) { return fgets($fp, 512); };
        $cmd = function (string $c) use ($fp, $read) { fputs($fp, $c . "\r\n"); return $read(); };

        $read();
        $cmd('EHLO wisewallet');
        if ($enc === 'tls') {
            $cmd('STARTTLS');
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd('EHLO wisewallet');
        }
        if ($user !== '') {
            $cmd('AUTH LOGIN');
            $cmd(base64_encode($user));
            $cmd(base64_encode($pass));
        }
        $cmd("MAIL FROM:<{$from}>");
        $cmd("RCPT TO:<{$to}>");
        $cmd('DATA');
        $headers = "From: WiseWallet <{$from}>\r\nTo: {$to}\r\nSubject: {$subject}\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\n";
        fputs($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
        $read();
        $cmd('QUIT');
        fclose($fp);
        return true;
    }
}
