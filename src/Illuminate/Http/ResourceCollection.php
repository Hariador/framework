<?php

namespace Illuminate\Http;

use Exception;
use IteratorAggregate;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\AbstractPaginator;

class ResourceCollection extends Resource implements IteratorAggregate
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var \Illuminate\Support\Collection
     */
    public $collection;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Map the given collection resource into its individual resources.
     *
     * @param  mixed  $resource
     * @return mixed
     */
    protected function collectResource($resource)
    {
        $this->collection = $resource->mapInto($this->collects());

        return $resource instanceof AbstractPaginator
                    ? $resource->setCollection($this->collection)
                    : $this->collection;
    }

    /**
     * Get the resource that this resource collects.
     *
     * @return string
     */
    protected function collects()
    {
        if ($this->collects) {
            return $this->collects;
        }

        if (Str::endsWith(class_basename($this), 'Collection') &&
            class_exists($class = Str::replaceLast('Collection', '', get_class($this)))) {
            return $class;
        }

        throw new Exception(
            'The ['.get_class($this).'] resource must specify the models it collects.'
        );
    }

    /**
     * Create a new JSON resource response for the given resource.
     *
     * @return \App\ResourceResponse
     */
    public function json()
    {
        return $this->resource instanceof AbstractPaginator
                    ? new Resources\PaginatedJsonResourceResponse($this)
                    : parent::json();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toJson($request)
    {
        return $this->resource->map(function ($item) use ($request) {
            return $item->toJson($request);
        })->all();
    }

    /**
     * Get an iterator for the resource collection.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        if ($this->collection instanceof IteratorAggregate) {
            return $this->collection->getIterator();
        }

        throw new Exception(
            "Unable to generate an iterator for this resource collection."
        );
    }
}
