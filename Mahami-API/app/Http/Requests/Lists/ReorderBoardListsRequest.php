<?php

namespace App\Http\Requests\Lists;

use App\Models\Board;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderBoardListsRequest extends FormRequest
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
            'ordered_ids.*' => ['required', 'integer', 'distinct', 'exists:board_lists,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $board = $this->route('board');
            $boardId = $board instanceof Board ? $board->id : (int) $board;

            $orderedIds = $this->input('ordered_ids', []);
            if (!empty($orderedIds) && $boardId) {
                $validCount = app(BoardListRepositoryInterface::class)
                    ->countInBoard((int) $boardId, $orderedIds);

                if ($validCount !== count($orderedIds)) {
                    $validator->errors()->add(
                        'ordered_ids',
                        'All lists in the reorder request must belong to this board.'
                    );
                }
            }
        });
    }
}

