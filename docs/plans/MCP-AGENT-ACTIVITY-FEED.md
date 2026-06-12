# MCP Agent Activity Transparency (v1)

**Status: deferred post-1.0.0** — planned after WordPress.org approval; not included in LayrShift 1.0.0.

## Answer: is it possible?

**Yes**, for observe-only scope (no approval gate):

| Goal | v1 feasibility |
|------|----------------|
| Real-time actions while MCP is connected | Yes — server-side hooks + SSE/poll to wp-admin |
| Show posts/pages/media/settings touched | Yes (best-effort) — derive targets from ability name + input |
| Detailed created/updated/deleted log | Yes — extend Logger + Activity Log UI |
| Preview before apply | Partial — Gutenberg pending queue only; global preview is v2 |
| Approve/reject before publish | Out of v1 — observe-only |

**Limitation:** `layrshift/execute-php` and `run-wp-cli` can change anything; v1 logs that they ran and coarse metadata, not a full diff.

## Architecture (v1)

1. **ActivityMonitor** — hook `wp_before_execute_ability` / `wp_after_execute_ability` for `layrshift/*` only
2. **ActivitySummarizer** — human labels (action, resource_type, summary)
3. **ActivityStore** — enriched `layrshift_ability_log` + short-lived stream transient
4. **ActivityStreamEndpoint** — `GET layrshift/v1/activity` + SSE stream
5. **Admin UI** — LayrShift → Agent Activity (live + history)

## Explicitly not in v1

- Blocking or queuing writes pending approval
- Full diff preview for execute-php / WP-CLI
- Logging `mcp-adapter/*` meta-tools
- Front-end/public activity feed

## Future v2

- Optional approval mode for write/delete abilities
- Deeper previews for Gutenberg/Elementor pending payloads
