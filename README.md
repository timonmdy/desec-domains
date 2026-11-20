# deSEC Domains

Connect [deSEC](https://desec.io) domains to your Pterodactyl panel and point them at your game servers.

- **Extension settings** hold the deSEC API token and the SRV defaults. They rarely need changes.
- **Domains** is the administrator page for connecting domains and managing their records.
- A **Domains tab** on every server allocates a whole domain or a subdomain to that server.
- Server users see their address on the **console page**.

## Install or update

1. Install Blueprint for your Pterodactyl instance.
2. Download `desec.blueprint` and copy it to your Pterodactyl panel folder.
3. Run:

   ```bash
   cd /var/www/pterodactyl
   blueprint -install desec
   ```

Run the same command to update an existing installation.

## First steps

1. Create an API token in your deSEC account (**Token Management**).
2. Open **Admin → Extensions → deSEC Domains**, paste the token under **deSEC API**, save, and press **Test the stored token**.
3. Open **Domains** and connect a domain that already exists in your deSEC account.
4. Open a server in the admin area, choose the **Domains** tab, and allocate a subdomain or the whole domain.

The extension adds the **Domains** entry to the admin sidebar and the **Domains** tab to the admin server pages. The server's native tabs are also shown on the Domains page, with **Domains** marked active. A small script in the admin layout adds the tab to the server navigation.

## What an allocation creates

| Allocation | SRV records | Players connect with |
| --- | --- | --- |
| Subdomain `survival` on `example.com` | `_minecraft._tcp.survival` | `survival.example.com` |
| Whole domain `example.com` | `_minecraft._tcp` and, optionally, a wildcard `*` | `example.com` and `anything.example.com` |

Every record uses the defaults from the extension settings: priority `0`, weight `0`, the server's main allocation port and the target `[subdomain.]domain`.

An SRV target must resolve to an address. If the target lies inside the connected domain and has no A or AAAA record yet, the extension creates one from the allocation IP and removes it again on release. **Existing records are never modified or deleted.** If an existing address record points elsewhere, the allocation shows a note.

A background job keeps deSEC in step with the panel every five minutes: it updates the port or address when a server's main allocation changes and removes the records of deleted servers. It makes no deSEC request while nothing changed.

## Behavior notes

- **Wildcard SRV records answer every service name** under the domain that has no record of its own, not only `_minecraft._tcp`. Do not use the wildcard on a domain that also serves mail, Matrix, XMPP or similar services.
- **One token or one per domain.** The default token covers every domain. A domain can have its own token instead, which is useful with deSEC's scoped tokens. See [docs/DESEC.md](docs/DESEC.md).
- **deSEC rate limits.** deSEC allows 2000 requests per day per account and limits changes per domain. Console pages read only the panel database, and reads in the admin area are cached.
- **The admin pages live under the extension page** (`/admin/extensions/desec`). Blueprint does not allow extensions to register routes such as `/admin/domains`.
- Detailed debug logging is in the extension's **General** settings. Scheduled job output goes to the scheduler's stderr; web requests use the Laravel log. Tokens are never logged.
- The panel scheduler (`php artisan schedule:run` every minute) must be running for the reconcile job.

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [Coding guidelines](docs/CODING_GUIDELINES.md)
- [deSEC notes: tokens, rate limits and DNS behavior](docs/DESEC.md)
- [Security policy](SECURITY.md)

## Development

```bash
npm install
npm run build
```

The build type-checks the dashboard and creates `dist/desec.blueprint`. Pull requests run the same typecheck and packaging build.

For live development, mirror the repository into a panel with `PANEL_DIR=/var/www/pterodactyl npm run dev:sync`, then run `blueprint -build` in the panel directory.
