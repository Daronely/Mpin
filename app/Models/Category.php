<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Category
{
    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM categories ORDER BY sort_order, name');
    }

    public static function bySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }

    /** Seed the default categories (idempotent). */
    public static function seedDefaults(): void
    {
        $defaults = [
            ['gaming',            'Gaming',            'gamepad'],
            ['technology',        'Technology',        'cpu'],
            ['programming',       'Programming',       'code'],
            ['operating-systems', 'Operating Systems', 'monitor'],
            ['hardware',          'Hardware',          'chip'],
            ['music',             'Music',             'music'],
            ['lifestyle',         'Lifestyle',         'heart'],
            ['education',         'Education',         'book'],
            ['communities',       'Communities',       'users'],
            ['other',             'Other',             'tag'],
        ];
        foreach ($defaults as $i => [$slug, $name, $icon]) {
            Database::run(
                'INSERT IGNORE INTO categories (slug, name, icon, sort_order) VALUES (?,?,?,?)',
                [$slug, $name, $icon, $i]
            );
        }
    }
}
