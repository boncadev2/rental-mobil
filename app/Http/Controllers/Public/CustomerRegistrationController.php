<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerRegistrationController extends Controller
{
    public function create(Request $request): Response
    {
        $bookingVehicleId = $request->integer('vehicle');

        return Inertia::render('Public/Customers/Register', [
            'bookingVehicleId' => Vehicle::query()->whereKey($bookingVehicleId)->exists() ? $bookingVehicleId : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'phone' => ['required', 'string', 'max:30', 'unique:customers,phone'], 'address' => ['required', 'string', 'max:1000'], 'password' => ['required', 'confirmed', 'min:8'], 'booking_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id']]);
        [$user, $customer] = DB::transaction(function () use ($data) {
            $role = Role::query()->firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);
            $user = User::query()->create(['name' => $data['full_name'], 'email' => $data['email'], 'password' => $data['password'], 'role_id' => $role->id, 'status' => 'ACTIVE']);
            $customer = Customer::query()->create(['user_id' => $user->id, 'customer_code' => 'CUS-'.str()->upper(str()->random(8)), 'full_name' => $data['full_name'], 'email' => $data['email'], 'phone' => $data['phone'], 'address' => $data['address'], 'status' => 'ACTIVE']);

            return [$user, $customer];
        });
        Auth::login($user);

        return response()->json(['message' => 'Pendaftaran berhasil. Anda sudah masuk sebagai pelanggan.', 'customer_code' => $customer->customer_code, 'redirect' => url('/')], 201);
    }
}
