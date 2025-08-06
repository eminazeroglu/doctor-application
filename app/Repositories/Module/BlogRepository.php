<?php

namespace App\Repositories\Module;

use App\Models\Blog;
use App\Repositories\BaseRepository;
use App\Services\Filter\BlogFilter;

class BlogRepository extends BaseRepository
{
    public function __construct(Blog $model)
    {
        parent::__construct($model);
        $this->setFilter(new BlogFilter(request()));
        $this->with = ['category'];
    }

    public function blogSearch() {
        return $this->executeQuery('blogSearch', function ($query) {
            $query->with('category');
            $query->active();
            $query = $this->filter->apply($query);
            return $query->paginate(request()->get('limit', 10));
        });
    }

    public function blogView($slug) {
        return $this->executeQuery('blogView', function ($query) use ($slug) {
            $query->with('category');
            $query->where('slug', $slug);
            return $query->firstOrFail();
        });
    }

    public function blogLast($blogId) {
        return $this->executeQuery('blogLast_' . $blogId, function ($query) use ($blogId) {
            $query->with('category');
            $query->where('id', '!=', $blogId);
            $query->latest();
            $query->limit(5);
            return $query->get();
        });
    }

    public function filters(): array
    {
        return [
            'categories' => app(CategoryRepository::class)->fetchCategoryByParent()->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'children' => $i->children,
            ]),
        ];
    }
}
