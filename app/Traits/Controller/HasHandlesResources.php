<?php

namespace App\Traits\Controller;

use App\Http\Resources\Admin\BaseResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

trait HasHandlesResources
{
    public string $resourceClass = BaseResource::class;
    public $treeResourceClass = null;

    /**
     * Resurs sinfini təyin edir
     *
     * @param string $resourceClass
     * @return $this
     */
    public function setResource(string $resourceClass): self
    {
        if (!is_subclass_of($resourceClass, JsonResource::class)) {
            throw new \InvalidArgumentException("The provided class must be a subclass of JsonResource.");
        }
        $this->resourceClass = $resourceClass;
        return $this;
    }

    /**
     * Ağac strukturlu resurs sinfini təyin edir
     *
     * @param string $resourceClass
     * @return $this
     */
    public function setTreeResource(string $resourceClass): self
    {
        if (!is_subclass_of($resourceClass, JsonResource::class)) {
            throw new \InvalidArgumentException("The provided class must be a subclass of JsonResource.");
        }
        $this->treeResourceClass = $resourceClass;
        return $this;
    }

    /**
     * Verilənləri uyğun resursa çevirir
     *
     * @param mixed $data
     * @return AnonymousResourceCollection|JsonResource
     */
    public function toResource(mixed $data): AnonymousResourceCollection|JsonResource
    {
        $resourceClass = (request()->has('tree') && $this->treeResourceClass)
            ? $this->treeResourceClass
            : $this->resourceClass;

        if ($data instanceof \Illuminate\Database\Eloquent\Collection ||
            $data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return $resourceClass::collection($data);
        }

        return new $resourceClass($data);
    }

    public function setHasShowResource($value): void
    {
        $this->hasShowResource = $value;
    }
}
