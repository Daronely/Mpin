<?php
/**
 * MPIN — route table.
 * Returns a closure that registers every route on the router.
 */
declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\MyDataController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\CreateController;
use App\Controllers\DashboardController;
use App\Controllers\ExploreController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ParticipateController;
use App\Controllers\PollController;
use App\Controllers\ProfileController;
use App\Controllers\SettingsController;
use App\Core\Router;

return static function (Router $r): void {

    /* ---------------- pages ---------------- */
    $r->get('/',              [HomeController::class, 'index']);
    $r->get('/lang/{lang:en|fa}', [PageController::class, 'switchLang']);
    $r->get('/my-data',       [MyDataController::class, 'show']);
    $r->post('/my-data/delete', [MyDataController::class, 'delete']);
    $r->get('/explore',       [ExploreController::class, 'index']);
    $r->get('/map',           [ExploreController::class, 'globalMap']);

    $r->get('/create',        [CreateController::class, 'form']);
    $r->post('/create',       [CreateController::class, 'store']);

    $r->get('/poll/{slug}',                [PollController::class, 'show']);
    $r->get('/poll/{slug}/participate',    [ParticipateController::class, 'show']);
    $r->get('/poll/{slug}/edit',           [CreateController::class, 'editForm']);
    $r->post('/poll/{slug}/edit',          [CreateController::class, 'update']);
    $r->post('/poll/{slug}/manage',        [PollController::class, 'manage']);

    $r->get('/login',        [AuthController::class, 'loginForm']);
    $r->get('/register',     [AuthController::class, 'registerForm']);
    $r->post('/logout',      [AuthController::class, 'logout']);

    $r->get('/dashboard',                      [DashboardController::class, 'index']);
    $r->get('/dashboard/poll/{id:\d+}',        [DashboardController::class, 'poll']);
    $r->get('/dashboard/poll/{id:\d+}/export', [DashboardController::class, 'export']);

    $r->get('/u/{username}', [ProfileController::class, 'show']);

    $r->get('/settings',     [SettingsController::class, 'index']);
    $r->post('/settings/profile',   [SettingsController::class, 'updateProfile']);
    $r->post('/settings/password',  [SettingsController::class, 'updatePassword']);
    $r->post('/settings/privacy',   [SettingsController::class, 'deleteMyResponses']);

    $r->get('/about',           [PageController::class, 'about']);
    $r->get('/privacy-policy',  [PageController::class, 'privacy']);
    $r->get('/terms',           [PageController::class, 'terms']);

    /* ---------------- admin ---------------- */
    $r->get('/admin',                        [AdminController::class, 'index']);
    $r->get('/admin/{section:users|polls|categories|reports|logs|settings}', [AdminController::class, 'section']);
    $r->post('/admin/users/{id:\d+}/{action:activate|suspend|make-admin|make-user|delete}', [AdminController::class, 'userAction']);
    $r->post('/admin/polls/{id:\d+}/{action:feature|unfeature|verify|unverify|close|open|delete}', [AdminController::class, 'pollAction']);
    $r->post('/admin/categories/add',    [AdminController::class, 'categoryAdd']);
    $r->post('/admin/categories/{id:\d+}/delete', [AdminController::class, 'categoryDelete']);
    $r->post('/admin/reports/{id:\d+}/{action:resolve|dismiss}', [AdminController::class, 'reportAction']);
    $r->post('/admin/settings/save',     [AdminController::class, 'settingsSave']);

    /* ---------------- API (JSON) ---------------- */
    $r->get('/api/stats',                 [ApiController::class, 'stats']);
    $r->get('/api/polls',                 [ApiController::class, 'polls']);
    $r->get('/api/polls/{slug}',          [ApiController::class, 'poll']);
    $r->get('/api/polls/{slug}/map',      [ApiController::class, 'pollMap']);
    $r->get('/api/polls/{slug}/results',  [ApiController::class, 'pollResults']);
    $r->post('/api/polls/{slug}/responses', [ApiController::class, 'submitResponse']);
    $r->post('/api/auth/login',           [ApiController::class, 'login']);
    $r->post('/api/auth/register',        [ApiController::class, 'register']);
    $r->post('/api/auth/logout',          [ApiController::class, 'logout']);
    $r->get('/api/me',                    [ApiController::class, 'me']);
    $r->post('/api/favorites/{pollId:\d+}/toggle', [ApiController::class, 'toggleFavorite']);
    $r->get('/api/notifications',         [ApiController::class, 'notifications']);
    $r->post('/api/notifications/read',   [ApiController::class, 'notificationsRead']);
    $r->get('/api/map/global',            [ApiController::class, 'globalMap']);
    $r->get('/api/countries',             [ApiController::class, 'countries']);
    $r->post('/api/reports',              [ApiController::class, 'report']);
};
