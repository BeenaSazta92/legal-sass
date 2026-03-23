<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubscriptionRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255|unique:subscriptions',
            'max_admins' => 'required|integer|min:1|max:100',
            'max_lawyers' => 'required|integer|min:1|max:1000',
            'max_clients' => 'required|integer|min:1|max:10000',
            'max_documents_per_user' => 'required|integer|min:1|max:10000',
            'is_default' => 'required|boolean' //used to manage status of default subscription
        ];
    }
}
