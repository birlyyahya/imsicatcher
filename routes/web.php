<?php

use App\Http\Controllers\MissionIssuePdfController;
use App\Http\Controllers\SpeedtestController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('network-traffic', 'pages.monitor.network-traffic')->name('network-traffic');

    Route::prefix('net-speedtest')->name('speedtest.')->group(function () {
        Route::match(['get', 'post', 'head'], 'empty', [SpeedtestController::class, 'empty'])->name('empty');
        Route::get('garbage', [SpeedtestController::class, 'garbage'])->name('garbage');
        Route::get('getIP', [SpeedtestController::class, 'getIP'])->name('getip');
    });
    Route::livewire('mission-issues', 'pages::monitor.mission-issues')->name('mission-issues');
    Route::livewire('mission-issues/create', 'pages::monitor.mission-issues-create')->name('mission-issues.create');
    Route::get('mission-issues/{issue}/pdf', MissionIssuePdfController::class)->name('mission-issues.pdf');
    Route::livewire('mission-issues/{issue}/edit', 'pages::monitor.mission-issues-edit')->name('mission-issues.edit');
    Route::livewire('mission-issues/{issue}', 'pages::monitor.mission-issues-show')->name('mission-issues.show');
    Route::livewire('operasi-alat', 'pages::monitor.operasi-alat')->name('operasi-alat');
    Route::livewire('operasi-alat/create', 'pages::monitor.operasi-alat-create')->name('operasi-alat.create');
    Route::livewire('operasi-alat/{operasiAlat}/edit', 'pages::monitor.operasi-alat-edit')->name('operasi-alat.edit');
    Route::livewire('operasi-alat/{operasiAlat}', 'pages::monitor.operasi-alat-show')->name('operasi-alat.show');
    Route::livewire('logs', 'pages::monitor.logs')->name('logs');
    Route::livewire('users', 'pages::management.users')->name('users');
    Route::livewire('satkers', 'pages::management.satkers')->name('satkers');
    Route::livewire('aset', 'pages::management.aset')->name('aset');
    Route::view('documentation', 'pages.documentation.index')->name('documentation');
    Route::livewire('documentation/materials', 'pages::documentation.materials')->name('documentation.materials');
    Route::livewire('documentation/support-contacts', 'pages::documentation.support-contacts')->name('documentation.support-contacts');
    Route::livewire('documentation/faqs', 'pages::documentation.faqs')->name('documentation.faqs');
    Route::get('speedtest-test', function () {
        return file_get_contents(public_path('speedtest_test.html'));
    })->name('speedtest-test');
});

require __DIR__.'/settings.php';
