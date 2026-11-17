<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class PageController
{
    public function about(): void
    {
        View::render('pages/about', ['title' => 'About Mpin', 'active' => 'about']);
    }

    public function privacy(): void
    {
        View::render('pages/privacy', ['title' => 'Privacy Policy — Mpin', 'active' => '']);
    }

    public function terms(): void
    {
        View::render('pages/terms', ['title' => 'Terms of Service — Mpin', 'active' => '']);
    }
}
