<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class PageController
{
    public function about(): void
    {
        View::render('pages/about', ['title' => __('about.title') . ' — Mpin', 'active' => 'about']);
    }

    public function privacy(): void
    {
        View::render('pages/privacy', ['title' => __('privacy.title') . ' — Mpin', 'active' => '']);
    }

    public function terms(): void
    {
        View::render('pages/terms', ['title' => __('terms.title') . ' — Mpin', 'active' => '']);
    }

    /** Switch UI language (/lang/en · /lang/fa). */
    public function switchLang(string $lang): void
    {
        \App\Core\I18n::set($lang);
        $back = (string)($_GET['back'] ?? '/');
        // only same-site paths, no protocol tricks
        if (!preg_match('#^/[a-zA-Z0-9\-_/%.?]*$#', $back) || str_contains($back, '//')) {
            $back = '/';
        }
        redirect($back);
    }
}
