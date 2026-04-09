<?php

namespace App\Services;

use App\Models\SkillTag;
use App\Models\DashboardLog;
use Illuminate\Support\Collection;

class SkillTagService
{
    public function getTagsForUser(int $userId): Collection
    {
        return SkillTag::where('user_id', $userId)->orderBy('tag_name')->get();
    }

    public function addTag(int $userId, string $tagName): SkillTag
    {
        $tag = SkillTag::create([
            'user_id' => $userId,
            'tag_name' => trim($tagName),
        ]);

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Added skill tag: ' . $tagName,
            'activity_type' => 'skill_tag_added',
            'log_date' => now(),
        ]);

        return $tag;
    }

    public function deleteTag(int $tagId, int $userId): void
    {
        $tag = SkillTag::where('skill_tag_id', $tagId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $tagName = $tag->tag_name;
        $tag->delete();

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Removed skill tag: ' . $tagName,
            'activity_type' => 'skill_tag_removed',
            'log_date' => now(),
        ]);
    }

    public function getAllFacultySkillsSummary(): Collection
    {
        return SkillTag::selectRaw('tag_name, COUNT(*) as faculty_count')
            ->join('users', 'skill_tags.user_id', '=', 'users.id')
            ->where('users.role_id', 3)
            ->groupBy('tag_name')
            ->orderByDesc('faculty_count')
            ->get()
            ->map(function ($tag) {
                $tag->faculty_members = SkillTag::where('tag_name', $tag->tag_name)
                    ->join('users', 'skill_tags.user_id', '=', 'users.id')
                    ->join('employees', 'users.id', '=', 'employees.user_id')
                    ->where('users.role_id', 3)
                    ->pluck('employees.full_name')
                    ->implode(', ');
                return $tag;
            });
    }
}
