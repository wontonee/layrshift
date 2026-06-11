---
name: template-studio
description: Generate Gutenberg page content via LayrShift Template Studio (Gemini). Use when the user asks to generate a landing page, hero section, Gutenberg template, or AI-built page layout on a LayrShift-connected site.
enable_prompt: true
enable_agentic: true
---

# Template Studio

Generate Gutenberg block content through LayrShift's Gemini-powered Template Studio.

## Prerequisites

1. Template Studio enabled in LayrShift settings
2. Gemini API key configured (`layrshift_pro_settings`)
3. Confirm with `layrshift/execute-php`:

```php
$settings = get_option( 'layrshift_pro_settings', array() );
return array(
    'enabled' => ! empty( $settings['enabled'] ),
    'has_key' => ! empty( $settings['gemini_api_key'] ),
    'model'   => $settings['gemini_model'] ?? 'gemini-2.0-flash',
);
```

If the key is missing, tell the user to add it in **LayrShift → Settings**.

## REST endpoints

Base: `/wp-json/layrshift/v1/templates`

| Route | Method | Purpose |
|---|---|---|
| `/editors` | GET | List editors (gutenberg, elementor) |
| `/settings` | POST | Save Gemini settings |
| `/generate` | POST | Generate content from prompt |
| `/create` | POST | Create a WordPress page with generated content |

Authenticate with the same Application Password used for MCP.

## Workflow

1. **Clarify** — page title, purpose, sections needed, brand colors/fonts if known
2. **Generate** — POST `/templates/generate` with `prompt`, optional `editor` (`gutenberg` or `auto`), optional `title`
3. **Review** — inspect returned block content before publishing
4. **Create** — POST `/templates/create` with `title`, `content`, optional `status` (`draft` default)
5. **Verify** — `layrshift/execute-php` to confirm post exists and has content

## Generate request example

```json
{
  "prompt": "Modern SaaS landing page with hero, three feature columns, testimonial, and CTA",
  "editor": "gutenberg",
  "title": "Home"
}
```

## Notes

- Gutenberg generation is fully supported; Elementor is marked coming soon
- Default to `draft` status so the user can review before publishing
- Prefer registered Gutenberg blocks over raw HTML in `core/html`
- After creation, tell the user the edit URL: `/wp-admin/post.php?post={id}&action=edit`
