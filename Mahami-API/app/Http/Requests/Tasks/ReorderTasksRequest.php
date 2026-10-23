<?php

namespace App\Http\Requests\Tasks;

use App\Models\BoardList;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderTasksRequest extends FormRequest
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
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'distinct', 'exists:tasks,id'],
        ];
    }

    /**
     * Configure the validator instance to ensure all tasks belong to the specified list.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $list = $this->route('list');
            $listId = $list instanceof BoardList ? $list->id : (int) $list;

            $orderedIds = $this->input('ordered_ids', []);
            if (!empty($orderedIds) && $listId) {
                $validCount = app(TaskRepositoryInterface::class)
                    ->countInList((int) $listId, $orderedIds);

                if ($validCount !== count($orderedIds)) {
                    $validator->errors()->add(
                        'ordered_ids',
                        'All tasks in the reorder request must belong to this list.'
                    );
                }
            }
        });
    }
}

