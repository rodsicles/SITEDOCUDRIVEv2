<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\Notification;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetRequestService
{
    /**
     * Hours before a pending request automatically expires.
     */
    public const EXPIRY_HOURS = 24;

    /**
     * Reasons that the public submission can be rejected, returned as codes
     * so the controller can render specific messages.
     */
    public const RESULT_OK = 'ok';
    public const RESULT_NOT_FOUND = 'not_found';
    public const RESULT_INACTIVE = 'inactive';
    public const RESULT_DEAN = 'dean';
    public const RESULT_DUPLICATE = 'duplicate';

    /**
     * Attempt to file a password reset request for the given username.
     *
     * Returns a status code so the controller can show specific feedback.
     */
    public function submitRequest(string $username, ?string $ip = null): array
    {
        $user = User::with('role', 'employee')
            ->where('username', $username)
            ->first();

        if (!$user) {
            DashboardLog::create([
                'user_id'       => null,
                'activity'      => 'Password reset request for unknown username: ' . $username,
                'activity_type' => 'password_reset_request_not_found',
                'visibility'    => 'dean',
                'ip_address'    => $ip,
            ]);

            return ['result' => self::RESULT_NOT_FOUND];
        }

        if ($user->status !== 'Active') {
            DashboardLog::create([
                'user_id'       => $user->id,
                'activity'      => 'Password reset request blocked (inactive account): ' . $user->username,
                'activity_type' => 'password_reset_request_inactive',
                'visibility'    => 'dean',
                'ip_address'    => $ip,
            ]);

            return ['result' => self::RESULT_INACTIVE];
        }

        // The Dean cannot route a reset through themselves — the public form
        // is only for non-Dean roles. (Role 1 = Dean.)
        if ($user->isDean()) {
            DashboardLog::create([
                'user_id'       => $user->id,
                'activity'      => 'Password reset request for Dean username blocked: ' . $user->username,
                'activity_type' => 'password_reset_request_dean_blocked',
                'visibility'    => 'dean',
                'ip_address'    => $ip,
            ]);

            return ['result' => self::RESULT_DEAN];
        }

        $existingPending = PasswordResetRequest::where('user_id', $user->id)
            ->pending()
            ->notExpired()
            ->first();

        if ($existingPending) {
            return ['result' => self::RESULT_DUPLICATE];
        }

        DB::transaction(function () use ($user, $ip) {
            $request = PasswordResetRequest::create([
                'user_id'      => $user->id,
                'status'       => PasswordResetRequest::STATUS_PENDING,
                'expires_at'   => now()->addHours(self::EXPIRY_HOURS),
                'requested_ip' => $ip,
            ]);

            $userName = $user->employee->full_name ?? $user->username;
            $roleLabel = $user->role->role_name ?? 'User';

            // Notify Dean (1) and Secretary (4) — Secretary mirrors Dean
            // access throughout the system.
            $supervisorIds = User::whereIn('role_id', [1, 4])->pluck('id')->toArray();
            $now = now();
            if (!empty($supervisorIds)) {
                Notification::insert(array_map(fn ($deanId) => [
                    'user_id' => $deanId,
                    'message' => "{$userName} ({$roleLabel}) requested a password reset. Review it under Password Reset Requests.",
                    'tone' => Notification::TONE_DANGER,
                    'is_read' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $supervisorIds));
            }

            DashboardLog::create([
                'user_id'       => $user->id,
                'activity'      => 'Password reset request created for ' . $user->username,
                'activity_type' => 'password_reset_request_created',
                'visibility'    => 'dean',
                'ip_address'    => $ip,
            ]);
        });

        return ['result' => self::RESULT_OK];
    }

    /**
     * Approve a pending request: generate a temporary password, force a
     * mandatory rotation on next login, and return the plaintext temp
     * password ONCE so the handler can hand it over in person. The plaintext
     * is never persisted.
     */
    public function approve(PasswordResetRequest $request, User $handler): string
    {
        if ($request->status !== PasswordResetRequest::STATUS_PENDING) {
            throw new \RuntimeException('This request has already been handled.');
        }

        if ($request->isExpired()) {
            $request->update(['status' => PasswordResetRequest::STATUS_EXPIRED]);
            throw new \RuntimeException('This request has expired.');
        }

        $tempPassword = $this->generateTempPassword();

        DB::transaction(function () use ($request, $handler, $tempPassword) {
            $user = $request->user;
            $user->update([
                'password'             => Hash::make($tempPassword),
                'must_change_password' => true,
                'password_changed_at'  => null,
            ]);

            $request->update([
                'status'     => PasswordResetRequest::STATUS_APPROVED,
                'handled_by' => $handler->id,
                'handled_at' => now(),
                'reason'     => null,
            ]);

            $handlerRole = $handler->isSecretary() ? 'Secretary' : 'Dean';
            Notification::create([
                'user_id' => $user->id,
                'message' => "Your password reset request was approved by the {$handlerRole}. Please contact them to receive your temporary password. You will be required to set a new password on first login.",
                'tone'    => Notification::TONE_SUCCESS,
            ]);

            DashboardLog::create([
                'user_id'        => $handler->id,
                'target_user_id' => $user->id,
                'activity'       => 'Approved password reset request for ' . $user->username,
                'activity_type'  => 'password_reset_request_approved',
                'visibility'     => 'dean',
            ]);
        });

        return $tempPassword;
    }

    public function deny(PasswordResetRequest $request, User $handler, ?string $reason = null): void
    {
        if ($request->status !== PasswordResetRequest::STATUS_PENDING) {
            throw new \RuntimeException('This request has already been handled.');
        }

        DB::transaction(function () use ($request, $handler, $reason) {
            $request->update([
                'status'     => PasswordResetRequest::STATUS_DENIED,
                'handled_by' => $handler->id,
                'handled_at' => now(),
                'reason'     => $reason,
            ]);

            $user = $request->user;
            $message = 'Your password reset request was denied.';
            if ($reason) {
                $message .= ' Reason: ' . $reason;
            }
            $message .= ' Please contact the Dean directly if you need further assistance.';

            Notification::create([
                'user_id' => $user->id,
                'message' => $message,
                'tone'    => Notification::TONE_DANGER,
            ]);

            DashboardLog::create([
                'user_id'        => $handler->id,
                'target_user_id' => $user->id,
                'activity'       => 'Denied password reset request for ' . $user->username
                    . ($reason ? ' (reason: ' . $reason . ')' : ''),
                'activity_type'  => 'password_reset_request_denied',
                'visibility'     => 'dean',
            ]);
        });
    }

    /**
     * Mark expired pending requests so they don't pollute the queue.
     */
    public function expireOverdue(): int
    {
        return PasswordResetRequest::pending()
            ->where('expires_at', '<=', now())
            ->update(['status' => PasswordResetRequest::STATUS_EXPIRED]);
    }

    /**
     * Generate a temporary password using an unambiguous alphabet so users
     * don't confuse 0/O, 1/l/I, etc. when typing it in.
     */
    public function generateTempPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
