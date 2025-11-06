<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Str;

trait HasConnectToken
{
    /* We may want to increase the length of this token in the future to make it more resilient.
     * We'll probably have to grandfather in the 16-character tokens as users can perpetually
     * extend the life of a token by playing at least once every two weeks.
     *
     * Also consider: RetroArch stores this key in a 32-byte buffer, so it may not exceed 31
     * characters, even though the DB field is 60 characters.
     */
    private const CONNECT_TOKEN_LENGTH = 16;

    /* Any time a token is used, its expiry date is pushed back two weeks. This allows the
     * token to persist indefinitely as long as the user remains active.
     */
    private const CONNECT_TOKEN_EXPIRY_DAYS = 14;

    /**
     * Gets whether the Connect API Key is valid.
     */
    public function isConnectTokenValid(): bool
    {
        return !empty($this->appToken) && strlen($this->appToken) === self::CONNECT_TOKEN_LENGTH;
    }

    /**
     * Gets whether the Connect API Key has expired.
     */
    public function isConnectTokenExpired(): bool
    {
        return $this->appTokenExpiry && $this->appTokenExpiry < Carbon::now();
    }

    /**
     * Generates a new Connect API Key
     *
     * NOTE: does not commit the new token. caller must ->save() or ->saveQuietly().
     */
    public function generateNewConnectToken(): void
    {
        do {
            $this->appToken = Str::random(self::CONNECT_TOKEN_LENGTH);
        } while ($this->where('appToken', $this->appToken)->exists());

        $this->extendConnectTokenExpiry();
    }

    /**
     * Updates the Connect API Key's expiration date.
     *
     * NOTE: does not commit the new expiration date. caller must ->save() or ->saveQuietly().
     */
    public function extendConnectTokenExpiry(): void
    {
        $this->appTokenExpiry = Carbon::now()->addDays(self::CONNECT_TOKEN_EXPIRY_DAYS);
    }
}
