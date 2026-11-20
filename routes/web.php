<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\BackupController as AdminBackupController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Member\LoanController as MemberLoanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\LoanController as StaffLoanController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('catalog.index');
})->name('home');

// Katalog Publik (dapat diakses oleh Guest maupun Pengguna Terautentikasi)
Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('catalog/{book}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Route Peminjaman khusus Anggota
    Route::middleware('role:anggota')
        ->prefix('my-loans')
        ->name('member.loans.')
        ->group(function () {
            Route::get('/', [MemberLoanController::class, 'index'])->name('index');
            Route::post('/', [MemberLoanController::class, 'store'])->middleware('throttle:20,1')->name('store');
            Route::get('{loan}', [MemberLoanController::class, 'show'])->name('show');
            Route::patch('{loan}/cancel', [MemberLoanController::class, 'cancel'])->name('cancel');
            Route::patch('{loan}/request-return', [MemberLoanController::class, 'requestReturn'])->name('request-return');
        });

    // Route Pembayaran Denda via Midtrans khusus Anggota
    Route::middleware('role:anggota')
        ->post('fines/{fine}/pay', [PaymentController::class, 'payFine'])
        ->middleware('throttle:20,1')
        ->name('member.fines.pay');

    // Route Pelayanan Peminjaman untuk Petugas & Admin
    Route::middleware('role:admin,petugas')
        ->prefix('loans')
        ->name('staff.loans.')
        ->group(function () {
            Route::get('/', [StaffLoanController::class, 'index'])->name('index');
            Route::get('{loan}', [StaffLoanController::class, 'show'])->name('show');
            Route::patch('{loan}/approve', [StaffLoanController::class, 'approve'])->name('approve');
            Route::patch('{loan}/reject', [StaffLoanController::class, 'reject'])->name('reject');
            Route::patch('{loan}/return', [StaffLoanController::class, 'returnBook'])->name('return');
        });

    // Route Administrasi khusus Admin
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('tags', [AdminTagController::class, 'index'])->name('tags.index');
            Route::post('tags', [AdminTagController::class, 'store'])->name('tags.store');
            Route::put('tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
            Route::delete('tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

            Route::get('books', [AdminBookController::class, 'index'])->name('books.index');
            Route::get('books/create', [AdminBookController::class, 'create'])->name('books.create');
            Route::post('books', [AdminBookController::class, 'store'])->name('books.store');
            Route::get('books/{book}/edit', [AdminBookController::class, 'edit'])->name('books.edit');
            Route::put('books/{book}', [AdminBookController::class, 'update'])->name('books.update');
            Route::delete('books/{book}', [AdminBookController::class, 'destroy'])->name('books.destroy');

            Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
            Route::patch('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])
                ->name('users.toggle-status');

            Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export', [AdminReportController::class, 'exportCsv'])->name('reports.export');

            Route::get('audit-logs', [AdminActivityLogController::class, 'index'])->name('audit-logs.index');

            Route::get('backups', [AdminBackupController::class, 'index'])->name('backups.index');
            Route::post('backups', [AdminBackupController::class, 'store'])
                ->middleware('throttle:10,1')
                ->name('backups.store');
            Route::post('backups/prune', [AdminBackupController::class, 'prune'])
                ->middleware('throttle:10,1')
                ->name('backups.prune');
            Route::post('backups/{filename}/verify', [AdminBackupController::class, 'verify'])
                ->where('filename', '[^/]+')
                ->name('backups.verify');
            Route::get('backups/{filename}/download', [AdminBackupController::class, 'download'])
                ->where('filename', '[^/]+')
                ->name('backups.download');
            Route::delete('backups/{filename}', [AdminBackupController::class, 'destroy'])
                ->where('filename', '[^/]+')
                ->name('backups.destroy');
        });
});

// Midtrans Payment Webhook Notification
Route::post('midtrans/notification', [PaymentController::class, 'handleNotification'])
    ->name('midtrans.notification');
