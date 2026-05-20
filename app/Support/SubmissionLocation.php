<?php

namespace App\Support;

use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\User;
use App\Services\AcademicHierarchyService;
use Illuminate\Support\Str;

final class SubmissionLocation
{
    public static function folderBreadcrumb(?Folder $folder): string
    {
        if (!$folder) {
            return '';
        }

        if (!$folder->relationLoaded('parent')) {
            $folder->load('parent.parent.parent');
        }

        return collect($folder->getAncestors())
            ->pluck('folder_name')
            ->push($folder->folder_name)
            ->implode(' › ');
    }

    public static function documentsUrl(User $user, ?Folder $folder): ?string
    {
        if (!$folder) {
            return null;
        }

        $hierarchy = app(AcademicHierarchyService::class);
        $tab = 'accreditation';

        if ($hierarchy->isUnderTgCategory($folder)) {
            $tab = 'teaching-guides';
        } elseif ($hierarchy->isUnderEqCategory($folder)) {
            $tab = 'exam-questionnaires';
        } else {
            $top = $folder->top_level_category ?? null;
            if ($top) {
                $tab = Str::slug($top);
            }
        }

        $routePrefix = match (true) {
            $user->isFaculty() => 'faculty',
            $user->isProgramCoordinator() => 'coordinator',
            $user->isDeanOrSecretary() => 'dean',
            default => 'faculty',
        };

        return route($routePrefix.'.documents', [
            'tab' => $tab,
            'folder' => $folder->folder_id,
        ]);
    }

    public static function examQuestionnairePath(ExamQuestionnaire $questionnaire): string
    {
        $questionnaire->loadMissing('document.folder.parent.parent');
        $folder = $questionnaire->document?->folder;

        if ($folder) {
            return self::folderBreadcrumb($folder);
        }

        $parts = array_filter([
            'Exam Questionnaires',
            $questionnaire->academic_year,
            $questionnaire->semester ? $questionnaire->semester.' Semester' : null,
            strtoupper((string) ($questionnaire->submission_type ?? 'toq')),
            $questionnaire->subject,
        ]);

        return implode(' › ', $parts);
    }

    public static function examQuestionnaireDocumentsUrl(User $user, ExamQuestionnaire $questionnaire): string
    {
        $questionnaire->loadMissing('document.folder');
        $folder = $questionnaire->document?->folder;

        if ($folder) {
            return self::documentsUrl($user, $folder) ?? route(self::routePrefix($user).'.documents', ['tab' => 'exam-questionnaires']);
        }

        return route(self::routePrefix($user).'.documents', ['tab' => 'exam-questionnaires']);
    }

    private static function routePrefix(User $user): string
    {
        return match (true) {
            $user->isFaculty() => 'faculty',
            $user->isProgramCoordinator() => 'coordinator',
            $user->isDeanOrSecretary() => 'dean',
            default => 'faculty',
        };
    }
}
