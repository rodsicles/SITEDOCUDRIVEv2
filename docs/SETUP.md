# Local setup

Clone/install notes for SITE DocuDrive. Product behavior is in `docs/SYSTEM_GUIDE.md`. UI rules are in `docs/AGENTDESIGN.md`.

Do not paste real passwords or `.env` values into documentation.

## Stack

Laravel 12, PHP 8.2+, Blade, Tailwind 4, Vite 7. Dependencies and scripts are in `composer.json` and `package.json`.

## Install

Requirements: PHP 8.2+, Composer, Node.js, and MySQL (or another database matching `.env`).

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Set `APP_TIMEZONE=Asia/Manila` and the `DB_*` values in `.env`, then:

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

`composer setup` runs install, env copy, key generation, `migrate --seed`, and the frontend build. Review seeders before using that path on a database that already has data.

Optional during development: `composer dev` starts the app, queue listener, logs, and Vite together.

## Commands

| Command | Purpose |
|---|---|
| `php artisan serve` | Local HTTP server |
| `npm run dev` / `npm run build` | Vite watch / production assets |
| `composer test` | Config clear, Rector dry-run, PHPUnit |
| `php artisan documents:index-content` | Index document text / OCR backlog |
| `php artisan schedule:work` | Run scheduled jobs locally |

`composer test` uses in-memory SQLite from `phpunit.xml`. **Do not run it on Laravel Cloud or production MySQL.** After Blade/CSS/JS edits, run `npm run build` and hard-refresh the browser.

Inspect pending migrations before production `migrate --force`. `2026_09_10_000001_ensure_it_dean_account` resets the `dean` account if it runs. See Operations in the system guide.

## Workflow corrections release (`86cd234`)

The latest workflow correction migration is [`2026_10_21_000001_add_workflow_correction_history.php`](../database/migrations/2026_10_21_000001_add_workflow_correction_history.php). It supports published teaching-load revisions/history/voiding, request corrections/exemptions, and course metadata history/manual-unit protection. Run it before using those functions. It requires the existing Teacher's Load, document-request, and course-unit schema; it does not assign subjects or create schedules.

On the target host, first inspect pending migrations:

```bash
php artisan migrate:status
```

If the pending list is reviewed and intended, the normal production/deployment command is:

```bash
php artisan migrate --force
```

If older unrelated migrations are intentionally pending (especially the Dean-account reset described above), and the prerequisite schema is already installed, run only this feature migration instead:

```bash
php artisan migrate --path=database/migrations/2026_10_21_000001_add_workflow_correction_history.php --force
```

Use the deployment hook or the manual command as appropriate for the host; do not assume that a Git push ran migrations. After release, verify authorized load revision/history/PDF, request cancellation/reopening with retained submissions, and course term/unit edits. Record the actual migration/deployment results separately. Production execution of this release has not been verified in this documentation update.

The migration rollback removes its new history/draft data and metadata flags. Review application/database recovery together rather than using schema rollback for a cosmetic issue.

Implementation checks for this release were 94 tests passed, 1 existing MySQL-only skip, successful Vite build, and a targeted Rector check. This is a historical release result; rerun suitable **local/test-environment** checks after new code changes. Never run `composer test` on Laravel Cloud.

## Sections release (October 27)

Run the reviewed migration `2026_10_27_000001_create_course_sections_table` before using Sections or the new Teacher's Load choices. It creates only the section catalog and eight BSIT A/B defaults for years 1–4; it does not change saved loads or invent sections for other programs. Local WAMP completed it as batch 10 on October 27. Cloud has not been checked or migrated. After deployment verify Dean/Coordinator scope, section add/rename/remove/restore, existing legacy section labels, and Drafts including open revisions. Rollback drops the section catalog but not saved load labels; coordinate code/schema rollback rather than leaving the new code without its table.

## Original filename metadata (October 27)

Before deploying the filename fix, apply `2026_10_27_000002_preserve_original_document_filenames` and `2026_10_27_000003_preserve_report_certificate_filenames`. They add nullable metadata for Documents/versions/TG/EQ and Reports/training certificates respectively. Both completed on local WAMP; Cloud migration state is unverified. No files or storage objects are renamed and historical missing names are not backfilled from guesses. Rollback removes this metadata, so coordinate application/database recovery. Check a renamed display title with a different original filename, request submission, approved academic download, copy/version/restore, report/certificate replacement, Unicode names, and legacy fallback. Generated Teacher's Load and backup naming is unchanged.

## Legacy WAMP imports

Legacy WAMP imports may contain MyISAM tables. Check engine compatibility before adding workflow foreign keys: MyISAM cannot provide transactional rollback or foreign-key guarantees. On October 21 the local upload-related tables were repaired to InnoDB with approval and the workflow migration completed as batch 9; Cloud was not changed. The migration now resumes already-created column groups/tables and installs missing constraints/indexes after interrupted MySQL DDL. Do not delete partially created history tables or reset accounts as a recovery shortcut. Review/approve engine changes on each target host separately.

## Remotes

In this checkout, `origin` is the V3 repository and `v2` is the user-described deployed repository. Confirm remotes before push or release work. A local commit is not deployment evidence.
