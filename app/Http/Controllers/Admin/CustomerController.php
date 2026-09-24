<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => Customer::query()->withCount('bookings')
                ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                ->latest()->paginate(20)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function showKtp(Customer $customer)
    {
        if (!$customer->ktp_file_path) {
            abort(404, 'KTP tidak ditemukan (Belum diunggah).');
        }

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($customer->ktp_file_path)) {
            abort(404, 'File KTP fisik tidak ditemukan di server (Mungkin terhapus atau tidak ikut tersalin ke Docker lokal).');
        }

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($customer->ktp_file_path));
    }
}
