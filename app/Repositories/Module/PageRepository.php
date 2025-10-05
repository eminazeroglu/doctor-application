<?php

namespace App\Repositories\Module;

use App\Enums\PageTypeEnum;
use App\Enums\WidgetTypeEnum;
use App\Models\Page;
use App\Models\PageWidget;
use App\Repositories\BaseRepository;
use App\Services\Filter\PageFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageRepository extends BaseRepository
{
    protected PageWidget $widgetModel;

    public function __construct(Page $model, PageWidget $widgetModel, Request $request)
    {
        parent::__construct($model);

        $this->widgetModel = $widgetModel;

        if ($request->has('with')) {
            $this->with = explode(',', $request->get('with'));
        } else {
            // Default olarak widgetları yükləyirik
            $this->with = ['widgets'];
        }

        $this->filter = app(PageFilter::class);
    }

    public function filters(): array
    {
        return [
            'page_types' => collect(PageTypeEnum::getValues())->map(fn($type) => [
                'id' => $type,
                'name' => PageTypeEnum::getDescription($type)
            ]),
            'widget_types' => collect(WidgetTypeEnum::getValues())->map(fn($type) => [
                'id' => $type,
                'name' => WidgetTypeEnum::getDescription($type)
            ])
        ];
    }

    /**
     * Slug ilə axtarmaq üçün metodu override edirik və widgetları yükləyirik
     */
    public function findBySlug(string $slug, ?string $locale = null): Page
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        if ($locale && method_exists($this->model, 'getTranslatableAttributes')) {
            return $query->where(function ($q) use ($slug, $locale) {
                $q->where('slug', $slug)
                    ->orWhereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(translates, '$.{$locale}.slug')) = ?",
                        [$slug]
                    );
            })
                ->where('is_active', true)
                ->firstOrFail();
        }

        return $query
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Səhifə növünə görə səhifələri tapmaq
     */
    public function findByType(string $type): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        return $query->where('type', $type)->get();
    }

    /**
     * Widget yaratmaq
     * @throws \Throwable
     */
    public function createWidget(array $data): PageWidget
    {
        try {
            DB::beginTransaction();

            $widget = $this->widgetModel->create($data);

            DB::commit();

            return $widget;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Widget yeniləmək
     * @throws \Throwable
     */
    public function updateWidget(int $id, array $data): PageWidget
    {
        try {
            DB::beginTransaction();

            $widget = $this->widgetModel->findOrFail($id);
            $widget->update($data);

            DB::commit();

            return $widget;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Widget silmək
     * @throws \Throwable
     */
    public function deleteWidget(int $id): bool
    {
        try {
            DB::beginTransaction();

            $widget = $this->widgetModel->findOrFail($id);
            $result = $widget->delete();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Widget-ın statusunu dəyişmək
     */
    public function changeWidgetStatus(int $id): PageWidget
    {
        $widget = $this->widgetModel->findOrFail($id);
        $widget->is_active = !$widget->is_active;
        $widget->save();

        return $widget;
    }

    /**
     * Səhifəyə aid widget-ları tapmaq
     */
    public function findWidgetsByPageId(int $pageId): Collection
    {
        return $this->widgetModel->where('page_id', $pageId)
            ->orderBy('order')
            ->get();
    }

    /**
     * Widget-ı ID ilə tapmaq
     */
    public function findWidgetById(int $id): PageWidget
    {
        return $this->widgetModel->findOrFail($id);
    }

    /**
     * Widget-ların sırasını yeniləmək
     * @throws \Throwable
     */
    public function updateWidgetsOrder(array $orders): bool
    {
        try {
            DB::beginTransaction();

            foreach ($orders as $item) {
                $this->widgetModel->where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
