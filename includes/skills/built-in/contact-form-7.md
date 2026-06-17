---
name: contact-form-7
description: Discover and inspect Contact Form 7 forms, shortcodes, and mail settings on a LayrShift site. Use for CF7 forms, contact forms, or form shortcodes.
enable_prompt: true
enable_agentic: true
---

# Contact Form 7 via LayrShift

Prefer **`layrshift/contact-form-7-*` abilities** when Contact Form 7 is active.

## Start here

1. `layrshift/skill-get` → `contact-form-7`
2. `layrshift/contact-form-7-get-status`
3. `layrshift/contact-form-7-list-forms`
4. `layrshift/contact-form-7-get-form` for markup and mail config

## Abilities

| Step | Ability |
|------|---------|
| Plugin status | `layrshift/contact-form-7-get-status` |
| List forms | `layrshift/contact-form-7-list-forms` |
| Form detail | `layrshift/contact-form-7-get-form` |

## Rules

- Forms are `wpcf7_contact_form` posts — edit via `execute-php` or block editor only with user approval
- Do not expose SMTP/API secrets if stored in third-party CF7 extensions
- Embed forms with the returned shortcode in pages via `gutenberg-edit-content`
