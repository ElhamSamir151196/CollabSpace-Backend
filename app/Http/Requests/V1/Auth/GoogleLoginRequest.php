<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The React app gets a Google OAuth access token
     * (e.g. useGoogleLogin from @react-oauth/google) and sends it here.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'access_token' => ['required', 'string', 'max:4096'],
            'remember' => ['sometimes', 'boolean'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
