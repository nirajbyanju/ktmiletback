<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'username' => "sometimes|required|string|max:255|unique:users,username,{$userId}",
            'email' => "sometimes|required|email|max:255|unique:users,email,{$userId}",
            'phone' => "sometimes|required|string|max:50|unique:users,phone,{$userId}",
            'date_of_birth' => 'nullable|date',
            'bio' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'local_bodies' => 'nullable|string|max:255',
            'street_name' => 'nullable|string|max:255',
        ];
    }
}
