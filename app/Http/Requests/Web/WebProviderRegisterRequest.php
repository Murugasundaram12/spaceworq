<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Auth\ProviderRegisterRequest;

class WebProviderRegisterRequest extends ProviderRegisterRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'mobile' => trim((string) $this->input('mobile', '')),
        ]);
    }
}
