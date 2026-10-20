<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignBoardTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:workspace_teams,id'],
            'permission' => ['required', 'string', 'in:viewer,editor,admin'],
        ];
    }
}
