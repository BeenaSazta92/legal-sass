<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMIN,LAWYER,CLIENT',
            //'firm_id' => 'required|exists:law_firms,id', // TODO :: make this required only when auth user is system_admin 
            'firm_id' => [
                'nullable',
                'exists:law_firms,id',
                function ($attribute, $value, $fail) {
                    // Make required if logged-in user is SYSTEM_ADMIN
                    if (auth()->user()->role === 'SYSTEM_ADMIN' && empty($value)) {
                        $fail('The firm_id field is required when creating a user as SYSTEM_ADMIN.');
                    }
                },
            ],
        ];
    }
}
