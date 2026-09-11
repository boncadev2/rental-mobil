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
}
