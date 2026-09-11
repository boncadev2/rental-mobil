<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Customer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $customer = $user->customer ?? Customer::query()
            ->whereNull('user_id')
            ->where('email', $user->email)
            ->first();

        if ($customer !== null) {
            $customer->update(['user_id' => $user->id]);
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'customer' => $customer,
        ]);
    }

    public function sendWhatsAppVerification(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 404);
        $code = (string) random_int(100000, 999999);
        $customer->update(['phone_verification_code' => $code, 'phone_verification_expires_at' => now()->addMinutes(10)]);

        return response()->json(['message' => 'Kode verifikasi dibuat untuk nomor WhatsApp Anda.', 'demo_code' => $code]);
    }

    public function verifyWhatsApp(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);
        $customer = $request->user()->customer;
        abort_unless($customer, 404);
        if (! $customer->phone_verification_expires_at?->isFuture() || $customer->phone_verification_code !== $data['code']) {
            return response()->json(['message' => 'Kode verifikasi tidak valid atau sudah kedaluwarsa.'], 422);
        }
        $customer->update(['phone_verified_at' => now(), 'phone_verification_code' => null, 'phone_verification_expires_at' => null, 'status' => $customer->ktp_file_path ? 'VERIFIED' : 'ACTIVE']);

        return response()->json(['message' => 'Nomor WhatsApp berhasil diverifikasi.']);
    }

    public function uploadKtp(Request $request): JsonResponse
    {
        $request->validate(['ktp_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);
        $customer = $request->user()->customer;
        abort_unless($customer, 404);
        $customer->update(['ktp_file_path' => Storage::disk('local')->putFile('identity-cards', $request->file('ktp_file')), 'ktp_verified_at' => now(), 'status' => 'VERIFIED']);

        return response()->json(['message' => 'KTP berhasil diunggah. Akun Anda sekarang terverifikasi.']);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
