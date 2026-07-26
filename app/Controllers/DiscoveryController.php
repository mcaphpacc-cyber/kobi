<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DiscoveryService;
use App\Controllers\BodyPartController;

class DiscoveryController extends Controller
{
    public function __construct(
        private DiscoveryService $service
    ) {
    }
    public function index()
    {
        $dashboard =
            $this->service
                 ->getDashboard();

        return $this->view(
            'discovery/index',
            compact('dashboard')
        );
    }
}