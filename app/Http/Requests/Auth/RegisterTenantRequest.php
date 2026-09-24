<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterTenantRequest extends FormRequest
{
    /**
     * Reserved subdomains that cannot be registered by tenants.
     *
     * @var array<int, string>
     */
    public const RESERVED_SLUGS = [
        'admin',
        'api',
        'app',
        'billing',
        'dashboard',
        'dev',
        'mail',
        'staging',
        'status',
        'support',
        'test',
        'www',
    ];

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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_slug' => [
                'required',
                'string',
                'max:63',
                'alpha_dash',
                'unique:tenants,slug',
                Rule::notIn(self::RESERVED_SLUGS),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
