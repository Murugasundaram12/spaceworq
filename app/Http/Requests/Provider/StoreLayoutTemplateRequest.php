<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreLayoutTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:500'],
            'elements' => ['required', 'array'],
            'elements.*.id' => ['nullable', 'string', 'max:80'],
            'elements.*.type' => ['required', 'string', 'in:desk,meeting,cabin,lounge,reception,booth'],
            'elements.*.label' => ['required', 'string', 'max:80'],
            'elements.*.x' => ['required', 'numeric', 'between:0,10000'],
            'elements.*.y' => ['required', 'numeric', 'between:0,10000'],
            'elements.*.width' => ['required', 'numeric', 'min:1', 'max:10000'],
            'elements.*.height' => ['required', 'numeric', 'min:1', 'max:10000'],
            'elements.*.seats' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'elements.*.color' => ['nullable', 'string', 'max:30'],
        ];
    }
}
