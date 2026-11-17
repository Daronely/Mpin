<?php
/**
 * MPIN — inline SVG icon set (stroke icons, currentColor).
 * Guarded so it is safe to include from multiple templates.
 */
declare(strict_types=1);

if (!function_exists('mpin_icon')):

function mpin_icon(string $name, int $size = 18, string $class = ''): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = [
            'pin'        => '<path d="M12 21s-7-5.1-7-11a7 7 0 0 1 14 0c0 5.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
            'gamepad'    => '<path d="M6 12h4M8 10v4M15 11h.01M18 13h.01"/><rect x="2" y="6" width="20" height="12" rx="6"/>',
            'cpu'        => '<rect x="6" y="6" width="12" height="12" rx="2"/><rect x="10" y="10" width="4" height="4"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
            'code'       => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4"/>',
            'monitor'    => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
            'drive'      => '<path d="M22 13H2"/><path d="M5.5 5.1 2 13v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5l-3.5-7.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/><path d="M6 17h.01M10 17h.01"/>',
            'music'      => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
            'heart'      => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/>',
            'book'       => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
            'users'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'tag'        => '<path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.42 0l8.58-8.58a1 1 0 0 0 0-1.42L12 2z"/><circle cx="7" cy="7" r="1.5"/>',
            'search'     => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/>',
            'plus'       => '<path d="M12 5v14M5 12h14"/>',
            'x'          => '<path d="M18 6 6 18M6 6l12 12"/>',
            'check'      => '<path d="M20 6 9 17l-5-5"/>',
            'share'      => '<circle cx="6" cy="12" r="3"/><circle cx="18" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><path d="m8.7 10.7 6.6-3.4M8.7 13.3l6.6 3.4"/>',
            'sun'        => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
            'moon'       => '<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/>',
            'user'       => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'settings'   => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
            'globe'      => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18z"/>',
            'calendar'   => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 9h18"/>',
            'edit'       => '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
            'trash'      => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
            'eye'        => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
            'lock'       => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'flag'       => '<path d="M4 22V4s1-1 4-1 5 2 8 2 4-1 4-1v13s-1 1-4 1-5-2-8-2-4 1-4 1z"/>',
            'download'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
            'filter'     => '<path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>',
            'zap'        => '<path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/>',
            'arrow-right'=> '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'bell'       => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
            'layers'     => '<path d="m12 2 10 5.5-10 5.5L2 7.5 12 2z"/><path d="m2 12.5 10 5.5 10-5.5M2 17.5 12 23l10-5.5"/>',
            'chart'      => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
            'star'       => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/>',
            'map'        => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>',
            'sparkles'   => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15z"/>',
            'shield'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
            'external'   => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
            'copy'       => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'info'       => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
            'send'       => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
            'list'       => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
            'grid'       => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'fire'       => '<path d="M12 22c4.4 0 8-3.6 8-8 0-3.5-2-6.5-4-8.5.5 2.5-.5 4.5-2 5.5C14 8 13 5 10 2c0 4-1.5 6-3 8-1.2 1.6-2 3-2 4.5 0 4.1 3.6 7.5 7 7.5z"/>',
            'home'       => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10z"/><path d="M9 22V12h6v10"/>',
            'compass'    => '<circle cx="12" cy="12" r="9"/><path d="m16 8-2.5 5.5L8 16l2.5-5.5L16 8z"/>',
            'message'    => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10z"/>',
            'key'        => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m11 12 9-9M17 6l3 3M14 9l2 2"/>',
            'link'       => '<path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/>',
        ];
    }
    $body = $icons[$name] ?? $icons['pin'];
    $cls = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<svg' . $cls . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
         . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . $body . '</svg>';
}

function mpin_logo(int $size = 32, string $id = 'lg'): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" fill="none" aria-hidden="true">'
         . '<defs><linearGradient id="' . $id . 'g" x1="0" y1="0" x2="48" y2="48">'
         . '<stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#3b82f6"/></linearGradient></defs>'
         . '<rect width="48" height="48" rx="12" fill="url(#' . $id . 'g)"/>'
         . '<path d="M24 9.5c-6.35 0-11.5 5.15-11.5 11.5 0 8.6 11.5 17.5 11.5 17.5S35.5 29.6 35.5 21c0-6.35-5.15-11.5-11.5-11.5z" fill="#fff"/>'
         . '<circle cx="24" cy="21" r="4.2" fill="url(#' . $id . 'g)"/>'
         . '</svg>';
}
endif;
