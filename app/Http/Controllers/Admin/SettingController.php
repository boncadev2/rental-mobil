<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    private const DEFAULTS = ['company_name' => 'RentalMobil', 'hero_title' => 'Mau pergi ke mana hari ini?', 'hero_subtitle' => 'Satu aplikasi untuk semua kebutuhan perjalanan Anda.', 'hero_image' => '', 'primary_color' => '#0284c7', 'driver_in_city' => '150000', 'driver_out_city' => '225000', 'company_address' => '', 'company_location' => '', 'company_phone' => '', 'company_whatsapp' => '', 'social_instagram' => '', 'social_facebook' => '', 'social_tiktok' => '', 'whatsapp_session_id' => '', 'whatsapp_admin_phone' => ''];

    public function edit()
    {
        return Inertia::render('Admin/Settings/Edit', ['settings' => [...self::DEFAULTS, ...AppSetting::values()]]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['company_name' => 'required|string|max:100', 'hero_title' => 'required|string|max:160', 'hero_subtitle' => 'nullable|string|max:255', 'hero_image' => 'nullable|image|max:5120', 'primary_color' => 'required|string|max:20', 'driver_in_city' => 'required|integer|min:0', 'driver_out_city' => 'required|integer|min:0', 'company_address' => 'nullable|string|max:500', 'company_location' => 'nullable|string|max:255', 'company_phone' => 'nullable|string|max:30', 'company_whatsapp' => 'nullable|string|max:30', 'social_instagram' => 'nullable|string|max:255', 'social_facebook' => 'nullable|string|max:255', 'social_tiktok' => 'nullable|string|max:255', 'whatsapp_session_id' => 'nullable|string|max:100', 'whatsapp_admin_phone' => 'nullable|string|max:30']);

        if ($request->hasFile('hero_image')) {
            $data['hero_image'] = Storage::disk('public')->url($request->file('hero_image')->store('hero', 'public'));
        } else {
            unset($data['hero_image']);
        }

        foreach ($data as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
