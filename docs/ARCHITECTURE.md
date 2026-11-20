# Architecture

## Where things live

```text
admin/        Blueprint admin entry: controller, layout view, wrapper script, Blade partials
app/
  Admin/      Page presenters (one per view) and action handlers (one per area)
  Dns/        Pure DNS logic. No I/O, no framework state
  Http/       Client API controllers
  Services/   Everything with side effects: deSEC client, allocation lifecycle, reconcile
  Storage/    One class per table, returns plain arrays
  Support/    Settings, schema, secrets, name validation, debug log
console/      Scheduled artisan command (reconcile)
dashboard/    React components for the server console page
migrations/   Database tables
routers/      Client API routes
```

## Data flow

1. An administrator submits a form on `/admin/extensions/desec`. `ActionRouter` maps the `action` field to one handler method.
2. The handler validates input and calls a service. Handlers do not talk to deSEC or the database.
3. `AllocationService` asks `AllocationPlan` which RRsets must exist, checks for existing address records, creates everything in **one bulk request** (atomic at deSEC), and stores which RRsets it owns.
4. Releasing deletes only the RRsets recorded as owned.
5. `Reconciler` runs every five minutes, compares the stored port and address with the server's main allocation, and pushes changes.
6. The console card calls `GET /api/client/extensions/desec/servers/{server}/domains`. That endpoint reads only `desec_allocations`.

## Tables

- `desec_domains`: connected zones and an optional sealed per-domain token.
- `desec_allocations`: one row per allocated (sub)domain with the target, port, the RRsets it owns, notes and a status.

## Security decisions

- Admin pages and actions require a root administrator in the controller itself, on top of Blueprint's routing.
- Tokens are sealed with the panel `APP_KEY`, are write-only in the UI, and never reach a view, an API response or a log.
- The client API exposes only the address, the kind, a wildcard flag and a status.
- Records created by an allocation are read-only in the record editor.
