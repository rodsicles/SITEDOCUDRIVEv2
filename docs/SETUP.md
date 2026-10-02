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

## Remotes

In this checkout, `origin` is the V3 repository and `v2` is the user-described deployed repository. Confirm remotes before push or release work. A local commit is not deployment evidence.
