# Contributing

## Before you start

Open an issue before large changes so work aligns with the project direction.

## Process

1. Open an issue describing the bug or feature.
2. Fork [github.com/wontonee/layrshift](https://github.com/wontonee/layrshift) and branch from `main`.
3. Run `composer install` in the plugin directory.
4. Make your changes.
5. Run checks before submitting:
   ```sh
   composer test
   bash scripts/plugin-check.sh
   ```
6. Open a pull request referencing the issue (e.g. `Closes #123`).

Do not post application passwords or production site URLs in issues.
