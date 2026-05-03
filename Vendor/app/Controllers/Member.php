<?php

namespace App\Controllers;

use App\Libraries\RolesSchema;
use App\Models\RolesModel;
use App\Models\UserModel;
use Config\CiTables;
use Config\Email as EmailConfig;
use Config\Services;

class Member extends BaseController
{
    private function page(string $pageTitle, string $message)
    {
        return view('shared/page', array_merge($this->getSiteLayoutData(), [
            'pageTitle' => $pageTitle,
            'message'   => $message,
        ]));
    }

    private function ensureUsersTable(): bool
    {
        return $this->ensureUsersTableExists();
    }

    private function ensurePasswordResetTable(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $db->prefixTable(CiTables::USER_PASSWORD_RESETS) . '` (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    used_at DATETIME NULL,
                    KEY reset_user_idx (user_id),
                    KEY reset_expires_idx (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ensureDeactivationTokensTable(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $db->prefixTable(CiTables::USER_DEACTIVATION) . '` (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    used_at DATETIME NULL,
                    KEY deact_user_idx (user_id),
                    KEY deact_expires_idx (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isUserActive(array $user): bool
    {
        return ((int) ($user['active'] ?? 1)) === 1;
    }

    /**
     * Match forgot-password token (plain URL segment) to {@see CiTables::USER_PASSWORD_RESETS}.token_hash.
     *
     * @return array<string, mixed>|null
     */
    private function findValidPasswordReset(string $plainToken): ?array
    {
        if ($plainToken === '') {
            return null;
        }

        $db = \Config\Database::connect();

        try {
            $rows = $db->table(CiTables::USER_PASSWORD_RESETS)
                ->where('used_at', null)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->orderBy('id', 'desc')
                ->limit(250)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($rows as $row) {
            $hash = $row['token_hash'] ?? null;
            if (! is_string($hash) || $hash === '') {
                continue;
            }
            if (password_verify($plainToken, $hash)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findValidDeactivationToken(string $plainToken): ?array
    {
        if ($plainToken === '') {
            return null;
        }

        $db = \Config\Database::connect();

        try {
            $rows = $db->table(CiTables::USER_DEACTIVATION)
                ->where('used_at', null)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->orderBy('id', 'desc')
                ->limit(250)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($rows as $row) {
            $hash = $row['token_hash'] ?? null;
            if (! is_string($hash) || $hash === '') {
                continue;
            }
            if (password_verify($plainToken, $hash)) {
                return $row;
            }
        }

        return null;
    }

    /** Fill SMTPPass from .env when BaseConfig missed alternate key names (fixes Gmail 530 without LOGIN). */
    private function mergeSmtpPasswordFromEnv(EmailConfig $cfg): void
    {
        if (trim($cfg->SMTPPass) !== '') {
            return;
        }

        foreach (['email.SMTPPass', 'MAIL_PASSWORD', 'MAIL_PASS', 'SMTP_PASSWORD', 'GMAIL_APP_PASSWORD'] as $key) {
            $v = env($key);
            if (! is_string($v)) {
                continue;
            }
            $t = trim($v, " \t\n\r\0\x0B\"'");
            if ($t !== '') {
                $cfg->SMTPPass = $t;

                return;
            }
        }
    }

    /** @return string|null Error/debug text, or null on success */
    private function sendTransactionalMail(string $to, string $subject, string $plainBody): ?string
    {
        try {
            $mailConfig = config(EmailConfig::class);
            $this->mergeSmtpPasswordFromEnv($mailConfig);
            $fromAddr = trim($mailConfig->fromEmail !== '' ? $mailConfig->fromEmail : $mailConfig->SMTPUser);

            if ($fromAddr === '') {
                return 'Cannot send mail: no sender address. Add email.fromEmail or email.SMTPUser '
                    . '(or MAIL_FROM_ADDRESS / MAIL_USERNAME) in .env. See Vendor/.env.example.';
            }

            if (
                $mailConfig->SMTPHost !== ''
                && trim((string) $mailConfig->SMTPUser) !== ''
                && trim((string) $mailConfig->SMTPPass) === ''
            ) {
                return 'SMTP password is missing: set email.SMTPPass or MAIL_PASSWORD (Gmail: App Password).';
            }

            $emailService = Services::email($mailConfig, false);
            $emailService->clear(true);
            $emailService->setFrom(
                $fromAddr,
                $mailConfig->fromName !== '' ? $mailConfig->fromName : 'Product Store'
            );
            $emailService->setTo($to);
            $emailService->setSubject($subject);
            $emailService->setMessage($plainBody);

            if (! $emailService->send()) {
                return trim((string) $emailService->printDebugger(['headers']));
            }

            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function User_Profile()
    {
        if (! $this->ensureUsersTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database unavailable.');
        }

        $sessionUser = session()->get('member_user');
        if (! is_array($sessionUser) || empty($sessionUser['id'])) {
            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Please sign in to view your profile.');
        }

        $userModel = new UserModel();
        $row       = $userModel->find((int) $sessionUser['id']);

        if ($row === null) {
            session()->remove('member_user');

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Your account was not found. Please sign in again.');
        }

        if (! $this->isUserActive($row)) {
            session()->remove('member_user');

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'This account has been deactivated.');
        }

        session()->set('member_user', $this->buildMemberSessionPayload($row));

        $layout = $this->getSiteLayoutData();

        return view('member/user_profile', array_merge($layout, [
            'pageTitle' => 'Profile',
            'notice'        => session()->getFlashdata('message'),
            'profile'       => [
                'id'           => (int) $row['id'],
                'email'        => (string) $row['email'],
                'display_name' => (string) ($row['display_name'] ?? ''),
                'remote_image' => trim((string) ($row['remote_image'] ?? '')),
                'role_name'    => (new RolesModel())->nameForRoleId((int) ($row['role_id'] ?? 1)),
                'created_at'   => (string) ($row['created_at'] ?? ''),
                'updated_at'   => (string) ($row['updated_at'] ?? ''),
            ],
        ]));
    }

    public function User_Logout()
    {
        session()->remove('member_user');

        return redirect()->to(site_url('Member/User/Login'))->with('message', 'You have been signed out.');
    }

    public function User_Register()
    {
        if (! $this->ensureUsersTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. User registration is unavailable.');
        }

        $layout = $this->getSiteLayoutData();

        if ($this->request->is('post')) {
            $rules = [
                'email'            => 'required|valid_email|max_length[255]',
                'display_name'     => 'permit_empty|max_length[120]',
                'password'         => 'required|min_length[8]|max_length[255]',
                'password_confirm' => 'required|matches[password]',
            ];

            if (! $this->validate($rules)) {
                return view('member/user_register', array_merge($layout, [
                    'pageTitle' => 'Register',
                    'errors'        => $this->validator->getErrors(),
                    'prefill'       => [
                        'email'          => trim((string) $this->request->getPost('email')),
                        'display_name'   => trim((string) $this->request->getPost('display_name')),
                        'remote_image'   => trim((string) $this->request->getPost('remote_image')),
                    ],
                ]));
            }

            $email        = strtolower(trim((string) $this->request->getPost('email')));
            $displayName  = trim((string) $this->request->getPost('display_name'));
            $password     = (string) $this->request->getPost('password');
            [$imgOk, $remoteImage, $imgErr] = $this->sanitizeRemoteProfileImageUrl((string) $this->request->getPost('remote_image'));
            if (! $imgOk) {
                return view('member/user_register', array_merge($layout, [
                    'pageTitle' => 'Register',
                    'errors'        => ['remote_image' => $imgErr ?? 'Invalid profile image URL.'],
                    'prefill'       => [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => trim((string) $this->request->getPost('remote_image')),
                    ],
                ]));
            }

            $userModel = new UserModel();

            if ($userModel->where('email', $email)->first() !== null) {
                return view('member/user_register', array_merge($layout, [
                    'pageTitle' => 'Register',
                    'errors'        => ['email' => 'An account with this email already exists.'],
                    'prefill'       => [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? trim((string) $this->request->getPost('remote_image')),
                    ],
                ]));
            }

            // Default role is always User (slug `user`, seeded id 1).
            RolesSchema::ensure();
            $rolesModel = new RolesModel();
            $userRoleId = $rolesModel->idForSlug(RolesModel::SLUG_USER);
            if ($userRoleId === null) {
                $userRoleId = 1;
            }

            $inserted = $userModel->insert([
                'email'          => $email,
                'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
                'display_name'   => $displayName,
                'remote_image'   => $remoteImage,
                'role_id'        => $userRoleId,
                'active'         => 1,
            ], true);

            if ($inserted === false) {
                return view('member/user_register', array_merge($layout, [
                    'pageTitle' => 'Register',
                    'errors'        => ['database' => 'Could not save your account. Please try again.'],
                    'prefill'       => [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                    ],
                ]));
            }

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Registration successful. You can sign in now.');
        }

        return view('member/user_register', array_merge($layout, [
            'pageTitle' => 'Register',
            'errors'        => [],
            'prefill'       => [],
        ]));
    }

    public function User_Login()
    {
        if (! $this->ensureUsersTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Sign-in is unavailable.');
        }

        $layout = $this->getSiteLayoutData();

        if ($this->request->is('post')) {
            $rules = [
                'email'    => 'required|valid_email|max_length[255]',
                'password' => 'required|max_length[255]',
            ];

            if (! $this->validate($rules)) {
                return view('member/user_login', array_merge($layout, [
                    'pageTitle' => 'Sign in',
                    'notice'        => null,
                    'errors'        => $this->validator->getErrors(),
                    'prefill'       => [
                        'email' => trim((string) $this->request->getPost('email')),
                    ],
                ]));
            }

            $email     = strtolower(trim((string) $this->request->getPost('email')));
            $password  = (string) $this->request->getPost('password');
            $userModel = new UserModel();
            $user      = $userModel->where('email', $email)->first();

            if ($user === null || ! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                return view('member/user_login', array_merge($layout, [
                    'pageTitle' => 'Sign in',
                    'notice'        => null,
                    'errors'        => ['login' => 'Invalid email or password.'],
                    'prefill'       => [
                        'email' => $email,
                    ],
                ]));
            }

            if (! $this->isUserActive($user)) {
                return view('member/user_login', array_merge($layout, [
                    'pageTitle' => 'Sign in',
                    'notice'        => null,
                    'errors'        => ['login' => 'This account has been deactivated.'],
                    'prefill'       => [
                        'email' => $email,
                    ],
                ]));
            }

            session()->set('member_user', $this->buildMemberSessionPayload($user));

            return redirect()->to(site_url('Member/User/Profile'))->with('message', 'You are signed in.');
        }

        return view('member/user_login', array_merge($layout, [
            'pageTitle' => 'Sign in',
            'notice'        => session()->getFlashdata('message'),
            'errors'        => [],
            'prefill'       => [],
        ]));
    }

    public function User_ForgotPassword()
    {
        if (! $this->ensureUsersTable() || ! $this->ensurePasswordResetTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Password reset is unavailable right now.');
        }

        $layout = $this->getSiteLayoutData();

        if ($this->request->is('post')) {
            $rules = [
                'email' => 'required|valid_email|max_length[255]',
            ];

            if (! $this->validate($rules)) {
                return view('member/user_forgot_password', array_merge($layout, [
                    'pageTitle' => 'Forgot password',
                    'errors'        => $this->validator->getErrors(),
                    'notice'        => null,
                    'prefill'       => [
                        'email' => trim((string) $this->request->getPost('email')),
                    ],
                    'resetLink'     => null,
                ]));
            }

            $email     = strtolower(trim((string) $this->request->getPost('email')));
            $userModel = new UserModel();
            $user      = $userModel->where('email', $email)->first();
            $resetLink = null;
            $mailDebug = null;

            if ($user !== null) {
                $db = \Config\Database::connect();
                $db->table(CiTables::USER_PASSWORD_RESETS)
                    ->where('user_id', (int) $user['id'])
                    ->where('used_at', null)
                    ->delete();

                $tokenPlain = bin2hex(random_bytes(24));
                $tokenHash  = password_hash($tokenPlain, PASSWORD_DEFAULT);
                $expiresAt  = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $db->table(CiTables::USER_PASSWORD_RESETS)->insert([
                    'user_id'    => (int) $user['id'],
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                ]);

                $resetLink = site_url('Member/User/Activate/' . $tokenPlain);

                $mailDebug = $this->sendTransactionalMail(
                    $email,
                    'Reset your password',
                    "A password reset was requested for your account.\n\n"
                    . "Use this link within 24 hours:\n"
                    . $resetLink
                    . "\n\nIf you did not request this, you can ignore this email."
                );
            }

            return view('member/user_forgot_password', array_merge($layout, [
                'pageTitle' => 'Forgot password',
                'errors'        => [],
                'notice'        => 'If that email exists, a password reset link has been prepared.',
                'prefill'       => [
                    'email' => $email,
                ],
                'resetLink'     => $resetLink,
                'mailDebug'     => $mailDebug,
            ]));
        }

        return view('member/user_forgot_password', array_merge($layout, [
            'pageTitle' => 'Forgot password',
            'errors'        => [],
            'notice'        => session()->getFlashdata('message'),
            'prefill'       => [],
            'resetLink'     => null,
            'mailDebug'     => null,
        ]));
    }

    /**
     * Signed-in user requests an email with `Member/User/DeActivate/{GUID}` to confirm account deactivation.
     */
    public function User_DeActivateRequest()
    {
        if (! $this->ensureUsersTable() || ! $this->ensureDeactivationTokensTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Account deactivation is unavailable right now.');
        }

        $sessionUser = session()->get('member_user');
        if (! is_array($sessionUser) || empty($sessionUser['id'])) {
            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Sign in to deactivate your account.');
        }

        $userModel = new UserModel();
        $user      = $userModel->find((int) $sessionUser['id']);

        if ($user === null) {
            session()->remove('member_user');

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Your account was not found. Please sign in again.');
        }

        if (! $this->isUserActive($user)) {
            session()->remove('member_user');

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'This account has already been deactivated.');
        }

        $layout = $this->getSiteLayoutData();

        if ($this->request->is('post')) {
            $rules = [
                'password' => 'required|max_length[255]',
            ];

            if (! $this->validate($rules)) {
                return view('member/user_deactivate_request', array_merge($layout, [
                    'pageTitle' => 'Deactivate account',
                    'errors'          => $this->validator->getErrors(),
                    'notice'          => null,
                    'deactivateLink'  => null,
                    'mailDebug'       => null,
                    'accountEmail'    => (string) ($user['email'] ?? ''),
                ]));
            }

            $password = (string) $this->request->getPost('password');
            if (! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                return view('member/user_deactivate_request', array_merge($layout, [
                    'pageTitle' => 'Deactivate account',
                    'errors'          => ['password' => 'Password does not match this account.'],
                    'notice'          => null,
                    'deactivateLink'  => null,
                    'mailDebug'       => null,
                    'accountEmail'    => (string) ($user['email'] ?? ''),
                ]));
            }

            $db = \Config\Database::connect();
            $db->table(CiTables::USER_DEACTIVATION)
                ->where('user_id', (int) $user['id'])
                ->where('used_at', null)
                ->delete();

            $tokenPlain = bin2hex(random_bytes(24));
            $tokenHash  = password_hash($tokenPlain, PASSWORD_DEFAULT);
            $expiresAt  = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $db->table(CiTables::USER_DEACTIVATION)->insert([
                'user_id'    => (int) $user['id'],
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
            ]);

            $deactivateLink = site_url('Member/User/DeActivate/' . $tokenPlain);
            $mailDebug      = $this->sendTransactionalMail(
                (string) $user['email'],
                'Confirm account deactivation',
                "You asked to deactivate your account.\n\n"
                . "Open this link within 24 hours to confirm (the link works only once):\n"
                . $deactivateLink
                . "\n\nIf you did not request this, ignore this email — your account stays active."
            );

            return view('member/user_deactivate_request', array_merge($layout, [
                'pageTitle' => 'Deactivate account',
                'errors'          => [],
                'notice'          => 'If email is configured, we sent a confirmation link to your address.',
                'deactivateLink'  => $deactivateLink,
                'mailDebug'       => $mailDebug,
                'accountEmail'    => (string) ($user['email'] ?? ''),
            ]));
        }

        return view('member/user_deactivate_request', array_merge($layout, [
            'pageTitle' => 'Deactivate account',
            'errors'          => [],
            'notice'          => session()->getFlashdata('message'),
            'deactivateLink'  => null,
            'mailDebug'       => null,
            'accountEmail'    => (string) ($user['email'] ?? ''),
        ]));
    }

    /**
     * Confirm deactivation using GUID from email ({@see CiTables::USER_DEACTIVATION}).
     */
    public function User_DeActivate(string $guid)
    {
        $guid = trim(rawurldecode($guid));

        if (! $this->ensureUsersTable() || ! $this->ensureDeactivationTokensTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Account deactivation is unavailable right now.');
        }

        $layout = $this->getSiteLayoutData();

        $render = static function (array $layoutData, array $viewVars): string {
            return view('member/user_deactivate', array_merge($layoutData, $viewVars));
        };

        $tokenRow = $this->findValidDeactivationToken($guid);
        if ($tokenRow === null) {
            return $render($layout, [
                'pageTitle' => 'Invalid link',
                'tokenValid'    => false,
                'statusMessage' => 'This deactivation link is invalid, expired, or was already used.',
                'accountEmail'  => '',
                'guid'          => '',
            ]);
        }

        $userModel = new UserModel();
        $user      = $userModel->find((int) ($tokenRow['user_id'] ?? 0));

        if ($user === null) {
            return $render($layout, [
                'pageTitle' => 'Account not found',
                'tokenValid'    => false,
                'statusMessage' => 'No account matches this link.',
                'accountEmail'  => '',
                'guid'          => '',
            ]);
        }

        $accountEmail = (string) ($user['email'] ?? '');
        $tokenId      = (int) ($tokenRow['id'] ?? 0);

        if (! $this->isUserActive($user)) {
            return $render($layout, [
                'pageTitle' => 'Already deactivated',
                'tokenValid'    => false,
                'statusMessage' => 'This account is already deactivated.',
                'accountEmail'  => $accountEmail,
                'guid'          => '',
            ]);
        }

        if ($this->request->is('post')) {
            $tokenRow2 = $this->findValidDeactivationToken($guid);
            if ($tokenRow2 === null || (int) ($tokenRow2['id'] ?? 0) !== $tokenId) {
                return $render($layout, [
                    'pageTitle' => 'Link expired',
                    'tokenValid'    => false,
                    'statusMessage' => 'This link was already used or expired.',
                    'accountEmail'  => '',
                    'guid'          => '',
                ]);
            }

            $db = \Config\Database::connect();
            $db->transStart();
            $userModel->update((int) $user['id'], ['active' => 0]);
            $db->table(CiTables::USER_DEACTIVATION)->where('id', $tokenId)->update([
                'used_at' => date('Y-m-d H:i:s'),
            ]);
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $render($layout, [
                    'pageTitle' => 'Deactivate account',
                    'tokenValid'    => true,
                    'statusMessage' => null,
                    'errors'        => ['database' => 'Could not deactivate the account. Please try again.'],
                    'accountEmail'  => $accountEmail,
                    'guid'          => $guid,
                ]);
            }

            $sessionUser = session()->get('member_user');
            if (is_array($sessionUser) && (int) ($sessionUser['id'] ?? 0) === (int) $user['id']) {
                session()->remove('member_user');
            }

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Your account has been deactivated.');
        }

        return $render($layout, [
            'pageTitle' => 'Deactivate account',
            'tokenValid'    => true,
            'statusMessage' => null,
            'errors'        => [],
            'accountEmail'  => $accountEmail,
            'guid'          => $guid,
        ]);
    }

    /**
     * Password reset / account recovery: matches GUID from email to {@see CiTables::USER_PASSWORD_RESETS}, then sets a new password.
     */
    public function User_Activate(string $guid)
    {
        $guid = trim(rawurldecode($guid));

        if (! $this->ensureUsersTable() || ! $this->ensurePasswordResetTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Account activation is unavailable right now.');
        }

        $layout = $this->getSiteLayoutData();

        $render = static function (array $layoutData, array $viewVars): string {
            return view('member/user_activate', array_merge($layoutData, $viewVars));
        };

        $resetRow = $this->findValidPasswordReset($guid);
        if ($resetRow === null) {
            return $render($layout, [
                'pageTitle' => 'Invalid link',
                'tokenValid'    => false,
                'statusMessage' => 'This link is invalid or has expired. Use Forgot password to request a new one.',
                'errors'        => [],
                'accountEmail'  => '',
                'guid'          => '',
            ]);
        }

        $userModel = new UserModel();
        $user      = $userModel->find((int) ($resetRow['user_id'] ?? 0));

        if ($user === null) {
            return $render($layout, [
                'pageTitle' => 'Account not found',
                'tokenValid'    => false,
                'statusMessage' => 'No account matches this reset link.',
                'errors'        => [],
                'accountEmail'  => '',
                'guid'          => '',
            ]);
        }

        $accountEmail = (string) ($user['email'] ?? '');
        $resetId      = (int) ($resetRow['id'] ?? 0);

        if ($this->request->is('post')) {
            $rules = [
                'password'         => 'required|min_length[8]|max_length[255]',
                'password_confirm' => 'required|matches[password]',
            ];

            if (! $this->validate($rules)) {
                return $render($layout, [
                    'pageTitle' => 'Set new password',
                    'tokenValid'    => true,
                    'statusMessage' => null,
                    'errors'        => $this->validator->getErrors(),
                    'accountEmail'  => $accountEmail,
                    'guid'          => $guid,
                ]);
            }

            $resetRow2 = $this->findValidPasswordReset($guid);
            if ($resetRow2 === null || (int) ($resetRow2['id'] ?? 0) !== $resetId) {
                return $render($layout, [
                    'pageTitle' => 'Link expired',
                    'tokenValid'    => false,
                    'statusMessage' => 'This link was already used or has expired. Request a new reset.',
                    'errors'        => [],
                    'accountEmail'  => '',
                    'guid'          => '',
                ]);
            }

            $password = (string) $this->request->getPost('password');
            $db       = \Config\Database::connect();

            $db->transStart();
            $userModel->update((int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $db->table(CiTables::USER_PASSWORD_RESETS)->where('id', $resetId)->update([
                'used_at' => date('Y-m-d H:i:s'),
            ]);
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $render($layout, [
                    'pageTitle' => 'Set new password',
                    'tokenValid'    => true,
                    'statusMessage' => null,
                    'errors'        => ['database' => 'Could not update your password. Please try again.'],
                    'accountEmail'  => $accountEmail,
                    'guid'          => $guid,
                ]);
            }

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Your password was updated. You can sign in now.');
        }

        return $render($layout, [
            'pageTitle' => 'Activate account',
            'tokenValid'    => true,
            'statusMessage' => null,
            'errors'        => [],
            'accountEmail'  => $accountEmail,
            'guid'          => $guid,
        ]);
    }

    public function User_Edit(int $id)
    {
        if (! $this->ensureUsersTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database unavailable.');
        }

        $sessionUser = session()->get('member_user');
        if (! is_array($sessionUser) || empty($sessionUser['id'])) {
            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Please sign in to edit your profile.');
        }

        $sessionId = (int) $sessionUser['id'];
        if ($id !== $sessionId) {
            return redirect()->to(site_url('Member/User/Edit/' . $sessionId))->with('message', 'You can only edit your own profile.');
        }

        $userModel = new UserModel();
        $row       = $userModel->find($id);

        if ($row === null) {
            session()->remove('member_user');

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Your account was not found. Please sign in again.');
        }

        if (! $this->isUserActive($row)) {
            session()->remove('member_user');

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'This account has been deactivated.');
        }

        $layout = $this->getSiteLayoutData();

        $renderForm = function (array $prefill, array $errors) use ($layout, $id): string {
            return view('member/user_edit', array_merge($layout, [
                'pageTitle' => 'Edit profile',
                'userId'        => $id,
                'errors'        => $errors,
                'prefill'       => $prefill,
            ]));
        };

        if ($this->request->is('post')) {
            $rules = [
                'email'              => 'required|valid_email|max_length[255]',
                'display_name'       => 'permit_empty|max_length[120]',
                'current_password'   => 'required|max_length[255]',
            ];

            if (! $this->validate($rules)) {
                return $renderForm([
                    'email'          => trim((string) $this->request->getPost('email')),
                    'display_name'   => trim((string) $this->request->getPost('display_name')),
                    'remote_image'   => trim((string) $this->request->getPost('remote_image')),
                ], $this->validator->getErrors());
            }

            $email        = strtolower(trim((string) $this->request->getPost('email')));
            $displayName  = trim((string) $this->request->getPost('display_name'));
            $currentPass  = (string) $this->request->getPost('current_password');
            $newPass      = (string) $this->request->getPost('password');
            $newConfirm   = (string) $this->request->getPost('password_confirm');
            [$imgOk, $remoteImage, $imgErr] = $this->sanitizeRemoteProfileImageUrl((string) $this->request->getPost('remote_image'));
            if (! $imgOk) {
                return $renderForm([
                    'email'        => $email,
                    'display_name' => $displayName,
                    'remote_image' => trim((string) $this->request->getPost('remote_image')),
                ], ['remote_image' => $imgErr ?? 'Invalid profile image URL.']);
            }

            if (! password_verify($currentPass, (string) ($row['password_hash'] ?? ''))) {
                return $renderForm([
                    'email'        => $email,
                    'display_name' => $displayName,
                    'remote_image' => $remoteImage ?? trim((string) $this->request->getPost('remote_image')),
                ], ['current_password' => 'Current password is incorrect.']);
            }

            $passErrors = [];
            if ($newPass !== '' || $newConfirm !== '') {
                if (mb_strlen($newPass) < 8) {
                    $passErrors['password'] = 'New password must be at least 8 characters.';
                }
                if ($newPass !== $newConfirm) {
                    $passErrors['password_confirm'] = 'Does not match new password.';
                }
            }

            if ($passErrors !== []) {
                return $renderForm([
                    'email'        => $email,
                    'display_name' => $displayName,
                    'remote_image' => $remoteImage ?? '',
                ], $passErrors);
            }

            $duplicate = $userModel->where('email', $email)->where('id !=', $id)->first();
            if ($duplicate !== null) {
                return $renderForm([
                    'email'        => $email,
                    'display_name' => $displayName,
                    'remote_image' => $remoteImage ?? '',
                ], ['email' => 'Another account already uses this email.']);
            }

            $data = [
                'email'          => $email,
                'display_name'   => $displayName,
                // Cleared field becomes '' so the stored URL is always overwritten.
                'remote_image'   => $remoteImage ?? '',
            ];

            if ($newPass !== '') {
                $data['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
            }

            if (! $userModel->update($id, $data)) {
                return $renderForm([
                    'email'        => $email,
                    'display_name' => $displayName,
                    'remote_image' => $remoteImage ?? '',
                ], ['database' => 'Could not save changes. Please try again.']);
            }

            $updatedRow = $userModel->find($id);
            session()->set('member_user', $this->buildMemberSessionPayload(is_array($updatedRow) ? $updatedRow : [
                'id'           => $id,
                'email'        => $email,
                'display_name' => $displayName,
                'remote_image' => $remoteImage ?? '',
            ]));

            return redirect()->to(site_url('Member/User/Profile'))->with('message', 'Your profile was updated.');
        }

        return $renderForm([
            'email'        => (string) ($row['email'] ?? ''),
            'display_name' => (string) ($row['display_name'] ?? ''),
            'remote_image' => trim((string) ($row['remote_image'] ?? '')),
        ], []);
    }
    public function User_Delete(string $guid) { return $this->page('User Delete', 'Delete user GUID: ' . $guid); }

    public function Admin_Profile() { return $this->page('Admin Profile', 'This is the default admin profile page.'); }

    /**
     * Grant administrator role to an existing member: verifies email/password and sets `role_id` to administrator.
     * When no administrator exists yet, the form is open; additional admins require `admin.registerKey` in `.env`
     * posted as `registration_secret`.
     */
    public function Admin_Register()
    {
        if (! $this->ensureUsersTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Administrator registration is unavailable.');
        }

        RolesSchema::ensure();

        $layout = $this->getSiteLayoutData();
        $admins = $this->countAdministrators();
        $formOpen = $this->adminRegistrationFormAllowed();
        $requiresSecret = $admins > 0;

        $render = function (array $extra) use ($layout, $formOpen, $requiresSecret): string {
            return view('member/admin_register', array_merge($layout, [
                'pageTitle' => 'Become administrator',
                'formOpen'         => $formOpen,
                'requiresSecret'   => $requiresSecret,
            ], $extra));
        };

        if (! $formOpen) {
            return $render([
                'errors'   => [],
                'prefill'  => [],
                'notice'   => null,
            ]);
        }

        if (! $this->request->is('post')) {
            return $render([
                'errors'   => [],
                'prefill'  => [],
                'notice'   => session()->getFlashdata('message'),
            ]);
        }

        $rules = [
            'email'    => 'required|valid_email|max_length[255]',
            'password' => 'required|max_length[255]',
        ];

        if ($requiresSecret) {
            $rules['registration_secret'] = 'required|max_length[255]';
        }

        if (! $this->validate($rules)) {
            return $render([
                'errors'  => $this->validator->getErrors(),
                'prefill' => [
                    'email'               => trim((string) $this->request->getPost('email')),
                    'registration_secret' => trim((string) $this->request->getPost('registration_secret')),
                ],
                'notice' => null,
            ]);
        }

        if ($requiresSecret && ! $this->adminRegistrationSecretValid()) {
            return $render([
                'errors'  => ['registration_secret' => 'Registration key is incorrect or missing.'],
                'prefill' => [
                    'email'               => trim((string) $this->request->getPost('email')),
                    'registration_secret' => '',
                ],
                'notice' => null,
            ]);
        }

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        if ($user === null) {
            return $render([
                'errors'  => ['email' => 'No account with this email. Register as a member first, then return here.'],
                'prefill' => [
                    'email'               => $email,
                    'registration_secret' => $requiresSecret ? '' : trim((string) $this->request->getPost('registration_secret')),
                ],
                'notice' => null,
            ]);
        }

        if (! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return $render([
                'errors'  => ['login' => 'Invalid email or password.'],
                'prefill' => [
                    'email'               => $email,
                    'registration_secret' => $requiresSecret ? '' : trim((string) $this->request->getPost('registration_secret')),
                ],
                'notice' => null,
            ]);
        }

        if (! $this->isUserActive($user)) {
            return $render([
                'errors'  => ['login' => 'This account has been deactivated.'],
                'prefill' => [
                    'email'               => $email,
                    'registration_secret' => $requiresSecret ? '' : trim((string) $this->request->getPost('registration_secret')),
                ],
                'notice' => null,
            ]);
        }

        if ($this->accountHasDashboardRole($user)) {
            return $render([
                'errors'  => ['login' => 'This account already has dashboard access (Owner or Administrator).'],
                'prefill' => [
                    'email'               => $email,
                    'registration_secret' => $requiresSecret ? '' : trim((string) $this->request->getPost('registration_secret')),
                ],
                'notice' => null,
            ]);
        }

        $rolesModel  = new RolesModel();
        $adminRoleId = $rolesModel->idForSlug(RolesModel::SLUG_ADMINISTRATOR);
        if ($adminRoleId === null) {
            $adminRoleId = 2;
        }

        if (! $userModel->update((int) $user['id'], ['role_id' => $adminRoleId])) {
            return $render([
                'errors'  => ['database' => 'Could not update the account. Please try again.'],
                'prefill' => [
                    'email'               => $email,
                    'registration_secret' => $requiresSecret ? '' : trim((string) $this->request->getPost('registration_secret')),
                ],
                'notice' => null,
            ]);
        }

        $updatedRow = $userModel->find((int) $user['id']);
        session()->set('member_user', $this->buildMemberSessionPayload(is_array($updatedRow) ? $updatedRow : $user));

        return redirect()->to(site_url('DashBoard/Index'))->with('message', 'Administrator role granted. You are signed in.');
    }

    private function countAdministrators(): int
    {
        RolesSchema::ensure();
        $adminRoleId = (new RolesModel())->idForSlug(RolesModel::SLUG_ADMINISTRATOR);

        if ($adminRoleId === null) {
            return 0;
        }

        return (int) (new UserModel())->where('role_id', $adminRoleId)->countAllResults();
    }

    private function adminRegistrationFormAllowed(): bool
    {
        if ($this->countAdministrators() === 0) {
            return true;
        }

        $key = env('admin.registerKey');

        return is_string($key) && trim($key) !== '';
    }

    private function adminRegistrationSecretValid(): bool
    {
        if ($this->countAdministrators() === 0) {
            return true;
        }

        $expected = env('admin.registerKey');
        if (! is_string($expected) || trim($expected) === '') {
            return false;
        }

        $provided = trim((string) $this->request->getPost('registration_secret'));

        return $provided !== '' && hash_equals($expected, $provided);
    }

    /** Owner or Administrator may use dashboard login and elevated operator flows. */
    private function accountHasDashboardRole(array $user): bool
    {
        RolesSchema::ensure();
        $roleId = (int) ($user['role_id'] ?? 0);
        $slug   = (new RolesModel())->slugForRoleId($roleId);

        return RolesModel::slugMayUseDashboard($slug);
    }

    /**
     * Sign in as administrator (same session as members; role must be Administrator).
     */
    public function Admin_Login()
    {
        if (! $this->ensureUsersTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Administrator sign-in is unavailable.');
        }

        RolesSchema::ensure();

        $sessionMember = session()->get('member_user');
        if (
            is_array($sessionMember)
            && ! empty($sessionMember['id'])
            && RolesModel::slugMayUseDashboard((string) ($sessionMember['role'] ?? ''))
        ) {
            return redirect()->to(site_url('DashBoard/Index'))->with(
                'message',
                'You are already signed in with dashboard access.'
            );
        }

        $layout = $this->getSiteLayoutData();

        if ($this->request->is('post')) {
            $rules = [
                'email'    => 'required|valid_email|max_length[255]',
                'password' => 'required|max_length[255]',
            ];

            if (! $this->validate($rules)) {
                return view('member/admin_login', array_merge($layout, [
                    'pageTitle' => 'Administrator sign in',
                    'notice'        => null,
                    'errors'        => $this->validator->getErrors(),
                    'prefill'       => [
                        'email' => trim((string) $this->request->getPost('email')),
                    ],
                ]));
            }

            $email     = strtolower(trim((string) $this->request->getPost('email')));
            $password  = (string) $this->request->getPost('password');
            $userModel = new UserModel();
            $user      = $userModel->where('email', $email)->first();

            if ($user === null || ! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                return view('member/admin_login', array_merge($layout, [
                    'pageTitle' => 'Administrator sign in',
                    'notice'        => null,
                    'errors'        => ['login' => 'Invalid email or password.'],
                    'prefill'       => [
                        'email' => $email,
                    ],
                ]));
            }

            if (! $this->isUserActive($user)) {
                return view('member/admin_login', array_merge($layout, [
                    'pageTitle' => 'Administrator sign in',
                    'notice'        => null,
                    'errors'        => ['login' => 'This account has been deactivated.'],
                    'prefill'       => [
                        'email' => $email,
                    ],
                ]));
            }

            if (! $this->accountHasDashboardRole($user)) {
                return view('member/admin_login', array_merge($layout, [
                    'pageTitle' => 'Administrator sign in',
                    'notice'        => null,
                    'errors'        => ['login' => 'This account does not have dashboard access (Owner or Administrator required). Use member login for standard accounts.'],
                    'prefill'       => [
                        'email' => $email,
                    ],
                ]));
            }

            session()->set('member_user', $this->buildMemberSessionPayload($user));

            return redirect()->to(site_url('DashBoard/Index'))->with('message', 'Signed in as administrator.');
        }

        return view('member/admin_login', array_merge($layout, [
            'pageTitle' => 'Administrator sign in',
            'notice'        => session()->getFlashdata('message'),
            'errors'        => [],
            'prefill'       => [],
        ]));
    }
    public function Admin_Edit(int $id) { return $this->page('Admin Edit', 'Edit admin ID: ' . $id); }
    public function Admin_Delete(int $id) { return $this->page('Admin Delete', 'Delete admin ID: ' . $id); }
}
