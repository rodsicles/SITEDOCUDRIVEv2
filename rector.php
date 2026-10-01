<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

// Check only: `vendor/bin/rector process --dry-run` (part of `composer test`).
// To apply a suggestion: remove its entry from the baseline below, run `vendor/bin/rector process`,
// review the diff, then run `composer test`.
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/routes',
        __DIR__.'/database',
    ])
    ->withPhpSets(php82: true)
    ->withPreparedSets(deadCode: true)
    ->withCache(__DIR__.'/storage/framework/cache/rector')
    // Baseline: suggestions found in existing code on 2026-10-01, awaiting review.
    // New code is still checked against every rule.
    ->withSkip([
        \Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [
            __DIR__.'/app/Http/Controllers/DocumentCategoryController.php',
            __DIR__.'/app/Http/Controllers/ExamQuestionnaireController.php',
            __DIR__.'/app/Http/Controllers/TeachingGuideController.php',
        ],
        \Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector::class => [
            __DIR__.'/app/Http/Controllers/TeachingGuideController.php',
            __DIR__.'/app/Services/DocumentService.php',
            __DIR__.'/app/Services/ExamRecordService.php',
            __DIR__.'/app/Services/NotificationService.php',
            __DIR__.'/app/Services/RecycleBinService.php',
            __DIR__.'/app/Services/TeachingGuideSyncService.php',
            __DIR__.'/app/Support/TaskAssigneeResolver.php',
        ],
        \Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class => [
            __DIR__.'/app/Http/Controllers/DocumentCommentController.php',
            __DIR__.'/app/Http/Controllers/DocumentRequestController.php',
            __DIR__.'/app/Http/Controllers/FolderController.php',
            __DIR__.'/app/Services/AcademicHierarchyService.php',
        ],
        \Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class => [
            __DIR__.'/app/Http/Controllers/UploadDestinationController.php',
            __DIR__.'/app/Services/DocumentService.php',
            __DIR__.'/app/Services/ExamQuestionnaireSyncService.php',
            __DIR__.'/app/Services/FacultyDocumentTreeService.php',
            __DIR__.'/app/Support/AcademicYear.php',
        ],
        \Rector\DeadCode\Rector\Closure\RemoveUnusedClosureVariableUseRector::class => [
            __DIR__.'/app/Services/DashboardService.php',
        ],
        \Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class => [
            __DIR__.'/app/Http/Middleware/DefenseAwareThrottle.php',
        ],
        \Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector::class => [
            __DIR__.'/database/seeders/SystemFolderSeeder.php',
        ],
        \Rector\Php74\Rector\Assign\NullCoalescingOperatorRector::class => [
            __DIR__.'/app/Http/Controllers/DocumentSearchController.php',
            __DIR__.'/app/Models/LeaveBalance.php',
        ],
        \Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class => [
            __DIR__.'/app/Services/SubmissionAnalyticsService.php',
            __DIR__.'/app/Services/DocumentSearchService.php',
        ],
        \Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class => [
            __DIR__.'/app/Http/Controllers/DocumentCommentController.php',
            __DIR__.'/app/Http/Controllers/DocumentRequestController.php',
            __DIR__.'/app/Http/Controllers/FolderController.php',
            __DIR__.'/app/Services/AcademicHierarchyService.php',
            __DIR__.'/app/Services/DocumentSearchService.php',
        ],
        \Rector\DeadCode\Rector\Closure\RemoveUnusedClosureVariableUseRector::class => [
            __DIR__.'/app/Services/DashboardService.php',
            __DIR__.'/app/Services/DocumentSearchService.php',
        ],
        \Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector::class => [
            __DIR__.'/app/Services/DocumentSearchService.php',
        ],
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector::class => [
            __DIR__.'/app/Http/Controllers/DocumentSearchController.php',
            __DIR__.'/database/seeders/CourseSeeder.php',
        ],
        \Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class => [
            __DIR__.'/app/Http/Controllers/CalendarController.php',
            __DIR__.'/app/Http/Requests/UpdateFolderRequest.php',
            __DIR__.'/app/Services/DashboardService.php',
            __DIR__.'/app/Services/ExamRecordService.php',
            __DIR__.'/app/Services/FolderService.php',
            __DIR__.'/app/Services/NotificationService.php',
            __DIR__.'/routes/web.php',
        ],
        \Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector::class => [
            __DIR__.'/app/Http/Controllers/CalendarController.php',
            __DIR__.'/app/Http/Controllers/DocumentCategoryController.php',
            __DIR__.'/app/Http/Controllers/TeacherLoadController.php',
            __DIR__.'/app/Services/DocumentPurgeService.php',
            __DIR__.'/app/Services/FacultyDocumentTreeService.php',
            __DIR__.'/app/Services/StorageQuotaService.php',
            __DIR__.'/database/migrations/2026_05_06_000002_backfill_file_size_for_documents.php',
            __DIR__.'/database/migrations/2026_05_21_190100_soften_email_in_users_table.php',
        ],
        \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
            __DIR__.'/app/Http/Controllers/ForgotPasswordController.php',
            __DIR__.'/app/Http/Controllers/PasswordResetRequestController.php',
            __DIR__.'/app/Http/Controllers/TeacherLoadController.php',
        ],
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class => [
            __DIR__.'/database/migrations/2026_09_24_000001_establish_current_program_structure.php',
            __DIR__.'/database/migrations/2026_09_24_000002_add_course_term_metadata_and_seed_curricula.php',
        ],
    ]);
