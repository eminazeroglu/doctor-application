<?php

namespace App\Http\Routing;

use Illuminate\Routing\ResourceRegistrar;

class ApiResourceRegister extends ResourceRegistrar
{
    protected $resourceDefaults = [
        'index',
        'filters',
        'order',
        'bulk',
        'store',
        'show',
        'action',
        'update',
        'destroy',
        'destroyAll',
    ];

    protected function addResourceDestroyAll($name, $base, $controller, $options): \Illuminate\Routing\Route
    {
        $uri    = $this->getResourceUri($name);
        $action = $this->getResourceAction($name, $controller, 'destroyAll', $options);
        return $this->router->delete($uri, $action);
    }

    protected function addResourceAction($name, $base, $controller, $options): \Illuminate\Routing\Route
    {
        $uri    = $this->getResourceUri($name . '/{' . str($name)->singular()->replace('-', '_') . '}/action');
        $action = $this->getResourceAction($name, $controller, 'action', $options);
        return $this->router->post($uri, $action);
    }

    protected function addResourceFilters($name, $base, $controller, $options): \Illuminate\Routing\Route
    {
        $uri    = $this->getResourceUri($name . '/filters');
        $action = $this->getResourceAction($name, $controller, 'filters', $options);
        return $this->router->get($uri, $action);
    }

    protected function addResourceOrder($name, $base, $controller, $options): \Illuminate\Routing\Route
    {
        $uri    = $this->getResourceUri($name . '/order');
        $action = $this->getResourceAction($name, $controller, 'order', $options);
        return $this->router->post($uri, $action);
    }

    protected function addResourceBulk($name, $base, $controller, $options): \Illuminate\Routing\Route
    {
        $uri    = $this->getResourceUri($name . '/bulk/{type}');
        $action = $this->getResourceAction($name, $controller, 'bulk', $options);
        return $this->router->post($uri, $action);
    }
}
