<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends StoreVehicleRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $id = $this->route('vehicle')->id;
        $rules['code'] = ['required', 'string', 'max:50', Rule::unique('vehicles', 'code')->ignore($id)];
        $rules['license_plate'] = ['required', 'string', 'max:30', Rule::unique('vehicles', 'license_plate')->ignore($id)];

        return $rules;
    }
}
