<?php

namespace App\Repositories\Module;

use App\Models\Faq;
use App\Repositories\BaseRepository;
use App\Services\Filter\FaqFilter;

class FaqRepository extends BaseRepository
{
    public function __construct(Faq $model)
    {
        parent::__construct($model);
        $this->setFilter(new FaqFilter(request()));
    }

    public function filters(): array
    {
        return [
            'demo' => 'test'
        ];
    }
}
