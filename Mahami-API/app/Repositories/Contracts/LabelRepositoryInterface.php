<?php

namespace App\Repositories\Contracts;

use App\Models\Board;
use App\Models\Label;
use Illuminate\Database\Eloquent\Collection;

interface LabelRepositoryInterface
{
    public function forBoard(Board $board): Collection;

    public function create(Board $board, array $data): Label;

    public function update(Label $label, array $data): Label;

    public function delete(Label $label): bool;
}
