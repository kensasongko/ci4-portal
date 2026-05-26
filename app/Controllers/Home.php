<?php

namespace App\Controllers;

use App\Models\Mapplication;

class Home extends BaseController
{
    public function index(): string
    {
        $applicationModel = new Mapplication();

        return view('welcome_message', [
            'user'         => session('user'),
            'applications' => $applicationModel->getActiveApplications(),
        ]);
    }
}
