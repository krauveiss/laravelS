<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:3'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $password = $this->input('password');

            if (
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[A-Za-z]/', $password)
            ) {
                $validator->errors()->add('password', 'password is incorrect');
            }
        });
    }
}
