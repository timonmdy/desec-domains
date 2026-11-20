<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Seals deSEC tokens with the panel's APP_KEY before they touch the database or the settings table.
 */
final class SecretBox
{
    private const PREFIX = 'enc:';

    public static function seal(string $plain): string
    {
        return $plain === '' ? '' : self::PREFIX . Crypt::encryptString($plain);
    }

    public static function open(string $sealed): string
    {
        if (!str_starts_with($sealed, self::PREFIX)) {
            return '';
        }

        try {
            return Crypt::decryptString(substr($sealed, strlen(self::PREFIX)));
        } catch (DecryptException) {
            return '';
        }
    }
}
