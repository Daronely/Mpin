<?php
declare(strict_types=1);

namespace App\Core;

/** Simple PHP view renderer with a shared layout. */
final class View
{
    public static function render(string $template, array $vars = []): void
    {
        $vars['_template'] = $template;
        extract($vars, EXTR_SKIP);
        // Render the template FIRST so it can define $vendor / $extraJs /
        // $wide for the layout (header needs $vendor for its CSS links).
        ob_start();
        require MPIN_APP . '/Views/' . $template . '.php';
        $content = ob_get_clean();
        require MPIN_APP . '/Views/layout/header.php';
        echo $content;
        require MPIN_APP . '/Views/layout/footer.php';
    }

    /** Render a partial template inside a view (does not flush). */
    public static function partial(string $name, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);
        require MPIN_APP . '/Views/partials/' . $name . '.php';
    }
}
