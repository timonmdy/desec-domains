<?php

namespace Pterodactyl\BlueprintFramework\Extensions\desec\Admin\Actions;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\BlueprintFramework\Extensions\desec\Dns\TargetTemplate;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\DesecClient;
use Pterodactyl\BlueprintFramework\Extensions\desec\Services\TokenResolver;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\DomainName;
use Pterodactyl\BlueprintFramework\Extensions\desec\Support\Settings;

class SettingsActions
{
    public function __construct(private Settings $settings, private DesecClient $client, private TokenResolver $tokens)
    {
    }

    public function save(Request $request): string
    {
        $request->validate([
            'apiUrl' => ['required', 'url', 'max:200'],
            'srvSubname' => ['required', 'string', $this->rule(fn (string $v) => DomainName::isServiceName(strtolower($v)), 'Enter a service name such as _minecraft._tcp.')],
            'srvTarget' => ['required', 'string', 'max:200', $this->rule(fn (string $v) => TargetTemplate::isValid($v), 'The target must render to a valid host name. Use {subdomain} and {domain}.')],
        ]);

        $input = $request->all();
        $input['srvSubname'] = strtolower(trim((string) $input['srvSubname']));
        $this->settings->save($input);

        return 'Settings saved.';
    }

    /** Lists the domains the default token can see, which proves the token and the API URL work. */
    public function test(): string
    {
        $count = count($this->client->domains($this->tokens->default()));

        return "The deSEC API accepted the token. It can see {$count} domain" . ($count === 1 ? '' : 's') . '.';
    }

    private function rule(Closure $check, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($check, $message): void {
            if (!is_string($value) || !$check($value)) {
                $fail($message);
            }
        };
    }
}
