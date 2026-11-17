<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\ResponseModel;

final class SettingsController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        View::render('settings', [
            'title'  => 'Settings — Mpin',
            'active' => '',
            'user'   => $user,
        ]);
    }

    public function updateProfile(): void
    {
        $user = Auth::current();
        if (!$user) {
            json_err('Please log in.', [], 401);
        }
        $in = array_merge($_POST, json_body());
        $display = trim((string)($in['display_name'] ?? ''));
        $bio = trim((string)($in['bio'] ?? ''));

        $errors = [];
        if ($display === '' || mb_strlen($display) > 60) {
            $errors['display_name'] = 'Display name is required (max 60 characters).';
        }
        if (mb_strlen($bio) > 300) {
            $errors['bio'] = 'Bio must be at most 300 characters.';
        }
        if ($errors) {
            json_err('Please fix the fields.', $errors);
        }
        \App\Core\Database::update('users', [
            'display_name' => $display,
            'bio' => $bio,
        ], 'id = ?', [$user['id']]);
        json_ok(['message' => 'Profile saved.']);
    }

    public function updatePassword(): void
    {
        $user = Auth::current();
        if (!$user) {
            json_err('Please log in.', [], 401);
        }
        $in = array_merge($_POST, json_body());
        $current = (string)($in['current_password'] ?? '');
        $new = (string)($in['new_password'] ?? '');
        $confirm = (string)($in['confirm_password'] ?? '');

        if (!password_verify($current, $user['password_hash'])) {
            json_err('Current password is incorrect.', ['current_password' => 'Incorrect password.']);
        }
        if (strlen($new) < 8) {
            json_err('New password too short.', ['new_password' => 'Password must be at least 8 characters.']);
        }
        if ($new !== $confirm) {
            json_err('Passwords do not match.', ['confirm_password' => 'Passwords do not match.']);
        }
        \App\Core\Database::update('users', [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
            'remember_hash' => null,
        ], 'id = ?', [$user['id']]);
        setcookie('mpinrm', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
        json_ok(['message' => 'Password changed. Other sessions were logged out.']);
    }

    /** Privacy: delete every response (and its location data) of this account. */
    public function deleteMyResponses(): void
    {
        $user = Auth::current();
        if (!$user) {
            json_err('Please log in.', [], 401);
        }
        $confirm = (string)($_POST['confirm'] ?? json_body()['confirm'] ?? '');
        if ($confirm !== 'DELETE') {
            json_err('Type DELETE to confirm.');
        }
        $n = ResponseModel::deleteUserResponses((int)$user['id']);
        json_ok(['message' => "Deleted {$n} of your responses and their location data."]);
    }
}
