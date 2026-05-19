<?php

namespace App\Support;

use Illuminate\Http\Request;

class DocumentListSortUrls
{
    public static function sortHref(
        string $routeName,
        Request $request,
        mixed $folderFilter,
        string $tab,
        string $sort,
    ): string {
        $params = $request->except('page');

        if ($sort === 'date') {
            unset($params['sort']);
        } else {
            $params['sort'] = $sort;
        }

        $params['folder'] = $folderFilter;
        $params['tab'] = $tab;

        return route($routeName, self::filterParams($params));
    }

    public static function typeHref(
        string $routeName,
        Request $request,
        mixed $folderFilter,
        string $tab,
        ?string $fileType,
    ): string {
        $params = $request->except('page', 'file_type');

        if ($fileType) {
            $params['file_type'] = $fileType;
        }

        $params['folder'] = $folderFilter;
        $params['tab'] = $tab;

        return route($routeName, self::filterParams($params));
    }

    public static function yearHref(
        string $routeName,
        Request $request,
        mixed $folderFilter,
        string $tab,
        string $year,
    ): string {
        $params = $request->except('page');

        if ($year === '') {
            unset($params['academic_year']);
        } else {
            $params['academic_year'] = $year;
        }

        $params['folder'] = $folderFilter;
        $params['tab'] = $tab;

        return route($routeName, self::filterParams($params));
    }

    public static function resetHref(string $routeName, mixed $folderFilter, string $tab, ?Request $request = null): string
    {
        $params = [
            'folder' => $folderFilter,
            'tab' => $tab,
        ];

        if ($request) {
            $search = $request->input('search') ?? $request->input('name');
            if ($search) {
                $params['search'] = $search;
            }
        }

        return route($routeName, self::filterParams($params));
    }

    private static function filterParams(array $params): array
    {
        return array_filter($params, static fn ($value) => $value !== null && $value !== '');
    }
}
