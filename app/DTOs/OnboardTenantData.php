<?php

namespace App\DTOs;

use App\Http\Requests\Auth\RegisterTenantRequest;

readonly class OnboardTenantData
{
    public function __construct(
        public string $companyName,
        public string $companySlug,
        public string $name,
        public string $email,
        public string $password,
    ) {}

    /**
     * Create DTO from validated FormRequest.
     */
    public static function fromRequest(RegisterTenantRequest $request): self
    {
        return new self(
            companyName: (string) $request->validated('company_name'),
            companySlug: (string) $request->validated('company_slug'),
            name: (string) $request->validated('name'),
            email: (string) $request->validated('email'),
            password: (string) $request->validated('password'),
        );
    }

    /**
     * Create DTO from an array.
     *
     * @param  array{company_name: string, company_slug: string, name: string, email: string, password: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            companyName: $data['company_name'],
            companySlug: $data['company_slug'],
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
        );
    }
}
