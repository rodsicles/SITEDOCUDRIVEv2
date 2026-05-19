<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $globalSearch
    ) {}

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        if (mb_strlen($query) < 3) {
            return response()->json([]);
        }

        return response()->json(
            $this->globalSearch->search(auth()->user(), $query)
        );
    }
}
