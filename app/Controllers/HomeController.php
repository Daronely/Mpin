<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Category;
use App\Models\Poll;

final class HomeController
{
    public function index(): void
    {
        $stats = Poll::globalStats();
        $popular = Poll::listPublic(['sort' => 'popular', 'per' => 6])['items'];
        $trending = Poll::listPublic(['sort' => 'trending', 'per' => 3])['items'];
        $latest = Poll::listPublic(['sort' => 'latest', 'per' => 6])['items'];

        $categories = [];
        $cats = \App\Core\Database::all(
            'SELECT c.*, (SELECT COUNT(*) FROM polls p
                           WHERE p.category_id = c.id AND p.visibility = "public"
                             AND p.status != "draft") AS poll_count
             FROM categories c ORDER BY c.sort_order, c.name'
        );

        View::render('home', [
            'title'      => 'Mpin — People Answer. The World Appears.',
            'active'     => 'home',
            'stats'      => $stats,
            'popular'    => $popular,
            'trending'   => $trending,
            'latest'     => $latest,
            'categories' => $cats,
            'mapData'    => Poll::globalMap(),
        ]);
    }
}
