<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BodyPartService;

class BodyPartController extends Controller
{
    public function __construct(
        private BodyPartService $service
    ) {
    }

    public function index()
    {
        $page =
            $this->service
                 ->index();

        return $this->view(
            'body-parts/index',
            compact('page')
        );
    }

    public function show(
        string $slug
    ) {
        $page =
            $this->service
                 ->show($slug);

        if ($page === null) {

            abort(404);

        }

        return $this->view(
            'body-parts/show',
            compact('page')
        );
    }
}