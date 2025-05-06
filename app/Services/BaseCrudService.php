<?php

namespace App\Services;

use App\Repositories\Interface\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseCrudService
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Yeni resurs yaradır.
     *
     * @param array $data
     */
    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Mövcud resursu yeniləyir.
     *
     * @param int $id
     * @param array $data
     */
    public function update(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Resursun statusunu dəyişir (məsələn, is_active).
     *
     * @param int $id
     * @param string $statusField
     * @return Model
     */
    public function changeStatus(int $id, string $statusField = 'is_active'): Model
    {
        return $this->repository->changeStatus($id, $statusField);
    }

    /**
     * Resursa aid filterləri listələmək.
     *
     */
    public function filters()
    {
        return $this->repository->filters();
    }

    /**
     * Resursa aid toplu dəyişikliklər.
     *
     */
    public function bulk($type, $request)
    {
        return $this->repository->bulk($type, $request);
    }

    /**
     * ID ilə resursu tapır.
     *
     * @param int $id
     * @return Model
     */
    public function findById(int $id): Model
    {
        return $this->repository->findById($id);
    }

    /**
     * Datatable üçün səhifələmə və filtr ilə məlumatları geri qaytarır.
     *
     * @return LengthAwarePaginator|Collection
     */
    public function paginateAndFilter(): LengthAwarePaginator|Collection
    {
        return $this->repository->paginateAndFilter();
    }

    /**
     * Aktiv resursları geri qaytarır.
     *
     * @return Collection
     */
    public function findActiveList(): Collection
    {
        return $this->repository->findActiveList();
    }

    /**
     * Slug ilə resursu tapır.
     *
     * @param string $slug
     * @param string|null $locale
     * @return Model
     */
    public function findBySlug(string $slug, ?string $locale = null): Model
    {
        return $this->repository->findBySlug($slug, $locale);
    }

    /**
     * Lokalizasiya edilmiş slug ilə resursu tapır.
     *
     * @param string $slug
     * @param string $locale
     * @return Model
     */
    public function findByLocalizedSlug(string $slug, string $locale): Model
    {
        return $this->repository->findByLocalizedSlug($slug, $locale);
    }

    /**
     * ID ilə resursu silir.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Sıralamanı dəyişdirmək
     */
    public function updateOrder($request)
    {
        return $this->repository->updateOrder($request->orders);
    }
}
