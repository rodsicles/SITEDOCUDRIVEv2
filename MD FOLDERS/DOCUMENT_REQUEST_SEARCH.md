# Document Requests and Content Search

Implemented 13 September 2026.

## Daily workflow

- Every active employee can create a request for one or more other active employees.
- Each recipient has an independent compliance status: pending, submitted, changes requested, or approved.
- A requester can review submissions and send a required correction note.
- Recipients upload through a focused modal. PDF, Word, image, or any supported type can be required.
- Request and review events create direct-link notifications and audit records.
- Dean, Coordinator, and Faculty sidebars expose Document Requests and Document Search.

## Search coverage

- Titles, subjects, tags, uploader, department, course, school year, semester, compliance status, type, and date.
- Word `.docx` text is extracted directly.
- Text PDFs use the PHP-native `smalot/pdfparser` package, with `pdftotext` as the preferred extractor when installed.
- Scanned PDFs and images use `pdftoppm` plus Tesseract OCR when those host executables are installed.
- SHA-256 fingerprints power duplicate warnings even when a document contains no searchable text.
- Existing and missed files are indexed hourly by the Laravel scheduler.

## Safe deployment

Run only the feature migration if `2026_09_10_000001_ensure_it_dean_account` is intentionally still pending:

```bash
php artisan migrate --path=database/migrations/2026_09_13_000003_create_document_request_and_search_tables.php --force
php artisan documents:index-content
```

For scanned-document OCR, install Poppler (`pdftoppm`) and Tesseract on the worker host. OCR is intentionally best-effort: missing binaries mark the index as failed without blocking uploads or normal document access.

The scheduler must be running for hourly backfills:

```bash
php artisan schedule:work
```
