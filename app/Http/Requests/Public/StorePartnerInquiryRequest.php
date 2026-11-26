<?php

namespace App\Http\Requests\Public;

use App\Models\PartnerInquiry;
use Illuminate\Foundation\Http\FormRequest;

class StorePartnerInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inquiry_type' => ['required', 'string', 'in:' . implode(',', PartnerInquiry::TYPES)],
            'entity_name' => ['required', 'string', 'min:2', 'max:200'],
            'city_name' => ['required', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:180'],
            'total_desks' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'desk_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'total_area_sqft' => ['nullable', 'integer', 'min:100', 'max:50000000'],
            'floor_plate_sqft' => ['nullable', 'integer', 'min:100', 'max:10000000'],
            'partnership_model' => ['nullable', 'string', 'max:80'],
            'construction_stage' => ['nullable', 'string', 'max:80'],
            'expected_completion' => ['nullable', 'string', 'max:80'],
            'indicative_rent' => ['nullable', 'string', 'max:80'],
            'developer_brand' => ['nullable', 'string', 'max:180'],
            'contact_name' => ['required', 'string', 'min:2', 'max:150'],
            'contact_phone' => ['required', 'string', 'regex:/^[\d\s\-()+]{7,25}$/'],
            'contact_email' => ['required', 'string', 'email:rfc', 'max:180'],
        ];
    }
}
