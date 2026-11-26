<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Public\StoreConsultationRequest;

class WebConsultationRequest extends StoreConsultationRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', $this->input('consultName', ''))),
            'company_name' => trim((string) $this->input('company_name', $this->input('consultCompany', ''))),
            'phone' => trim((string) $this->input('phone', $this->input('consultPhone', ''))),
            'city_name' => trim((string) $this->input('city_name', $this->input('consultCity', ''))),
            'seats_range' => $this->input('seats_range', $this->input('consultSeats')),
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }
}
