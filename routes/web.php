<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClaimInviteController;
use App\Http\Controllers\CoupleController;
use App\Http\Controllers\FieldPrivacyController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MailDiagnosticController;
use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\MyPortfolioController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileFieldController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RecordMediaController;
use App\Http\Controllers\TreeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

// Outside every auth group: the language has to be changeable from the sign-in
// and claim pages, which is where it matters most.
Route::get('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

// The address asked for by name. Same page as admin/portfolios, so there is
// one implementation and two ways in rather than two things to keep in step.
Route::middleware(['auth', 'admin'])
    ->get('/portfolio/admin', [PortfolioController::class, 'index'])
    ->name('portfolio.admin');

Route::middleware('guest')->group(function () {
    Route::get('/claim/{token}', [ClaimInviteController::class, 'show'])->name('claim.show');
    Route::post('/claim/{token}', [ClaimInviteController::class, 'store'])->name('claim.store');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    // A person editing their own portfolio. No slug in the address: which page
    // this is follows from who is signed in, so there is nothing to change in
    // the URL that would reach somebody else's.
    Route::get('/my-portfolio', [MyPortfolioController::class, 'edit'])->name('my-portfolio.edit');
    Route::patch('/my-portfolio', [MyPortfolioController::class, 'update'])->name('my-portfolio.update');

    // Breeze's own account settings (login email / password) — distinct from
    // the tree profile below, which is the person's public-facing record.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/tree', [TreeController::class, 'index'])->name('tree.index');
    Route::get('/tree/data', [TreeController::class, 'data'])->name('tree.data');

    Route::get('/people/{person}', [PersonController::class, 'show'])->name('people.show');
    Route::get('/people/{person}/edit', [PersonController::class, 'edit'])->name('people.edit');
    Route::patch('/people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::post('/people/{person}/photo', [PersonController::class, 'updatePhoto'])->name('people.photo.update');

    // Its own route rather than part of the profile form: a moderator may
    // record a death for anyone in the tree, including the elders whose
    // profiles they cannot otherwise edit.
    Route::patch('/people/{person}/deceased', [PersonController::class, 'updateDeceased'])->name('people.deceased.update');

    Route::get('/people/{person}/children/create', [PersonController::class, 'createChild'])->name('people.children.create');
    Route::post('/people/{person}/children', [PersonController::class, 'storeChild'])->name('people.children.store');

    Route::patch('/couples/{couple}', [CoupleController::class, 'update'])->name('couples.update');
    Route::delete('/couples/{couple}', [CoupleController::class, 'destroy'])->name('couples.destroy');

    Route::patch('/people/{person}/field-privacy/{fieldKey}', [FieldPrivacyController::class, 'update'])
        ->name('field-privacy.update');

    Route::post('/people/{person}/fields', [ProfileFieldController::class, 'store'])->name('fields.store');
    Route::patch('/fields/{field}', [ProfileFieldController::class, 'update'])->name('fields.update');
    Route::delete('/fields/{field}', [ProfileFieldController::class, 'destroy'])->name('fields.destroy');

    Route::post('/people/{person}/records', [RecordController::class, 'store'])->name('records.store');
    Route::patch('/records/{record}', [RecordController::class, 'update'])->name('records.update');
    Route::delete('/records/{record}', [RecordController::class, 'destroy'])->name('records.destroy');

    Route::post('/records/{record}/media', [RecordMediaController::class, 'store'])->name('records.media.store');
    Route::delete('/record-media/{media}', [RecordMediaController::class, 'destroy'])->name('record-media.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    Route::get('/people/create', [AdminController::class, 'createPerson'])->name('people.create');
    Route::post('/people', [AdminController::class, 'storePerson'])->name('people.store');

    Route::get('/people/{person}/password', [AdminController::class, 'editPassword'])->name('people.password.edit');
    Route::put('/people/{person}/password', [AdminController::class, 'updatePassword'])->name('people.password.update');
    Route::post('/people/{person}/resend-invite', [AdminController::class, 'resendInvite'])->name('people.resend-invite');
    Route::delete('/people/{person}', [AdminController::class, 'destroy'])->name('people.destroy');

    Route::get('/people/{person}/parents/create', [AdminController::class, 'createParent'])->name('people.parents.create');
    Route::post('/people/{person}/parents', [AdminController::class, 'storeParent'])->name('people.parents.store');

    Route::get('/people/{person}/spouses/create', [AdminController::class, 'createSpouse'])->name('people.spouses.create');
    Route::post('/people/{person}/spouses', [AdminController::class, 'storeSpouse'])->name('people.spouses.store');

    Route::post('/relationships', [AdminController::class, 'attachParent'])->name('relationships.attach');
    Route::delete('/relationships/{child}/{parent}', [AdminController::class, 'detachParent'])->name('relationships.detach');

    // The eight family portfolios. Open to moderators as well as the Super
    // Admin: it is a list of links, and finding a relative's page is part of
    // looking after the tree rather than a privilege.
    Route::get('/portfolios', [PortfolioController::class, 'index'])->name('portfolios.index');
    Route::get('/portfolios/{slug}/edit', [PortfolioController::class, 'edit'])->name('portfolios.edit');
    Route::patch('/portfolios/{slug}', [PortfolioController::class, 'update'])->name('portfolios.update');
    // Publishing is its own step so several edits go out together, and so a
    // half-finished sentence is not live the moment it is typed.
    Route::post('/portfolios/publish', [PortfolioController::class, 'publish'])->name('portfolios.publish');

    // Kept back from moderators: appointing them, and the mail diagnostic,
    // which reports how the server is configured. Both are the Super Admin's.
    // A moderator passes the "admin" middleware above but not this one.
    Route::middleware('super-admin')->group(function () {
        Route::get('/moderators', [ModeratorController::class, 'index'])->name('moderators.index');
        Route::post('/moderators/{user}', [ModeratorController::class, 'promote'])->name('moderators.promote');
        Route::delete('/moderators/{user}', [ModeratorController::class, 'demote'])->name('moderators.demote');

        // Browser equivalent of `php artisan app:mail-check`, for hosts with no shell.
        Route::get('/mail-check', [MailDiagnosticController::class, 'show'])->name('mail-check');
        Route::post('/mail-check', [MailDiagnosticController::class, 'send'])->name('mail-check.send');
    });

    Route::patch('/tree/positions/{person}', [TreeController::class, 'updatePosition'])->name('tree.positions.update');
    Route::post('/tree/positions/reset', [TreeController::class, 'resetPositions'])->name('tree.positions.reset');
});

require __DIR__.'/auth.php';
