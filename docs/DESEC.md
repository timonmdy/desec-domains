# deSEC notes

These notes explain the deSEC behavior this extension relies on. Source: <https://desec.readthedocs.io/en/latest/>.

## Tokens

A deSEC token without scoping policies can read and write every domain in the account. A token with at least one policy only sees domains that have a policy, and can be limited to a domain, a record type or a subname. That makes one token per domain practical:

- Use the **default token** (extension settings) when you trust the panel with the whole account.
- Give a domain its own **scoped token** (Domains → Manage → Domain token) to limit what a leaked token can do. The token needs write access to `SRV`, `A` and `AAAA` records of the domain, plus read access for the overview and the record editor.
- A token needs `perm_create_domain` only if you create domains through the API. This extension only connects domains that already exist.

## Rate limits

- 2000 requests per day per account across all domains.
- Changes are limited per domain (about 15 per minute, 100 per hour and 300 per day), and bulk requests count once.

Consequences in this extension:

- Allocation uses one bulk request for all records.
- The console page never calls deSEC.
- Admin reads are cached (setting **Cache deSEC reads**).
- The reconcile job only writes when something changed, retries failed allocations at most every 15 minutes, and stops for the run when deSEC answers with HTTP 429.

## SRV records

An SRV record has the form `priority weight port target.` and its target needs a trailing dot in deSEC. The target must resolve to an address, which is why the extension can create an A or AAAA record for it.

Wildcards are only valid as the first label (`*`). A whole-domain allocation therefore creates `_minecraft._tcp` plus `*`. The wildcard matches every SRV lookup under the domain that has no record of its own, so it also catches unrelated services. An exact record, for example one created by a subdomain allocation, always wins over the wildcard.

The apex is addressed as `@` in API URLs and as an empty subname in request bodies.

## TTL

deSEC enforces a per-domain minimum TTL, usually 3600. Lower values are rejected and the error is shown to the administrator.
