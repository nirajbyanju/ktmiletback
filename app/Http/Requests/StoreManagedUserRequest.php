<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:50|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'required',
            'status' => 'nullable|boolean',
            'email_verified' => 'nullable|boolean',
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
