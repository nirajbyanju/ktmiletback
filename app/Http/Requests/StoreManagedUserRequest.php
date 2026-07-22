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
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'required',
            'status' => 'nullable|boolean',
            'email_verified' => 'nullable|boolean',
        ];
    }
}
