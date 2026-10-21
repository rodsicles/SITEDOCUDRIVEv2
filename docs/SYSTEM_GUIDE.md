# SITE DocuDrive — System Guide

## Quick Context

Latest source review: **2026-10-21, Asia/Manila**. Implementation baseline: **`86cd234`**, including **`788be16`** and **`77c2ad8`**. This review covers the recent workflow changes; the earlier broad baseline was `839a07d`. Both V2 and V3 `main` were successfully pushed to `86cd234` in this session. Production deployment, migration execution, workers, and browser behavior remain unverified.

SITE DocuDrive is the employee document and academic-workflow portal for the School of Information Technology and Engineering, St. Paul University Philippines. It uses Laravel 12 / PHP 8.2+, Blade, Tailwind 4, Vite 7, and JavaScript. Dependencies and scripts are authoritative in `composer.json` and `package.json`.

- One SITE department contains **BLIS, BSEnSE, BSIT, BSCpE**. BSEnSE currently means Environmental and Sanitary Engineering.
- Coordinator management scope is **home program + Dean-assigned “Also handles” programs**. Reuse `CoordinatorDepartment::programs()` / `User::handledPrograms()`. Management scope is separate from subjects the Coordinator personally teaches.
- Faculty document access includes own files, explicit shares, and qualifying broadcasts. Dean visibility is broad but must honor personal-category and private-folder rules. Never infer authorization from a sidebar.
- Current UI: dark-green sidebar, white surfaces, pale green-gray workspace, square controls, restrained borders, Segoe UI. Read the design section before adding styles.
- Shared document search is implemented in `DocumentSearchService`; compact errors use `SiteRequest` and `ui-error-dialog`. Some requested edge cases remain incomplete: see Known gaps.
- Upload, document-request, review, archive, and Teacher's Load flows are distinct. Approval rules and saved records must survive UI edits.
- Latest workflows: late archive additions; current teaching assignments on profiles; published load revisions/voiding/history; author-managed request corrections; editable course term/year/units with history. The last three need `2026_10_21_000001_add_workflow_correction_history` before use. Existing subject assignments remain year-independent; no schedules were invented or backfilled.
- `origin` is V3 and `v2` is the V2 repository in this checkout. V2 is the user-described deployed repository. Verify remotes and deployment status before release actions; a local commit is not deployment evidence.
- Living documentation lives under `docs/`: this guide (behavior), `docs/AGENTDESIGN.md` (UI), `docs/AGENTS.md` (workflow), `docs/SETUP.md` (install). Do not follow `docs/archive/legacy/` as current instructions.
- Read only the relevant section below. Update this guide after implementation changes; do not copy private employee records, secrets, or logs into it.

## Topic index

| Task | Read section | Start in code |
|---|---|---|
| Access / programs / accounts | Permissions and accounts | `app/Support/CoordinatorDepartment.php`, `app/Services/EmployeeService.php`, `routes/web.php` |
| Files / folders / uploads | Documents and submissions | `app/Services/DocumentService.php`, `app/Services/FolderService.php` |
| Search / OCR | Search and indexing | `app/Services/DocumentSearchService.php`, `app/Services/GlobalSearchService.php` |
| Requests / compliance / corrections | Document requests | `app/Http/Controllers/DocumentRequestController.php`, `app/Services/DocumentRequestManagementService.php` |
| Teaching workload / revisions | Teacher's Load | `app/Services/TeacherLoadService.php`, `resources/js/teacher-loads.js` |
| Profile subjects / timetable | Teacher's Load | `app/Services/TeachingAssignmentService.php`, `resources/views/partials/teaching-assignment-summary.blade.php` |
| Course term / units / edit history | Permissions and accounts | `app/Support/CourseInput.php`, `app/Services/CourseService.php`, `app/Http/Controllers/CourseHistoryController.php` |
| Dashboard / tasks / communication | Daily operations | `app/Services/DashboardService.php`, `routes/web.php` |
| History / school years / late additions | Archives and audit trail | `app/Services/SchoolYearService.php`, `app/Services/ArchiveContentService.php`, `app/Models/DashboardLog.php` |
| Layout / colors / modal / profile | UI design and `docs/AGENTDESIGN.md` | `resources/css/app.css`, `resources/views/layouts/dashboard.blade.php` |
| Failures / recovery | Error handling | `resources/js/site-request.js`, `app/Support/ApiError.php` |
| Setup / deploy / tests | Operations and verification | `docs/SETUP.md`, `composer.json`, `phpunit.xml`, `routes/console.php` |
| Performance / leftover schema | Extracted ops notes | `package.json`, `app/Services/DashboardService.php` |
| Where docs live | Documentation map | `docs/`, `docs/archive/legacy/` |

## Permissions and accounts

| Role | Current model and boundaries |
|---|---|
| Dean | Broad employee, document, review, and administrative access; creates Coordinators and Faculty and assigns extra handled programs. Privacy still applies. |
| Secretary | Shares many `/dean` routes through `role:Dean,Secretary`, but individual controller checks can be stricter. Do not assume universal Dean equivalence. Teacher's Load manager checks explicitly name Dean and Coordinator. |
| Program Coordinator | Manages permitted Faculty/program resources using home plus extra programs. Can create Faculty; cannot infer permission to create Coordinators or use system administration merely from expanded program scope. |
| Faculty Employee | Personal work, assigned tasks and subjects, received requests and shared documents; own published/void teaching loads and saved revision history. No access to unpublished load revisions. |

Sources: `routes/web.php`, `app/Models/User.php`, `app/Models/Document.php`, `app/Models/Folder.php`, and `app/Models/Concerns/ScopesSubmissionVisibility.php`. Specific endpoint checks are authoritative for exceptions.

Accounts use an employee record linked to a user/role. Create Coordinator and Edit Employee expose home Program plus “Also handles”; extra assignments are stored through `EmployeeProgram` / `employee_programs`. An invalid or missing home program yields no Coordinator handled-program list. The UI hides the home program from the additional list. Own profile and employee profile show the extra programs.

Account forms share `resources/views/partials/account-create-form.blade.php`. Subjects use the term/year/search picker. Employee numbers are generated by `EmployeeNumberGenerator`; personal profile is not the place to reassign them. Faculty classification supports Full-Time and Shared. First-login password rotation is part of created-account behavior. Coordinator personal teaching assignments remain home-program-based; extra management programs do not automatically grant teaching subjects.

Changing handled programs affects documents, reviews, course catalog, analytics, Teacher's Load selection, search, requests, announcements, calendar, and activity scope. Verify each affected direct endpoint and cache when changing this rule. Never assign additional programs automatically during documentation or deployment.

Course Catalog create/edit supports code/title plus optional year level (1–6), semester, and lecture/lab units. `CourseInput` accepts existing official alphanumeric, space, and hyphen formats; duplicate checks are case-insensitive within a program. `CourseService::updateDetails` records before/after metadata in `course_changes`; authorized catalog managers can open Change history. UI saves carry a concurrency version. Explicit unit edits set `units_manually_set`, so `courses:sync-units` preserves them. Unknown values can remain null. Existing published load rows/revisions keep their snapshots; deactivation/reactivation remains the way to remove/restore referenced courses.

## Documents and submissions

Daily flow: select category → browse folder hierarchy → select destination → upload → review where required → access the resulting document/preview. Current Folder, Recent, and Favorites provide alternate entry points. Actions include authorized viewing/downloading, favorites, renaming, versions, copying/moving, and recycle-bin operations.

Built-in categories include Accreditation and Certifications, Event Letters, Academics, Teaching Guides, and Exam Questionnaires. Managed categories also use stable `category_id` associations while legacy category values remain supported.

The shared Documents scope bar provides a **Favorite documents (count)** link with a star icon. It opens the existing `browse=favorites` list across folders and categories, not just the current folder. Favorites are personal to the signed-in user and `DocumentService::getFavoriteDocuments()` filters each document through `canView()`; a favorite never grants extra access. Star/unstar actions remain in the file rows. No separate favorites sidebar/page or migration is needed.

Dynamic category rules: personal categories are owner-scoped; system-wide categories are managed through authorized administration. Scope is immutable. Renaming preserves associations. Deactivation keeps existing records browsable but blocks new incoming content. Category management has no permanent-delete action. See `docs/runbooks/dynamic-categories-deployment.md` for the rollback caveat.

The guided upload dialog is `resources/views/partials/document-upload-dialog.blade.php`; keep its approved destination-selection flow. Client/server validation limits ordinary batches to up to three files, 10 MB per file. Teaching Guide/Exam Questionnaire destinations accept PDF/Word; other permitted destinations support configured image types. Storage quota checks are additional constraints.

Academic hierarchy is managed by `AcademicHierarchyService`. Teaching Guides use year/semester/subject and TG/LB destinations; exam structures use assessment folders and TOS/TOQ destinations. Inspect that service and upload-destination endpoints rather than rebuilding folder paths in UI code.

Normal academic submissions have pending/approved/rejected review flows. Dean/Secretary upload paths can auto-approve; scoped Coordinator review endpoints also exist. The document library applies `onlyApprovedShareable()`, including request-submission approval rules, so a pending item may belong in a review queue rather than normal search results.

Privacy is enforced through folder/category ownership and document visibility. Before changing lock behavior, inspect `FolderService` propagation to descendants and category ownership. A private folder must not become visible merely because an actor handles more programs. Missing privacy-schema fallbacks exist and are not proof that an unmigrated deployment is safe.

Document versioning currently has two paths: ordinary preview replacement uses `DocumentService::uploadNewVersion`, while request corrections use `DocumentVersionService`. Their authorization, linked academic-file synchronization, approval, and search-index handling are not identical; see Known gaps. Preview/download responses use storage and naming helpers. Do not confuse a version snapshot with a duplicate submission or a school-year archive.

## Search and indexing

Implemented in `839a07d`: shared authorized document matching through `DocumentSearchService`, reused by Documents, suggestions, global search, and the retained document-search route. Header search also returns authorized people, tasks, and announcements through `GlobalSearchService`.

Current matching covers title, subject, tags, legacy category text, indexed content, and the immediate folder name/slug. Descendant traversal for current-folder filtering is recursive. **Searching every ancestor name in the displayed breadcrumb is not yet equivalent to this matching implementation.**

Scopes in the current service:

- `folder`: selected folder and descendants; defaults to active school year plus unassigned-year documents unless a year filter overrides it.
- `all`: all accessible documents; a selected year restricts results, but without a year filter this scope is not automatically limited to the active year.
- `archives`: archived school-year IDs, optionally restricted to one archived year.

Filters include employee, program, course, school year, semester, compliance status, type, and dates. Saved searches are normalized again against current scope. Permission checks precede results and must also protect counts, suggestions, excerpts, preview, and downloads. Search is database matching (`LIKE` and related queries), not a separate external search engine.

`DocumentContentIndexer` extracts DOCX text; `.doc` uses antiword; PDF extraction prefers pdftotext with a PHP parser fallback; scanned PDF/image extraction needs Poppler/Tesseract. Missing executables can prevent content search while metadata remains usable. Index status includes pending/indexed/no_text/failed. Do not promise OCR solely because a package or UI label exists.

SHA-256 `file_hash` values are stored during upload/index for duplicate-file warnings. Hashing does not replace authorization or content search.

`IndexDocumentContentJob` declares three attempts, backoff, and a timeout. The indexer still catches many extraction exceptions and records failure, so those failures do not automatically trigger queue retries. The hourly `documents:index-content` schedule provides another indexing path; inspect command selection before assuming it retries every failed record.

## Document requests

Dean/Coordinator create requests for eligible recipients, choose requirements/destination, and set due dates. Faculty receive and submit requests; they cannot create/manage requests through the current endpoints. Recipient eligibility and academic course constraints are enforced by the current controller; one department does not make every employee/course automatically eligible.

The creation modal refreshes recipient choices from `GET /document-requests/recipients` whenever opened and offers **Refresh recipients** without resetting the request form. Both the initial list and refresh use `DocumentRequestManagementService::eligiblePeople()`: active users except the requester, limited to home plus extra programs for Coordinators. Faculty cannot call the recipient-list endpoint. Search includes name, username, program, and role, resets list scroll, and shows matching counts. Selections survive refresh when still eligible; updated course assignments feed the common-course destination picker. A failed refresh keeps the prior list and displays a retry message; submission still revalidates recipients/course assignments on the server. No account backfill or migration is needed for this refresh.

Each recipient tracks independent pending, submitted, changes_requested, approved, or exempt status. The parent request is open, completed, or cancelled. Submission is separate from requester review. Request approvals can synchronize Teaching Guide/Exam Questionnaire records and their final destination. Corrections require meaningful feedback. Requests generate notifications and activity records.

Creation now shows a live selected-recipient count and names. **Done selecting** collapses the checklist without sending the request; **Change recipients** reopens it and selected checkboxes remain part of submission. The deadline is optional and explicitly labeled with the application timezone. Client checks use the server clock/timezone and refresh the minimum as the form ages; past/too-soon deadlines show inline guidance. Server validation still requires a future deadline if supplied. Failed creation reopens the modal with old inputs, selected recipients, an in-modal error summary, and the deadline error at its field instead of only a red message on the list page.

Submission success redirects explicitly to Received, not the POST endpoint/referrer. Retrying an already submitted/approved recipient with a linked document returns an already-submitted notice without a second upload, notification, version, or lock increment; authorization is checked first. Closed/expired/exempt submission attempts and file validation redirect browser users to the list with useful errors; JSON callers retain 422 validation responses. The upload button prevents repeated clicks. Corrections remain allowed only in `changes_requested` state.

Source: `app/Http/Controllers/DocumentRequestController.php` and its models/views. General requests can target active system categories; personal categories are not cross-account submission destinations. Preserve assignment restrictions and file authorization when altering recipient or destination pickers.

Request authors (Dean/Coordinator) can use **Manage request** to edit title/instructions/deadline/late-submission rules, add eligible recipients, exempt outstanding recipients with a reason, cancel, or reopen. Destination, course, file type, and existing submitted files cannot be changed through this form. Added recipients are rechecked against current handled programs and academic assignments. Exemptions retain the submitted document and previous review state; reinstatement restores that state. Approved recipients cannot be exempted. Cancellation blocks submission/review without deleting approvals/files. Requests complete when all recipients are approved or exempt; a completed request can be reopened to add more recipients. Every management action records reason, actor, before/after state in `document_request_changes`, notifies affected recipients, and increments `lock_version`. Parent-row locking serializes management with submissions/reviews; stale management forms return 409. See `DocumentRequestManagementService` and `DocumentRequestManagementTest`.

## Teacher's Load

Dean and Coordinator managers select eligible Faculty or teaching Coordinators, school year, semester, and employment type. Subject choices come from the selected employee's assigned courses for that semester. Empty assignments are reported; the picker must not silently substitute the whole catalog.

The modal moves through Faculty & term → Teaching details → Review & export. Rows contain subjects/sections, schedules, lecture/lab units, class size, and load equivalent; non-course duties are supported. Catalog units prefill lecture/lab values with an explicit edit control. New load-equivalent values start at zero for manual entry. Changing context must handle incompatible rows carefully.

Validation uses a bounded in-editor alert: server field errors include their step, subject/duty row, and schedule, with links that open the relevant step and focus the control. Finalize checks earlier steps, an empty load, and subjects without schedules before submitting; saving an unscheduled draft remains allowed. JSON/CSRF headers are preserved on save/finalize so server validation is not lost to an HTML response. Server/session failures are distinct from missing fields. The local WAMP status check on October 21 found `2026_10_21_000001_add_workflow_correction_history` pending; this UI fix does not execute it or prove the user's failing request had no other server cause.

Loads are saved as drafts, previewed/exported as PDF, then finalized. Finalization requires complete validated rows/schedules. Published rows are immutable during revision preparation. Dean/scoped Coordinator can start a reasoned revision, save/preview it separately in `revision_draft`, publish it, or discard it. The existing published timetable/PDF/profile remains current until publication atomically saves its snapshot to `teacher_load_revisions` and replaces the rows. Saved published revisions remain downloadable through authorized history. Managers can void a published load with a reason (its PDF is labeled VOID and current teaching summaries exclude its schedule), then prepare a replacement, or delete an unpublished draft. Archived loads remain read-only. `lock_version` guards every modification; Faculty see only their own published/void records and never unpublished revision data. Lifecycle actions are logged; publication/voiding notify the employee. Sources: `TeacherLoadController`, `TeacherLoadService`, `TeacherLoad` model, `resources/views/teacher-loads/`, `resources/js/teacher-loads.js`, and `resources/css/teacher-loads.css`.

`TeachingAssignmentService` supplies the current teaching summary on Faculty/Coordinator own profiles and Teacher's Load pages. It combines active account subject assignments for the selected semester (including older assignments with no timetable) with the employee's finalized load for the active school year and semester. Published section/schedule snapshots are retained; draft schedules, other employees' loads, and past-year schedules are excluded. Account assignments have no school-year field, so they are displayed as current assignments rather than backfilled into historical loads. No synthetic sections, schedules, or load records are created. Managers see the summary after selecting an employee within `TeacherLoadService::facultyQuery()` scope and can follow **Manage subjects** to the existing account editor. Coordinators cannot edit another Coordinator's account. In the load editor, **Add assigned subjects** adds missing subject rows with catalog units and empty schedules; managers complete the required section/class size and eventual timetable. Finalization publishes the initial load or its revision; an unpublished revision never changes the profile timetable.

## Daily operations

| Feature | Working reference and purpose |
|---|---|
| Dashboards / analytics | Role-based summaries and actionable text insights help staff identify overdue work, changes, and useful observations. Inspect `DashboardService`, analytics services, and role dashboard views; do not invent statistics. |
| Tasks | Dean task creation/assignment; assignees update progress and attach permitted files. `TaskService`, task controllers, and `TaskAssigneeResolver` define scope and destinations. |
| Notifications | Bell count/list, read actions, and actionable links; notification service generates role-appropriate destinations. Polling/request guards are present. |
| Announcements | Shared feed with current visibility restrictions, including Coordinator handled-program scope. `Announcement` model and controller define access. |
| Calendar | Events/details use role and handled-program restrictions in `CalendarEvent` and `CalendarController`. |
| Reports / professional development | Report files and employee professional records use their dedicated controllers/services. Do not treat dashboard reports, uploaded reports, and performance evaluations as the same record type. |
| User guide | `resources/views/user-guide.blade.php` is employee-facing help. Update relevant instructions when workflows change; it is separate from this developer reference. |
| Login / profile | Username/password login, reset requests, forced initial password change, photo upload, personal details and security. Profile uses tabs and shows fixed account information plus handled programs. |

## Archives and audit trail

`SchoolYearService` archives the active year and creates the next active year's academic folder structure. It tags relevant unassigned records; pending/rejected academic submissions are detached from the archived year to remain in active workflows. Restore-as-active is a consequential recovery operation that can remove the mistaken active year; inspect the service and confirmation before using it.

Archived-year pages retain authorized view/download links and now allow late additions. `ArchiveContentController` and `ArchiveContentService` serve year-scoped destination browsing, subject-folder creation, file uploads, folder creation, and rename actions. They require an archived year and a destination tagged to that same year, check private ancestors, managed-category ownership/activity, and academic course access, then reuse the document upload limits and approval flow. Ordinary upload, folder-create, move, and copy paths reject archived destinations so they cannot silently attach active-year records to an archive. Faculty/Coordinator academic submissions follow pending review; Dean/Secretary uploads can auto-approve under the existing rules. Approved mirrors retain the archive's year ID. Archive rows label content created after the current `archived_at` date. Rename activity records the old/new name and actor. Faculty can rename their own archived document and custom folders; managers retain existing document rename scope, while approved Teaching Guide/Exam Questionnaire rename controls are for Dean/Secretary and scoped Coordinator. This feature adds no migration; inspect `ArchiveContentTest` before changing year or permission rules.

Archives retain school-year history. Recycle Bin handles deleted content and a scheduled 30-day purge. Database backup/restore is a separate administrative workflow (`BackupController` / `BackupService`); database backups do not automatically establish a complete external-file recovery strategy.

Runtime activity records use `DashboardLog`: actor, target employee/user where supplied, activity/type, visibility, time, and IP. Dean log access is broad; Coordinator lists include own/targeted actions and relevant Faculty in handled programs; Faculty lists include own and targeted actions. Dean Audit Trail has separate controller query logic. Verify Secretary behavior explicitly when changing log access.

This guide's change history records **software changes**, not employee actions. Git remains the complete code history. Do not describe the runtime log as tamper-proof or exhaustive without implementing and verifying those guarantees.

## UI design

UI patterns to follow: `docs/AGENTDESIGN.md`. Token values and cascade: `resources/css/app.css`, plus existing feature CSS and shared Blade components. Historical screenshots and `docs/archive/legacy/AGENTDESIGN.md` are not current CSS specifications. New work uses `--site-*` (`#0d5c3b` family). Some older `app.css` rules still hard-code lime `#028a0f`; do not add more lime.

| Token | Value | Purpose |
|---|---|---|
| `--site-primary` | `#0d5c3b` | Primary actions / accents |
| `--site-primary-strong` / `--site-primary-hover` | `#08472e` / `#0a5135` | Strong/interactive states |
| `--site-sidebar` / `--site-sidebar-strong` | `#0d4f32` / `#083d27` | Navigation |
| `--site-heading` / `--site-text` | `#0b4931` / `#16241d` | Headings / body |
| `--site-muted` | `#68766f` | Secondary text |
| `--site-surface` / `--site-surface-subtle` | `#ffffff` / `#f7faf8` | Content / subtle workspace |
| `--site-surface-selected` | `#edf7f1` | Selection |
| `--site-border` / `--site-border-strong` | `#d9e2dd` / `#9fb8aa` | Dividers / stronger boundaries |

Font stack: Segoe UI, Tahoma, Geneva, Verdana, system sans-serif. Use semantic status colors for warnings/errors rather than changing the brand. No new lime branding, gradients, or glass chrome. Square corners and disabled transitions are present globally. Preserve visible keyboard focus despite the global suppression of focus shadows. Dark mode remaps `--site-*` to a charcoal workspace and keeps the green sidebar; see `docs/AGENTDESIGN.md`. Light token values must not change when editing dark.

Shell: grouped dark sidebar, bottom account/photo controls, consistent page title and utilities. Desktop/tablet/mobile layouts share the same visual language. Use compact statistics strips, purposeful sections and readable tables instead of a card around every number. Document screens use breadcrumbs, folder choices, a consolidated toolbar and file list. Keep search scopes obvious.

Forms: align labels and controls, show validation next to the relevant field, distinguish fixed account details from editable inputs, and preserve entered values on recoverable failures. Login is a split identity/sign-in layout; school/university branding appears in the identity panel and Employee portal in the header. Profile now uses the `profile-panel` components, not the old uneven password grid.

Modals: use shared shell/header/body/footer patterns, visible close action, bounded scrolling and restrained backdrop. Course edits, request-management actions, and load lifecycle confirmations use native `dialog.ui-dialog`; management/history pages remain separate from upload. Keep upload destination and file-entry steps intact. Teacher's Load retains its wider dedicated editing workspace. Compact error dialog is 420px maximum width, with approximately 1.05rem title and .875rem body text, two-action capacity, and a subtle shadow. Do not apply that small width to complex forms.

## Error handling

Current shared UI: `resources/js/site-request.js`, `resources/views/partials/ui-error-dialog.blade.php`; server helper: `app/Support/ApiError.php`. `SiteRequest` checks JSON/content status and maps session expiry, forbidden access, rate limiting, and request errors to compact feedback. It provides focus trapping/restoration and optional support references. Callers still need network-error handling; it is not a universal interceptor for every fetch.

Use dialogs for blocked actions, inline feedback for field validation and ordinary search empty/error states, and unobtrusive handling for background refreshes. Keep diagnostic details in logs. Request retry must account for an unknown write outcome. Custom 403/404/419/500/503 templates exist for full-page failures.

Upload controllers have `PreventsDuplicateUpload`: an optional client token is guarded in cache for one hour. This is not a complete durable per-file retry ledger. Ordinary document uploads gained per-file database transactions/cleanup; upload branches and version/report paths need separate review. Photo replacement now stores the new image before removing the old one. Verify failure paths before claiming all operations are atomic.

## Operations and verification

Local setup and commands are in `docs/SETUP.md` (root `README.md` only points here). Do not copy real credentials or `.env` contents into documentation. Source-visible configuration is in `config/`; uploads go through `UploadStorage` and the configured local/object-storage disk. Production persistence must be checked on the host, not inferred from a successful local upload.

Scheduled tasks in `routes/console.php`: academic folders on August 10, recycle purge daily at 02:00, content indexing hourly without overlap. Scheduler/worker runtime and timezone must be verified in deployment; the user's documentation timezone is Asia/Manila. OCR on scanned files needs Poppler and Tesseract on the worker host; missing binaries should fail indexing without blocking uploads.

Important migrations include managed categories (`2026_09_23_000001_add_managed_document_categories`) and extra Coordinator programs (`2026_10_01_000001_create_employee_programs_table`). Inspect all pending migrations, including historical data-changing ones, before production execution. Category rollback deliberately rejects removal when managed records exist; prefer a forward fix or coordinated database/application/storage recovery.

Release `86cd234` requires `database/migrations/2026_10_21_000001_add_workflow_correction_history.php`. It adds `teacher_load_revisions`, `document_request_changes`, and `course_changes`; published-load revision/draft/void fields; request concurrency and exemption fields; and course concurrency/manual-unit flags. It does not seed employee assignments or schedules. Earlier Teacher's Load, request, and course-unit migrations must already be present. See [the release checklist](SETUP.md#workflow-corrections-release-86cd234). Production execution has not been verified. Rolling this migration back removes the new history/draft data; use a reviewed forward fix rather than rollback for an ordinary UI problem.

`2026_09_10_000001_ensure_it_dean_account` resets the `dean` username, profile fields, and password when it runs. Do not include it in a blanket production migrate unless that account reset is intended. Prefer path-specific artisan migrate for unrelated feature tables when that migration is still pending on purpose.

`composer test` clears configuration, runs Rector in dry-run mode, and runs Laravel tests. **Never run `composer test` on Laravel Cloud / production MySQL.** `phpunit.xml` uses in-memory SQLite, array cache/session/mail, and synchronous queues. `npm run build` builds frontend assets. Some MySQL-only behavior may be skipped; read the reported reason and do not count skips as passing verification. `tests/Feature/CoordinatorProgramsTest.php`, `DocumentSearchAuthorizationTest.php`, `TeacherLoadTest.php`, and `ProgramStructureTest.php` are useful entry points.

Implementation verification for `86cd234`: `php artisan test` reported **94 passed, 1 existing MySQL-only skip (718 assertions)**; Vite build and a targeted Rector dry-run over the touched workflow services/controllers passed. Entry points include `DocumentRequestManagementTest`, `CourseMetadataTest`, `TeacherLoadTest`, `ProfileEditTest`, and `ArchiveContentTest`. This documentation refresh verifies paths, links, factual claims, and the diff only; it does not rerun application tests, execute migrations, or verify production/browser behavior.

## Extracted ops notes

Local WAMP repair (2026-10-21): the reported upload 500 was a missing `document_requests.lock_version`; legacy MyISAM tables allowed partial writes despite the surrounding transaction and blocked new foreign keys. With approval, `users`, `document_requests`, `document_request_recipients`, `documents`, `document_recipients`, `notifications`, `document_search_indexes`, `document_versions`, and `dashboard_logs` were converted to InnoDB without deleting rows/files. Workflow migration `2026_10_21_000001_add_workflow_correction_history` then completed as batch 9; local `migrate:status` showed no pending migrations. The original reported submission remained submitted with its linked file present. The migration can resume existing table/column groups and repair missing foreign keys/unique index after interrupted MySQL DDL. These findings/actions are local only; do not assume Cloud engines or migration status match.

Pulled from archived guides so those files are no longer required reading. Re-verify in code before treating a backlog item as current work.

**Frontend packages already local.** `package.json` includes `@fortawesome/fontawesome-free` (imported from `resources/css/app.css`), `chart.js` (`resources/js/chart-loader.js`), and `@fullcalendar/*` (`resources/js/calendar-loader.js`). Do not reintroduce CDN copies of those libraries. After CSS or JS changes, run `npm run build` and hard-refresh.

**Dashboard cache.** Dean/Coordinator/Faculty dashboard stats commonly use `Cache::remember` with a 5-minute TTL; some analytics keys use 10–15 minutes. Stale numbers after a data fix usually need `php artisan cache:clear`, not a UI rewrite.

**Production cache commands (host-specific).** Laravel Cloud / production hosts may use `php artisan optimize:clear` then `config:cache`, `route:cache`, and `view:cache` after a deploy. Confirm the host process before running them. Do not cache config/routes as a substitute for a local `composer test`.

**Leave and leftover schema.** `leave_requests` / leave-balance models and migrations exist, and some widgets still mention pending leaves, but `routes/` has no leave routes and there is no `resources/views/leave/` tree in this checkout. Treat leave as **partial schema, not a shipped workflow**. Archived “Leave Management COMPLETE” claims are false for current routing. Leave-balance year reset has no verified scheduler job.

**Aspirational backlog (historical KB).** Older notes proposed email/SMTP reminders, iCal export, recurring events, Pusher live updates, service-worker/offline support, Redis, and extra bulk actions. Several items on that list already have later implementations (document search/filters, in-app preview, versioning, Dean employee administration). Confirm each idea against current routes and services; do not implement from the archived recipe files.

**Historical implementation recipes.** `docs/archive/legacy/MD FOLDERS/FEATURES_IMPLEMENTATION_GUIDE.md` contains old model/UI snippets (comments, skills, toasts, Pusher). Use it only as archaeology. Current models, Blade, and `package.json` are authoritative.

## Known gaps and verification boundaries

- Search matches the immediate folder name/slug, not every ancestor name. Managed-category naming and breadcrumb matching need explicit coverage before promising full-path search.
- `all` search can include multiple years without an explicit year filter; do not describe it as active-year-only.
- Indexer-caught failures may not trigger job retries despite configured backoff.
- Upload batching returns a success count, not a full failed-file list. Optional token caching is not comprehensive recovery from partial uploads; ordinary and academic upload branches differ.
- New shared error infrastructure does not prove every fetch caller handles network errors, modal nesting, or session redirects consistently.
- Folder privacy scopes inspect stored folder privacy flags; descendant/category propagation and migration readiness require direct verification. Do not assert blanket privacy guarantees from one helper alone.
- Version/report storage cleanup and stale indexing-job behavior still require dedicated failure-path verification; the latest commit does not establish all earlier prompt requirements as complete.
- The October 21 audit's account/session and permission findings are still open: deactivation lacks an explicit ongoing active-status/session-revocation gate; Reports and Professional Development have broader Coordinator checks than handled-program scoping; Recycle Bin listing/restore needs a private-folder authorization review. These were not included in approved items 1/3/5.
- Ordinary document replacement still has a separate path from request corrections. Consolidating approval, file-mirror, authorization, and indexing behavior remains outstanding; workflow corrections in `86cd234` did not fix that path.
- Only audit feature items **1 (load corrections), 3 (request management), and 5 (catalog editing)** were approved and implemented. Term-specific assignments, task editing/reassignment/cancellation, sharing-management controls, report corrections, and expanded folder/training recovery remain recommendations, not shipped functions.
- Production migrations, OCR binaries, scheduler/worker health, fresh browser checks and remote deployment state were not verified in this pass.

## Documentation map

**Official reading order:** `docs/SETUP.md` (clone) → `docs/AGENTS.md` (workflow) → this guide (behavior) → `docs/AGENTDESIGN.md` (UI) → named PHP/Blade/JS files. Employee-facing help is `resources/views/user-guide.blade.php`. Category deploy/rollback is `docs/runbooks/dynamic-categories-deployment.md`. Folder map: `docs/README.md`.

**Archive policy:** files under `docs/archive/legacy/` are historical copies, not competing instructions. On 2026-10-02 the user removed five duplicate guides (`QUICK_START.md`, `SETUP_GUIDE.md`, `IMPLEMENTATION_COMPLETE.md`, `NEW_FEATURES_SUMMARY.md`, `TAILWIND_REFACTORING_GUIDE.md`). Those remain recoverable from Git. Do not delete the remaining archive files unless the user names them. Vendor/package docs and licenses are outside this map. Ignore missing `MEMORY.md` links inside old files.

| Current path | Role |
|---|---|
| `docs/AGENTDESIGN.md` | Current UI / design system (follow this, not the archive copy) |
| `docs/SYSTEM_GUIDE.md` | Main behavior / authorization reference |
| `docs/AGENTS.md` | Full agent rules (same intent as root stub) |
| `docs/SETUP.md` | Local setup and command index |
| `docs/README.md` | Folder map |
| `AGENTS.md` (repo root) | Cursor entry stub |
| `README.md` (repo root) | GitHub pointer into `docs/` |
| `docs/runbooks/dynamic-categories-deployment.md` | Category migration/rollback |
| `docs/archive/legacy/` | Historical Markdown only |

Remaining archive (still in tree): old lime `AGENTDESIGN.md`; `MD FOLDERS/UI_COLOR_LATEST_UPDATE.md`, `DOCUMENT_REQUEST_SEARCH.md`, `FEATURES_IMPLEMENTATION_GUIDE.md`, `PERFORMANCE_OPTIMIZATION.md`; `EMP-Dashboard-KnowledgeBase/KNOWLEDGE_BASE.md`. Git history remains the recovery path. Do not copy credentials from old guides.

## Change history

Dates below are Git commit dates, not assertions of production deployment. This is a compact milestone record; use Git for every intermediate code change.

| Date | Commit | Change and current relevance |
|---|---|---|
| 2026-09-09 | `d215454`, `670fc94` | Clickable notifications/versioning/preview and Dean operational dashboard |
| 2026-09-13 | `6cbb7db` | Institutional white/dark-green UI baseline |
| 2026-09-14 | `776a363`, `d6283c2` | Text insights, activity-log refinement and sidebar account controls |
| 2026-09-14 | `442afe0`, `61e4490` | Request filing workflow and privacy-control refinements |
| 2026-09-23 | `e111675`, `36ed5e7` | Guided upload and managed personal/system categories; category migration required |
| 2026-09-24 | `5fc7f8b`, `0547938` | SITE program structure, term-guided assignments, Teacher's Load and faculty classification |
| 2026-09-29 | `3ada45a`, `3a9cb63` | Catalog units, load navigation, dialogs, Coordinator Faculty creation and responsive workspace |
| 2026-10-01 | `983590b` | Profile redesign; Rector dry-run added to test command |
| 2026-10-01 | `fe62b4a` | Home plus extra handled programs; `employee_programs` migration; assignments configured by Dean |
| 2026-10-01 | `839a07d` | Shared document search, archive scope and compact error infrastructure; limitations recorded above |
| 2026-10-02 | `34d1692` | Living docs in `docs/`: `SYSTEM_GUIDE.md`, `AGENTDESIGN.md`, `AGENTS.md`, `SETUP.md`. Legacy Markdown archived. No deletions. |
| 2026-10-02 | `49a50a4` | Dark mode tokens remapped to charcoal workspace + green sidebar/accent only. Light `--site-*` values unchanged. |
| 2026-10-02 | `bbb45f0` | Removed five duplicate archived Markdown guides. Remaining archive files kept. Recoverable from Git. |
| 2026-10-21 | `77c2ad8` | Archived-year late uploads, folders, and rename actions with year-bound endpoints and existing academic approval; no migration. Local verification: 83 passed, 1 MySQL-only skip; Vite build passed. Production/browser behavior unverified. |
| 2026-10-21 | `788be16` | Current teaching assignments on own Faculty/Coordinator profile and scoped Teacher's Load views; existing assignments need no schedule/backfill, published schedules come from the current finalized load. Manager subject-editor links and bulk subject rows with empty schedules. No migration. Profile/TeacherLoad tests: 14 passed; build passed. Browser/deployment unverified. |
| 2026-10-21 | `86cd234` | Approved audit items 1/3/5: load revisions/history/voiding and draft removal; author-managed request corrections; scoped course metadata edits/history and protected manual units. Requires `2026_10_21_000001_add_workflow_correction_history`. Local verification: 94 passed, 1 existing MySQL-only skip; Vite build and targeted Rector check passed. Pushed to V2/V3 main; deployment/migration execution unverified. |
| 2026-10-21 | uncommitted (docs only) | Refreshed source baseline, verified release IDs, topic routing, modal guidance, schema/release instructions, and the boundary between implemented corrections and unapproved audit recommendations. Checked documentation paths/links and diff; no application or production operations. |
| 2026-10-21 | uncommitted | Made the shared Favorite documents navigation explicit with a star, count, and cross-folder explanation for Dean, Coordinator, and Faculty. Reuses existing personal favorites and authorization; no migration. Vite build and Blade compilation passed; browser behavior not verified. |

| 2026-10-21 | uncommitted | Teacher's Load save/finalize preserves JSON/CSRF headers; validation identifies steps/rows/schedules and links to controls; finalization prechecks missing schedules/empty rows, while server failures remain distinct. Clearer server field labels, no authorization/schema change. TeacherLoadTest: 11 passed (117 assertions); Vite build, JS/PHP syntax, API smoke checks, and diff checks passed. No WAMP migration executed; browser/deployment not verified. |

| 2026-10-21 | uncommitted | Request recipient choices refresh on modal open/manual refresh from a non-cached authorized endpoint using shared eligibility; searchable names/usernames and match counts, preserved eligible selections, delegated course-picker updates, retry feedback. No schema or account changes. DocumentRequest tests: 12 passed (83 assertions); build, Blade compilation, and diff checks passed. Browser/deployment not verified. |

| 2026-10-21 | uncommitted | Added explicit Done/Change recipient selection with live names/count; optional timezone-labeled deadline with inline validation and in-modal recovery preserving inputs. Role/academic checks unchanged; no migration. DocumentRequest tests: 14 passed (99 assertions); recipient/deadline JS smoke checks, build, Blade compilation, and diff checks passed. Browser/deployment not verified. |

| 2026-10-21 | uncommitted | Fixed local request-upload 500 via approved InnoDB repair and completed workflow migration (batch 9); preserved original submission/file. Added resumable migration constraints, explicit submit redirects, safe repeat submissions, browser validation redirects, and double-click guard. Local schema/file checks passed; targeted workflow tests/build/Blade checks recorded in handoff. Cloud/browser end-to-end behavior not verified. |

Maintenance: after an authorized change, update current behavior first, then append `Date | actual commit or uncommitted | workflow/roles + migration/config + checks/limitations`. Keep Quick Context short, link to code instead of copying implementations, and do not append whole prompts or chat transcripts. For docs-only commits, the baseline remains the latest implementation commit audited; do not chase the document's own commit hash.
