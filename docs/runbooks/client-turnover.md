# Client turnover: review gate

Status: read-only inventory implemented. Production cleanup is NOT executed or enabled.

1. Verify production environment and the exact `dean` user ID. Confirm user and employee names match the intended client Dean. Never infer ID from a screenshot or local database.
2. Run `php artisan turnover:preview --keep-user-id=VERIFIED_ID --expect-name="EXACT VERIFIED NAME"` in the target environment. This only reads the database and prints inventory; it does not access/delete storage, back up data, or test storage existence. Protect the output as employee information.
3. Review all proposed clears and every REVIEW table. Preserve catalogs, units, school years, system folders/categories, Dean credentials/profile. Explicitly decide personal folders/categories, assignments, course history, equipment, backup history and analytics sources. Inspect foreign keys referencing deleted users from retained records. Unknown tables are never automatically cleared.
4. Inventory storage separately, including converted PDFs, unreferenced objects, archives and avatars. Cross-check protected/shared paths; never delete a whole bucket. Existing orphan-cleanup command is NOT a turnover tool.
5. Back up database and each relevant storage disk to a separate protected destination. Verify restoration in isolation. No production tests or seeders.
6. Prepare the deletion executor only after the exact inventory is approved. Require reviewed manifest, environment identity, protected user ID, backup evidence, maintenance and stopped workers/scheduler. Refuse nontransactional affected database tables. Use explicit dependency ordering; never disable foreign keys or truncate the database. Rehearse on the restored copy.
7. Before execution refresh inventory under maintenance; stop if it differs. Commit database changes transactionally, retain the manifest for storage deletion retries, and report every failed object deletion. Storage deletion cannot be rolled back by a database transaction.
8. Verify one account remains, Dean credentials unchanged, catalogs retained, practice data empty and no broken relationships. Revoke other sessions and inspect external queues/caches. Verify login and controlled upload/download; remove test artifacts. Keep an external turnover record and backup retention policy.

Analytics must reflect real remaining data: one account, retained catalog counts, zero cleared operational records. New login/turnover activity is expected. Do not hardcode displayed metrics to zero.

Never use `migrate:fresh`, `db:wipe`, bulk bucket deletion or seeders for turnover. Cloud execution, backup restoration and storage inventory remain unverified.
