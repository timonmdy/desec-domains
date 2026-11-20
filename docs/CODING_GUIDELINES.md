# Coding guidelines

These rules are derived from the MC Panels code base, which this project follows. Replace this page with the MC Panels wiki page if it is more current.

## General

- Keep files small and single-purpose. Most files are under 150 lines; split before a file grows past about 200.
- One class or component per file. File names match the exported name.
- Prefer plain data (arrays, typed objects) over clever abstractions. No dead code and no speculative options.
- Comments explain **why**, not what. Keep them rare.

## PHP

- Namespace `Pterodactyl\BlueprintFramework\Extensions\desec\...`. Admin controller only in `admin/controller.php` with the `{identifier}` placeholders.
- Controllers are thin: authorize, validate, call a service, return. Nothing else.
- Layers: `Admin` (presentation and action routing), `Dns` (pure logic), `Services` (side effects), `Storage` (one class per table, returns arrays), `Support` (shared helpers).
- Settings are schema-driven (`SettingsSchema`). A new setting is one schema entry; the admin form renders it automatically.
- Constructor injection for dependencies, `private` by default, `final` for pure helper classes.
- Fail with exceptions that carry a message an administrator can act on. Never swallow errors silently.
- Security-relevant changes are logged with `Log::notice` (who, what), never with secrets.
- Secrets are sealed with `SecretBox`, write-only in forms, and never returned from an endpoint.
- Migrations are date-prefixed, drop what they create, and use plain unsigned integers instead of foreign keys, matching Pterodactyl's tables.

## TypeScript and React

- 4-space indentation, single quotes, single quotes in JSX attributes.
- Layout of `dashboard/`: `api/` (client plus one `*.api.ts` per area and shared `api.types.ts`), `config/*.config.ts`, `hooks/`, `utils/*.util.ts`, `features/<name>/`, `framework/` (theme and UI primitives).
- Styled-components in a `X.styles.ts` next to `X.tsx`. Transient props start with `$`. Colors come from the theme tokens, never hard-coded.
- Relative imports inside the extension. The `@/` alias is only for Pterodactyl's own modules.
- A component that renders inside the panel must never break the panel: handle failures by rendering nothing and warning in the console.

## Repository

- Version lives in `package.json`, `package-lock.json`, `conf.yml` and the bug report template. The release workflow updates all four.
- Pull requests must pass `npm run typecheck` and `npm run pack`.
- User-facing behavior changes get a line in the README **Behavior notes**.
