<?php

namespace App\Repositories\Eloquent;

use App\Models\Board;
use App\Models\Label;
use App\Repositories\Contracts\LabelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentLabelRepository implements LabelRepositoryInterface
{
    public function forBoard(Board $board): Collection
    {
        return $board->labels()->latest()->get();
    }

    public function create(Board $board, array $data): Label
    {
        return $board->labels()->create($data);
    }

    public function update(Label $label, array $data): Label
    {
        $label->update($data);

        return $label;
    }

    public function delete(Label $label): bool
    {
        return (bool) $label->delete();
    }
}
