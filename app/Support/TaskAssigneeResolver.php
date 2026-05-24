<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class TaskAssigneeResolver
{
    public const SCOPE_INDIVIDUAL = 'individual';

    public const SCOPE_DEPARTMENT_IT = 'department_it';

    public const SCOPE_DEPARTMENT_ENGINEERING = 'department_engineering';

    public const SCOPE_DEPARTMENT_SITE = 'department_site';

    public const DEPARTMENT_IT = 'Information Technology';

    public const DEPARTMENT_ENGINEERING = 'Engineering';

    /**
     * Active faculty and program coordinators eligible for Dean-assigned tasks.
     */
    public static function assignableQuery()
    {
        return User::query()
            ->with(['employee', 'role'])
            ->whereIn('role_id', [2, 3])
            ->where('status', 'Active')
            ->whereHas('employee');
    }

    /**
     * @param  list<int>  $assigneeIds
     * @return list<int>
     */
    public static function resolve(string $scope, array $assigneeIds = []): array
    {
        $ids = match ($scope) {
            self::SCOPE_DEPARTMENT_IT => self::userIdsForDepartment(self::DEPARTMENT_IT),
            self::SCOPE_DEPARTMENT_ENGINEERING => self::userIdsForDepartment(self::DEPARTMENT_ENGINEERING),
            self::SCOPE_DEPARTMENT_SITE => self::userIdsForDepartments([
                self::DEPARTMENT_IT,
                self::DEPARTMENT_ENGINEERING,
            ]),
            default => self::resolveIndividual($assigneeIds),
        };

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<int>  $assigneeIds
     * @return list<int>
     */
    protected static function resolveIndividual(array $assigneeIds): array
    {
        $assigneeIds = array_values(array_unique(array_map('intval', $assigneeIds)));
        $assigneeIds = array_filter($assigneeIds, fn ($id) => $id > 0);

        if ($assigneeIds === []) {
            return [];
        }

        return self::assignableQuery()
            ->whereIn('id', $assigneeIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    protected static function userIdsForDepartment(string $department): array
    {
        return self::userIdsForDepartments([$department]);
    }

    /**
     * @param  list<string>  $departments
     * @return list<int>
     */
    protected static function userIdsForDepartments(array $departments): array
    {
        return self::assignableQuery()
            ->whereHas('employee', fn ($q) => $q->whereIn('department', $departments))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function assignableUsersSorted(): Collection
    {
        return self::assignableQuery()
            ->get()
            ->sortBy(fn ($u) => $u->employee->full_name ?? $u->username)
            ->values();
    }
}
