<?php

declare(strict_types=1);

namespace App\Controllers;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title' => 'KOBI Core Framework',
            'version' => config('version')
        ]);
    }
}