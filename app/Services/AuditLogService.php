<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'otp',
        'otp_code',
        'secret',
        'webhook_secret',
        'signature',
        'razorpay_signature',
        'account_number',
        'account_number_encrypted',
        'pan',
    ];

    public function log(
        string $action,
        ?User $actor = null,
        ?Model $auditable = null,
        array $metadata = [],
        ?int $providerId = null,
        ?Request $request = null
    ): AuditLog {
        $req = $request ?? request();

        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->user_type,
            'provider_id' => $providerId,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent(),
            'metadata' => $this->redact($metadata),
            'created_at' => now(),
        ]);
    }

    private function redact(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, self::REDACTED_KEYS, true)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }
            $sanitized[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $sanitized;
    }
}
