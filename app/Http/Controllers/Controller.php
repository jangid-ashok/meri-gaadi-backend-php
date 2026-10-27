<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function paginateRequest(Request $request, $query)
    {
        $perPage = $request->integer('per_page', 15);

        if (! in_array($perPage, [10, 15, 20, 50, 100], true)) {
            $perPage = 15;
        }

        $page = max(1, $request->integer('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Requests can become stale after filtering or deleting the final row on a page.
        if ($page > $paginator->lastPage()) {
            $paginator = $query->paginate($perPage, ['*'], 'page', $paginator->lastPage());
        }

        return $paginator->withQueryString();
    }
}
