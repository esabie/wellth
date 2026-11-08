<?php

namespace App\Http\Controllers;

use App\Support\PrivacyPolicy;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    public function __invoke(PrivacyPolicy $privacy): Response
    {
        return Inertia::render('Privacy', $privacy->content());
    }
}
