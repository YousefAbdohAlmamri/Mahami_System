<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MoveTaskRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_list_id' => ['required', 'integer', 'exists:board_lists,id'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance to ensure cross-board movements are strictly prevented.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $task = $this->route('task');
            $targetListId = (int) $this->input('target_list_id');

            if ($task instanceof Task && $targetListId) {
                $targetList = app(BoardListRepositoryInterface::class)->find($targetListId);

                if ($targetList && (int) $task->list->board_id !== (int) $targetList->board_id) {
                    $validator->errors()->add(
                        'target_list_id',
                        'The target list must belong to the same board as the task.'
                    );
                }
            }
        });
    }
}

