<?php

use App\Enums\VehicleStatus;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\VehicleCheckoutController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\VehicleReturnController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\CustomerRegistrationController;
use App\Http\Controllers\Public\MyBookingController;
use App\Http\Controllers\Public\PaymentController;
use App\Http\Controllers\Public\VehicleController as PublicVehicleController;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AppSetting;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\MaintenanceSchedule;
use App\Models\Payment;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Public/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'featuredVehicles' => Vehicle::query()->with(['category', 'images'])->where('featured', true)->take(3)->get(), 'settings' => AppSetting::values(),
    ]);
});

Route::get('/cars', [PublicVehicleController::class, 'index'])->name('cars.index');
Route::get('/customer-registration', [CustomerRegistrationController::class, 'create'])->name('customer-registration.create');
Route::post('/customer-registration', [CustomerRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('customer-registration.store');
Route::get('/cars/{vehicle}', [PublicVehicleController::class, 'show'])->name('cars.show');
Route::post('/booking', [BookingController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('booking.store');
Route::get('/booking/{vehicle}/availability', [BookingController::class, 'availability'])->name('booking.availability');
Route::get('/booking/{vehicle}', [BookingController::class, 'create'])->name('booking.create');
Route::get('/payment/{booking}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/payment/{booking}', [PaymentController::class, 'create'])->name('payment.create');
Route::get('/my-bookings', [MyBookingController::class, 'index'])->middleware('auth')->name('my-bookings.index');
Route::get('/my-bookings/{booking}', [MyBookingController::class, 'show'])->middleware('auth')->name('my-bookings.show');
Route::post('/my-bookings/{booking}/return-request', [MyBookingController::class, 'requestReturn'])->middleware('auth')->name('my-bookings.return-request');
Route::post('/api/payment/webhook', PaymentWebhookController::class)->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/dashboard', function () {
    if (request()->user()->hasRole('customer')) {
        $bookings = Booking::query()->with('vehicle')->whereHas('customer', fn ($query) => $query->where('user_id', request()->user()->id));

        return Inertia::render('Customer/Dashboard', ['metrics' => ['bookings' => (clone $bookings)->count(), 'active' => (clone $bookings)->whereIn('status', ['PAID', 'CONFIRMED', 'READY_FOR_PICKUP', 'IN_USE'])->count(), 'pending' => (clone $bookings)->where('status', 'WAITING_PAYMENT')->count(), 'spending' => (clone $bookings)->whereNotIn('status', ['CANCELLED'])->sum('total_amount')], 'recentBookings' => $bookings->latest()->take(5)->get(), 'customerPhoneVerified' => request()->user()->customer?->phone_verified_at !== null, 'customerKtpVerified' => request()->user()->customer?->ktp_verified_at !== null]);
    }

    $receivables = Invoice::query()
        ->where('balance', '>', 0)
        ->whereHas('booking', fn ($query) => $query->where('status', '!=', 'CANCELLED'));

    return Inertia::render('Dashboard', ['metrics' => ['bookings' => Booking::count(), 'revenue' => Payment::whereIn('status', ['PAID', 'SUCCEEDED'])->sum('amount'), 'receivables' => (clone $receivables)->sum('balance'), 'receivableCount' => $receivables->count(), 'vehiclesInUse' => Vehicle::where('status', VehicleStatus::IN_USE)->count(), 'maintenanceDue' => MaintenanceSchedule::whereIn('status', ['DUE_SOON', 'OVERDUE'])->count()]]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/verify-whatsapp/send', [ProfileController::class, 'sendWhatsAppVerification'])->name('profile.whatsapp.send');
    Route::post('/profile/verify-whatsapp', [ProfileController::class, 'verifyWhatsApp'])->name('profile.whatsapp.verify');
    Route::post('/profile/verify-ktp', [ProfileController::class, 'uploadKtp'])->name('profile.ktp.upload');
});

Route::middleware(['auth', 'role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('vehicles', VehicleController::class);
    Route::post('bookings/{booking}/cash-settlement', [AdminBookingController::class, 'cashSettlement'])->name('bookings.cash-settlement');
    Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::resource('bookings', AdminBookingController::class)->only(['index', 'show']);
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('reports/financial', [FinancialReportController::class, 'index'])->name('reports.financial');
    Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
    Route::get('maintenance/{vehicle}', [MaintenanceController::class, 'show'])->name('maintenance.show');
    Route::post('maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('checkouts', [VehicleCheckoutController::class, 'index'])->name('checkouts.index');
    Route::get('checkouts/{booking}/create', [VehicleCheckoutController::class, 'create'])->name('checkouts.create');
    Route::post('checkouts/{booking}', [VehicleCheckoutController::class, 'store'])->name('checkouts.store');
    Route::get('returns/{booking}/create', [VehicleReturnController::class, 'create'])->name('returns.create');
    Route::post('returns/{booking}', [VehicleReturnController::class, 'store'])->name('returns.store');
});

require __DIR__.'/auth.php';
