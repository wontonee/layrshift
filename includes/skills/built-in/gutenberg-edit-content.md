---
name: gutenberg-edit-content
description: Create or edit WordPress content in the native Gutenberg/block editor using LayrShift Gutenberg abilities. Activate when the user asks to build, rebuild, migrate, or update a post, page, template, template part, or navigation using Gutenberg/native blocks.
enable_prompt: true
enable_agentic: true
---

# Editing Gutenberg Content

Use this playbook for native WordPress block editor work. Static Gutenberg blocks need the browser JavaScript serializer before queued content becomes live, so the **Block Editor Queue** admin page is part of the workflow.

## Start Here

1. Call `layrshift/gutenberg-get-finalizer-runtime`.
2. If `finalizer_runtime.online` is false, ask the user to open `finalizer_runtime.dashboard_url` (`admin.php?page=layrshift-gutenberg-finalize`) in wp-admin and keep that page open while you work.
3. If `finalizer_runtime.online` is true, tell the user to keep the queue page open. If later responses show `online=false`, ask them to reopen it.
4. Use `finalizer_runtime.sse_url` with `curl -N` for waiting, or `finalizer_runtime.poll_url` as fallback. Do not spam MCP abilities while waiting.

## Read Before Writing

- Use `layrshift/gutenberg-get-content` for the target. It reads live `post_content`, not queued pending specs.
- If `pending_gutenberg_change` is present, inspect the batch with `layrshift/gutenberg-get-pending-batch` before editing. Do not stack another pending change unless the user confirms canceling the old batch.

## Compose With Registered Blocks

Build content from registered blocks — core *and* third-party — as `{name, attributes, innerBlocks}`. The finalizer serializes each block with its editor JavaScript; never hand-write block HTML.

- **Core blocks:** `core/heading`, `core/paragraph`, `core/list` + `core/list-item`, `core/image`, `core/quote`, `core/buttons` + `core/button`, `core/table`, `core/code`, `core/separator`. Use `core/group`, `core/columns` + `core/column` for layout.
- **Third-party blocks:** if a registered block exists (WooCommerce, Kadence, ACF, etc.), use it. Discover names via `layrshift/execute-php`:

```php
$blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
return array_keys( $blocks );
```

- Use `core/html` only for small fragments with no registered-block equivalent. Never wrap whole sections in `core/html`.

## Choose the Write Path

- Use `layrshift/gutenberg-write-content` only when every block is a registered `layrshift/*` dynamic-only block (writes live).
- For static/native blocks, use the pending queue:
  1. `layrshift/gutenberg-add-pending-change` — omit `batch_id` on first target, reuse it for the rest of the same change.
  2. `layrshift/gutenberg-enable-batch-finalization` after all changes are queued.
  3. Stream SSE or poll until batch is `finalized`, `failed`, or `conflicted`.

## Completion

Queued changes are not live until the batch reports `finalized`. Re-read with `layrshift/gutenberg-get-content` and verify the block tree.
