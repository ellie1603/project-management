<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One login, logout, or failed sign-in attempt, for the System Usage report.
 */
class UserSignIn extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'email', 'event', 'ip_address', 'user_agent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * A short, readable device description such as "Chrome on Windows".
     */
    public function device(): string
    {
        $agent = (string) $this->user_agent;

        if ($agent === '') {
            return 'Unknown device';
        }

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Electron') => 'BMPC Desktop',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $system ? "{$browser} on {$system}" : $browser;
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
