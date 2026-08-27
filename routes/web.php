<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ProviderVerificationController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HelpCenterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\Provider\ReviewController as ProviderReviewController;
use App\Http\Controllers\Provider\ServiceController as ProviderServiceController;
use App\Http\Controllers\Provider\ServiceDraftController;
use App\Http\Controllers\Provider\StatisticsController as ProviderStatisticsController;
use App\Http\Controllers\Provider\SubscriptionController as ProviderSubscriptionController;
use App\Http\Controllers\Provider\TransactionController as ProviderTransactionController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques (visiteurs, clients, prestataires, admins)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/prestataires', [ProviderController::class, 'index'])->name('providers.index');
Route::get('/prestataires/{providerProfile}', [ProviderController::class, 'show'])->name('providers.show');

Route::post('/chatbot', [ChatbotController::class, 'ask'])
    ->middleware('throttle:30,1')
    ->name('chatbot.ask');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/aide', [HelpCenterController::class, 'index'])->name('help.index');

Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');

// Passage quotidien déclenché en HTTP, pour les hébergements serverless où
// aucun `schedule:run` ne tourne (voir DEPLOY-VERCEL.md). Protégé par
// CRON_SECRET ; sans ce secret la route répond 503.
Route::get('/cron/subscriptions-refresh', [CronController::class, 'subscriptionsRefresh'])
    ->middleware('cron')
    ->name('cron.subscriptions.refresh');

// Contrôle d'après-déploiement. Même protection : ce que renvoie cette route
// décrit la configuration de la production, ça ne se laisse pas lire par tout
// le monde.
Route::get('/cron/health', [CronController::class, 'health'])
    ->middleware('cron')
    ->name('cron.health');

/*
|--------------------------------------------------------------------------
| Routes pour tout utilisateur authentifié et actif
|--------------------------------------------------------------------------
*/

/*
 * Pages légales — publiques et sans état, comme le veut leur usage : on doit
 * pouvoir les lire avant de créer un compte, et les citer par leur adresse.
 */
Route::get('/conditions-generales', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/mentions-legales', [LegalController::class, 'notice'])->name('legal.notice');
Route::get('/confidentialite', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{serviceRequest}/accept', [RequestController::class, 'accept'])->name('requests.accept');
    Route::post('/requests/{serviceRequest}/refuse', [RequestController::class, 'refuse'])->name('requests.refuse');
    Route::post('/requests/{serviceRequest}/start', [RequestController::class, 'start'])->name('requests.start');
    Route::post('/requests/{serviceRequest}/complete', [RequestController::class, 'complete'])->name('requests.complete');
    Route::post('/requests/{serviceRequest}/cancel', [RequestController::class, 'cancel'])->name('requests.cancel');
    Route::post('/requests/{serviceRequest}/review', [ReviewController::class, 'store'])->name('requests.review');

    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('conversations.messages.store');

    Route::get('/favoris', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favoris/{providerProfile}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/phone/verify', [PhoneVerificationController::class, 'show'])->name('phone.verify');
    Route::post('/phone/verify/send', [PhoneVerificationController::class, 'send'])
        ->middleware('throttle:3,1')
        ->name('phone.verify.send');
    Route::post('/phone/verify', [PhoneVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('phone.verify.check');
});

/*
|--------------------------------------------------------------------------
| Routes réservées aux prestataires
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:provider'])->prefix('provider')->name('provider.')->group(function () {
    Route::resource('services', ProviderServiceController::class)->except('show');

    Route::post('/services/draft', [ServiceDraftController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('services.draft');

    Route::get('/profile', [ProviderProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProviderProfileController::class, 'update'])->name('profile.update');

    Route::get('/statistics', [ProviderStatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/reviews', [ProviderReviewController::class, 'index'])->name('reviews.index');
    Route::get('/transactions', [ProviderTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/export', [ProviderTransactionController::class, 'export'])->name('transactions.export');

    Route::get('/subscription', [ProviderSubscriptionController::class, 'show'])->name('subscription.show');
    Route::get('/subscription/{plan}', [ProviderSubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription/{plan}', [ProviderSubscriptionController::class, 'subscribe'])
        ->middleware('throttle:5,1')
        ->name('subscription.subscribe');
    Route::post('/subscription/{plan}/gratuit', [ProviderSubscriptionController::class, 'activateFree'])
        ->middleware('throttle:5,1')
        ->name('subscription.free');
});

/*
|--------------------------------------------------------------------------
| Routes réservées aux clients
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/profile', [ClientProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ClientProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Routes réservées aux administrateurs
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/reactivate', [AdminUserController::class, 'reactivate'])->name('users.reactivate');

    Route::get('/services', [AdminServiceController::class, 'index'])->name('services.index');
    Route::patch('/services/{service}/toggle-status', [AdminServiceController::class, 'toggleStatus'])->name('services.toggle-status');
    Route::delete('/services/{service}', [AdminServiceController::class, 'destroy'])->name('services.destroy');

    Route::resource('categories', ServiceCategoryController::class)->except('show');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::post('/moderation/{report}/dismiss', [ModerationController::class, 'dismiss'])->name('moderation.dismiss');

    Route::get('/verifications', [ProviderVerificationController::class, 'index'])->name('verifications.index');
    Route::post('/verifications/{providerProfile}/approve', [ProviderVerificationController::class, 'approve'])->name('verifications.approve');
    Route::post('/verifications/{providerProfile}/reject', [ProviderVerificationController::class, 'reject'])->name('verifications.reject');
});

require __DIR__.'/auth.php';
