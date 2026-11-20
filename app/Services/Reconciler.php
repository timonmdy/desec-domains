<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Services;

use Pterodactyl\Models\Server;
use RuntimeException;
use Pterodactyl\BlueprintFramework\Extensions\desec\Dns\HostAddress;
use Pterodactyl\BlueprintFramework\Extensions\desec\Storage\AllocationStore;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DebugLog;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

/**
 * Keeps deSEC in step with the panel without hooking panel events: a deleted server loses its records,
 * a changed main allocation updates port and address. Costs no deSEC request while nothing changed.
 */
class Reconciler
{
    /** A failed allocation is retried at most this often so a revoked token cannot burn deSEC's daily budget. */
    private const RETRY_SECONDS = 900;

    public function __construct(
        private AllocationStore $allocations,
        private AllocationService $service,
        private Settings $settings,
    ) {
    }

    public function run(callable $line): void
    {
        foreach ($this->allocations->all() as $allocation) {
            $server = Server::query()->with('allocation')->find($allocation['server_id']);

            try {
                if ($server === null) {
                    $this->service->release($allocation);
                    $line("Released {$allocation['host']}: its server no longer exists.");
                } elseif ($this->drifted($allocation, $server)) {
                    $this->service->refresh($allocation, $server);
                    $line("Updated {$allocation['host']}.");
                }
            } catch (DesecException $exception) {
                $this->service->markFailed($allocation['id'], $exception->getMessage());
                $line("Failed {$allocation['host']}: {$exception->getMessage()}");
                DebugLog::write($this->settings->all(), 'Reconcile failed.', ['allocation' => $allocation['id'], 'status' => $exception->status()]);
                if ($exception->isRateLimited()) {
                    $line('deSEC rate limit reached, stopping until the next run.');

                    return;
                }
            } catch (RuntimeException $exception) {
                $this->service->markFailed($allocation['id'], $exception->getMessage());
                $line("Failed {$allocation['host']}: {$exception->getMessage()}");
            }
        }
    }

    private function drifted(array $allocation, Server $server): bool
    {
        if ($allocation['status'] !== 'active') {
            return time() - $allocation['updated_at'] >= self::RETRY_SECONDS;
        }

        $hasAddress = $allocation['address'] !== null;

        return (int) ($server->allocation?->port ?? 0) !== $allocation['port']
            || ($hasAddress && HostAddress::publicIp($server) !== $allocation['address']);
    }
}
