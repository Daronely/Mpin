<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\Category;
use App\Models\Poll;

final class ExploreController
{
    public function index(): void
    {
        $list = Poll::listPublic([
            'sort'     => $_GET['sort'] ?? 'latest',
            'q'        => trim((string)($_GET['q'] ?? '')),
            'category' => trim((string)($_GET['category'] ?? '')),
            'country'  => trim((string)($_GET['country'] ?? '')),
            'page'     => (int)($_GET['page'] ?? 1),
            'per'      => 12,
        ]);

        View::render('explore', [
            'title'       => 'Explore polls — Mpin',
            'active'      => 'explore',
            'list'        => $list,
            'categories'  => Category::all(),
            'countries'   => Poll::countries(100),
            'filters'     => [
                'sort'     => in_array($_GET['sort'] ?? '', ['latest', 'popular', 'trending'], true) ? $_GET['sort'] : 'latest',
                'q'        => trim((string)($_GET['q'] ?? '')),
                'category' => trim((string)($_GET['category'] ?? '')),
                'country'  => trim((string)($_GET['country'] ?? '')),
            ],
        ]);
    }

    public function globalMap(): void
    {
        View::render('map', [
            'title'  => 'Global Pulse — Mpin',
            'active' => 'map',
            'stats'  => Poll::globalStats(),
        ]);
    }
}
