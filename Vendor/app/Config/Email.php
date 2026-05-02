<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Config\DotEnv;

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'mail';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = '';

    /**
     * Which SMTP authentication method to use: login, plain
     */
    public string $SMTPAuthMethod = 'login';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 25;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'text';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    public function __construct()
    {
        parent::__construct();

        $this->loadWorkspaceDotEnvIfNestedVendor();

        $customMailPath = $this->resolveMailPathFromEnv();
        if ($customMailPath !== '') {
            $this->mailPath = $customMailPath;
            $this->applyPhpSendmailPathIni($customMailPath);
        }

        $this->SMTPHost = $this->firstEnvString($this->SMTPHost, [
            'email.SMTPHost',
            'MAIL_HOST',
            'SMTP_HOST',
        ]);
        $this->SMTPUser = $this->firstEnvString($this->SMTPUser, [
            'email.SMTPUser',
            'MAIL_USERNAME',
            'SMTP_USER',
            'SMTP_USERNAME',
        ]);
        $this->SMTPPass = $this->firstEnvString($this->SMTPPass, [
            'email.SMTPPass',
            'MAIL_PASSWORD',
            'MAIL_PASS',
            'SMTP_PASS',
            'SMTP_PASSWORD',
            'GMAIL_APP_PASSWORD',
        ]);

        $port = $this->firstEnvString('', ['email.SMTPPort', 'MAIL_PORT']);
        if ($port !== '') {
            $this->SMTPPort = (int) $port;
        }

        $crypto = env('email.SMTPCrypto');
        if (is_string($crypto) && $crypto !== '') {
            $this->SMTPCrypto = $crypto;
        }
        $mailEnc = env('MAIL_ENCRYPTION');
        if ($mailEnc !== null && $mailEnc !== '' && strtolower((string) $mailEnc) !== 'null') {
            $this->SMTPCrypto = strtolower((string) $mailEnc) === 'ssl' ? 'ssl' : 'tls';
        }

        $proto = env('email.protocol');
        if (is_string($proto) && $proto !== '') {
            $this->protocol = $proto;
        }
        $mailer = env('MAIL_MAILER');
        if ($mailer !== null && strtolower((string) $mailer) === 'smtp') {
            $this->protocol = 'smtp';
        }

        $this->fromEmail = $this->firstEnvString($this->fromEmail, [
            'email.fromEmail',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM',
            'SMTP_FROM',
            'FROM_EMAIL',
        ]);
        $this->fromName = $this->firstEnvString($this->fromName, [
            'email.fromName',
            'MAIL_FROM_NAME',
        ]);

        if ($this->fromEmail === '') {
            $iniFrom = ini_get('sendmail_from');
            if (is_string($iniFrom) && filter_var(trim($iniFrom), FILTER_VALIDATE_EMAIL)) {
                $this->fromEmail = trim($iniFrom);
            }
        }

        // "From" is mandatory for CodeIgniter\Email — match SMTP login when possible.
        if ($this->fromEmail === '' && $this->SMTPUser !== '') {
            $this->fromEmail = $this->SMTPUser;
        }

        if ($this->fromName === '') {
            $this->fromName = 'Product Store';
        }

        $this->ensureProviderSmtpDefaults();

        // PHP mail() is usually unavailable on Windows/local dev; use SMTP when host is known.
        if ($this->SMTPHost !== '' && $this->protocol === 'mail') {
            $this->protocol = 'smtp';
        }
    }

    /**
     * If only the mailbox login/from address is set, infer common SMTP endpoints so CI does not fall back to mail().
     */
    private function ensureProviderSmtpDefaults(): void
    {
        if ($this->SMTPHost !== '') {
            return;
        }

        $user = $this->SMTPUser !== '' ? $this->SMTPUser : $this->fromEmail;
        if ($user === '') {
            return;
        }

        $domain = strtolower(substr(strrchr($user, '@') ?: '', 1));

        // Gmail KB: smtp.gmail.com; auth required; 587 + STARTTLS → CI `tls`; 465 implicit SSL → `ssl`.
        if ($domain === 'gmail.com') {
            $this->SMTPHost = 'smtp.gmail.com';
            if ($this->SMTPPort === 25) {
                $this->SMTPPort = 587;
            }
            if ($this->SMTPCrypto === '') {
                $this->SMTPCrypto = $this->SMTPPort === 465 ? 'ssl' : 'tls';
            }

            return;
        }

        if (in_array($domain, ['outlook.com', 'hotmail.com', 'live.com'], true)) {
            $this->SMTPHost = 'smtp-mail.outlook.com';
            if ($this->SMTPPort === 25) {
                $this->SMTPPort = 587;
            }
            if ($this->SMTPCrypto === '') {
                $this->SMTPCrypto = 'tls';
            }
        }
    }

    /**
     * When the CI project root folder is named `Vendor`, load `.env` one level above
     * so mail vars can live beside `Vendor/` without relying on early bootstrap paths.
     */
    private function loadWorkspaceDotEnvIfNestedVendor(): void
    {
        if (! defined('ROOTPATH') || ! defined('SYSTEMPATH')) {
            return;
        }

        $root = rtrim((string) ROOTPATH, '\\/ ');
        if ($root === '' || basename($root) !== 'Vendor') {
            return;
        }

        $workspace = dirname($root) . DIRECTORY_SEPARATOR;
        $file      = $workspace . '.env';
        if (! is_file($file) || ! is_readable($file)) {
            return;
        }

        require_once SYSTEMPATH . 'Config/DotEnv.php';
        (new DotEnv($workspace))->load();
    }

    /** @param list<string> $keys */
    private function firstEnvString(string $current, array $keys): string
    {
        if ($current !== '') {
            return $current;
        }

        foreach ($keys as $key) {
            $v = env($key);
            if (! is_string($v)) {
                continue;
            }
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    /**
     * Sendmail executable for protocol `sendmail`, or used by PHP `mail()` via sendmail_path (see applyPhpSendmailPathIni).
     * Env wins over the default `/usr/sbin/sendmail` so Windows/XAMPP paths do not need php.ini edits for CI mail config.
     */
    private function resolveMailPathFromEnv(): string
    {
        foreach ([
            'email.mailPath',
            'MAIL_SENDMAIL_PATH',
            'SENDMAIL_PATH',
            'CURRENT_MAIL_PATH',
            'CURRENT_MAIL_EXE_PATH',
        ] as $key) {
            $v = env($key);
            if (! is_string($v)) {
                continue;
            }
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    /**
     * Align PHP's mail() transport with the same Sendmail-compatible binary (e.g. XAMPP sendmail.exe).
     * Format matches php.ini: quoted path when needed, plus `-t` as in typical XAMPP examples.
     */
    private function applyPhpSendmailPathIni(string $executablePath): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = '"' . str_replace('"', '\"', $executablePath) . '" -t';
        } elseif (strpbrk($executablePath, " \t\"") !== false) {
            $cmd = '"' . str_replace('"', '\\"', $executablePath) . '" -t';
        } else {
            $cmd = $executablePath . ' -t';
        }

        @ini_set('sendmail_path', $cmd);
    }
}
