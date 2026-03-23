<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLawFirmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $firmId = $this->route('firm')->id ?? null;
        return [
            'name' => 'sometimes|required|string|max:255|unique:law_firms,name,' . $firmId,
            'subscription_id' => 'sometimes|required|exists:subscriptions,id',
            'status' => 'sometimes|required|in:active,suspended',
        ];
    }
}
