<?php

namespace App\Http\Controllers;

use App\Support\Faqs;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __invoke(Faqs $faqs): Response
    {
        return Inertia::render('Faq', [
            'faqs' => $faqs->all(),
        ]);
    }
}
