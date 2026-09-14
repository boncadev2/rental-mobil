<?php
namespace App\Http\Controllers\Admin;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $r)
    {
        return Inertia::render('Admin/Bookings/Index', ['bookings' => Booking::with(['customer', 'vehicle', 'invoice'])->when($r->status, fn ($q, $s) => $q->where('status', $s))->latest()->paginate(20)->withQueryString(), 'status' => $r->status]);
    }

    public function show(Booking $booking)
    {
        return Inertia::render('Admin/Bookings/Show', ['booking' => $booking->load(['customer', 'vehicle', 'vehicle.category']), 'invoice' => \App\Models\Invoice::where('booking_id', $booking->id)->first(), 'payments' => \App\Models\Payment::where('booking_id', $booking->id)->get(), 'checkout' => \App\Models\VehicleCheckout::with('items')->where('booking_id', $booking->id)->first(), 'checkin' => \App\Models\VehicleReturn::with('items')->where('booking_id', $booking->id)->first()]);
    }

    public function cashSettlement(Booking $booking, PaymentService $payments): RedirectResponse
    {
        $payments->recordCashSettlement($booking);

        return to_route('admin.bookings.show', $booking)->with('success', 'Pelunasan cash berhasil dicatat.');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if (! in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::READY_FOR_PICKUP], true)) {
            throw ValidationException::withMessages(['booking' => 'Hanya booking yang belum checkout yang dapat dibatalkan.']);
        }

        $booking->update(['status' => BookingStatus::CANCELLED]);

        return to_route('admin.bookings.show', $booking)->with('success', 'Booking dibatalkan. Kendaraan kembali tersedia untuk tanggal tersebut.');
    }
}
