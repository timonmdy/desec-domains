<?php

app(\Pterodactyl\BlueprintFramework\Extensions\desec\Services\Reconciler::class)->run(fn (string $line) => $this->line($line));
