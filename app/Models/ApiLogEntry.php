<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLogEntry extends Model
{
    protected $table = 'api_logs';

    public const UPDATED_AT = null; // We only track created_at.

    protected $fillable = [
        'api_version',
        'user_id',
        'endpoint',
        'method',
        'response_code',
        'response_time_ms',
        'response_size_bytes',
        'ip_address',
        'user_agent',
        'request_data',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'created_at' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, ApiLogEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes

    // == helpers

    /**
     * Log a new API request.
     */
    public static function logRequest(
        string $apiVersion,
        ?int $userId,
        string $endpoint,
        string $method,
        int $responseCode,
        ?int $responseTimeMs,
        ?int $responseSizeBytes,
        string $ipAddress,
        ?string $userAgent,
        ?array $requestData = null,
        ?string $errorMessage = null
    ): self {
        return self::create([
            'api_version' => $apiVersion,
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'response_time_ms' => $responseTimeMs,
            'response_size_bytes' => $responseSizeBytes,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_data' => $requestData,
            'error_message' => $errorMessage,
        ]);
    }
}
