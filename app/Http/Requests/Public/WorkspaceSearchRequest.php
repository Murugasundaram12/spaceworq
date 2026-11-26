<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:60'],
            'subcategory' => ['nullable', 'string', 'max:60'],
            'subtype' => ['nullable', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:150'],
            'q' => ['nullable', 'string', 'max:150'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:120'],
            'sort' => ['nullable', 'string', 'in:recommended,priceLowHigh,priceHighLow,rating'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
