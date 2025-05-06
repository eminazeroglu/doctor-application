<?php

namespace App\Services\Module;

use App\Models\PageWidget;
use App\Repositories\Module\PageRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PageService extends BaseCrudService
{
    public function __construct(PageRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Slug ilə səhifəni tapmaq
     */
    public function findBySlug(string $slug, string $locale = null): Model
    {
        return $this->repository->findBySlug($slug, $locale);
    }

    /**
     * Səhifə növünə görə səhifələri tapmaq
     */
    public function findByType(string $type)
    {
        return $this->repository->findByType($type);
    }

    /**
     * Widget yaratmaq
     */
    public function createWidget(array $data): PageWidget
    {
        return $this->repository->createWidget($data);
    }

    /**
     * Widget yeniləmək
     */
    public function updateWidget(int $id, array $data): PageWidget
    {
        return $this->repository->updateWidget($id, $data);
    }

    /**
     * Widget silmək
     */
    public function deleteWidget(int $id): bool
    {
        return $this->repository->deleteWidget($id);
    }

    /**
     * Widget-ın statusunu dəyişmək
     */
    public function changeWidgetStatus(int $id): PageWidget
    {
        return $this->repository->changeWidgetStatus($id);
    }

    /**
     * Səhifəyə aid widget-ları tapmaq
     */
    public function findWidgetsByPageId(int $pageId): Collection
    {
        return $this->repository->findWidgetsByPageId($pageId);
    }

    /**
     * Widget-ı ID ilə tapmaq
     */
    public function findWidgetById(int $id): PageWidget
    {
        return $this->repository->findWidgetById($id);
    }

    /**
     * Widget-ların sırasını yeniləmək
     */
    public function updateWidgetsOrder(array $orders): bool
    {
        return $this->repository->updateWidgetsOrder($orders);
    }
}
