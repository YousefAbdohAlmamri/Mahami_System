<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): User;

    public function findByEmail(string $email): ?User;

    /**
     * @param  array<int, string>  $names
     */
    public function findByNames(array $names): Collection;

    public function create(array $data): User;
}
