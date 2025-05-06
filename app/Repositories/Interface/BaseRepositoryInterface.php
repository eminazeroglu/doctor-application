<?php

namespace App\Repositories\Interface;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BaseRepositoryInterface
{
    public function setUseCache(bool $useCache);
    public function create(array $data);

    public function update(int $id, array $data);

    public function changeStatus(int $id, string $statusField = 'is_active');
    public function updateOrder(array $orders);

    public function findById(int $id);

    public function findAll();

    public function findOneWhere(array $conditions);

    public function findWhere(array $conditions);
    public function findWhereIn(array $conditions);
    public function updateWhere(array $conditions, array $data);
    public function deleteWhere(array $conditions);

    public function paginateAndFilter(): LengthAwarePaginator|Collection;

    public function findActiveList(): Collection;

    public function delete(int $id): mixed;
}
