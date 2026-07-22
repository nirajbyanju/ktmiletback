<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterUserRequest extends FormRequest
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
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'referral_code' => 'nullable|string|max:20',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'error' => [
                'status' => 'error',
                'validationErrors' => $validator->errors(),
            ],
        ], 422);

        throw new HttpResponseException($response);
    }
}
