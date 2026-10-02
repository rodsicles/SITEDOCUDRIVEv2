# Dynamic document categories

This change adds personal categories for Faculty, Coordinator and Dean, and system-wide categories managed by the Dean. Existing built-in categories and document category enum values are preserved. Custom documents use their stable `category_id` and retain `Other` in the legacy category column.

## Deployment

1. Take a database backup or snapshot before deployment.
2. Run `php artisan migrate --force` as part of the deployment, before routing traffic to the new application code. The required migration is `2026_09_23_000001_add_managed_document_categories`.
3. Build assets with `npm ci` and `npm run build`, using the existing deployment process.
4. Verify creating a personal category, a Dean system-wide category, uploading, renaming, and deactivating. Check another account cannot access a personal category or its files.

The migration adds fields and foreign keys; it does not rewrite existing documents or folders. No production migration is run by the implementation or its automated tests. Feature tests use the configured in-memory SQLite database.

## Behavior

- Category scope is immutable. Personal category folders and descendants inherit owner-only access.
- Category names are unique within their scope, ignoring case and repeated whitespace. Built-in names are reserved.
- Renaming preserves tab URLs and document associations.
- Deactivated categories remain browsable for existing records, but reject new uploads, copies, moves into them, and request submissions. Reactivate to resume submissions.
- General document requests can target active system-wide categories. Personal categories cannot receive requests from other accounts.
- Category management has no permanent-delete action.

## Rollback

Do not run a blind migration rollback after categories have been created. The migration intentionally refuses to remove category metadata when managed category records exist. Reverting to older application code is also unsafe once personal category folders exist, because older navigation does not understand them. For rollback, use the coordinated pre-deployment database and application backup and account for any files uploaded since that backup. Prefer a forward fix to preserve new data.
