<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Support;

use Illuminate\Support\Facades\Log;

final class DebugLog
{
    public static function write(array $settings, string $event, array $context = []): void
    {
        if (!($settings['debugLogging'] ?? false)) {
            return;
        }

        $message = 'deSEC Domains: ' . $event;
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . ' ' . json_encode($context, JSON_PARTIAL_OUTPUT_ON_ERROR) . PHP_EOL);

            return;
        }

        Log::debug($message, $context);
    }
}
