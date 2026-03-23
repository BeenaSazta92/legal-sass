<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
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
        $subscriptionId = $this->route('subscription')?->id;
        return [
            'name' => "sometimes|required|string|max:255|unique:subscriptions,name,$subscriptionId",
            'max_admins' => 'sometimes|required|integer|min:1|max:100',
            'max_lawyers' => 'sometimes|required|integer|min:1|max:1000',
            'max_clients' => 'sometimes|required|integer|min:1|max:10000',
            'max_documents_per_user' => 'sometimes|required|integer|min:1|max:10000',
            'is_default' => 'sometimes|boolean',
        ];
    }
}
