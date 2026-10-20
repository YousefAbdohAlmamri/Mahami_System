<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteWorkspaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['sometimes', 'in:admin,member,guest'],
        ];
    }
}

