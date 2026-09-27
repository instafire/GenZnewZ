<?php

namespace Botble\Base\Supports;

use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Routing\Route;

class CustomResourceRegistrar extends ResourceRegistrar
{
    protected $resourceDefaults = ['index', 'create', 'store', 'edit', 'update', 'destroy'];

    protected function addResourceEdit($name, $base, $controller, $options): Route
    {
        $uri = $this->getResourceUri($name) . '/' . static::$verbs['edit'] . '/{' . $base . '}';

        $action = $this->getResourceAction($name, $controller, 'edit', $options);

        return $this->router->get($uri, $action)->wherePrimaryKey($base);
    }

    protected function addResourceUpdate($name, $base, $controller, $options): Route
    {
        $uri = $this->getResourceUri($name) . '/' . static::$verbs['edit'] . '/{' . $base . '}';

        $action = $this->getResourceAction($name, $controller, 'update', $options);

        return $this->router->post($uri, $action)->wherePrimaryKey($base);
    }

    protected function addResourceStore($name, $base, $controller, $options): Route
    {
        $uri = $this->getResourceUri($name) . '/' . static::$verbs['create'];

        $action = $this->getResourceAction($name, $controller, 'store', $options);

        return $this->router->post($uri, $action);
    }

    protected function addResourceIndex($name, $base, $controller, $options): Route
    {
        $uri = $this->getResourceUri($name);

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'index', $options);

        return $this->router->match(['GET', 'POST'], $uri, $action);
    }

    protected function addResourceDestroy($name, $base, $controller, $options): Route
    {
        return parent::addResourceDestroy($name, $base, $controller, $options)->wherePrimaryKey($base);
    }
}
