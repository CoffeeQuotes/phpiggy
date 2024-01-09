<?php

declare(strict_types=1);

namespace Framework;


class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function add(string $method, string $path, array $controller): void
    {
        $this->routes[] = [
            'path' => $this->normalizePath($path),
            'method' => strtoupper($method),
            'controller' => $controller,
            'middlewares' => []
        ];
    }

    private function normalizePath(string $path): string
    {
        /* trim will remove forward slahses (& whitespaces) from beginning and end of $path */
        $path =  trim($path, '/');
        $path = "/{$path}/";
        /* Preg_replace will replace all forward slashes with one forward slash  */
        $path = preg_replace('#[/]{2,}#', '/', $path);
        return $path;
    }

    public function dispatch(string $path, string $method, Container $container = null): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        foreach ($this->routes as $route) {
            /**
             * We are using preg_match to check if $path matches $route['path'] instead of using comparsion operator because of we want to support route parameters.  
             * */
            if (!preg_match("#^{$route['path']}$#", $path) || $route['method'] !== $method) {
                continue;
            }
            $controller = $route['controller'];
            [$class, $function] = $controller;
            $controllerInstance = $container ? $container->resolve($class) : new $class();
            $action = fn () => $controllerInstance->{$function}();
            $allMiddlewares = [...$route['middlewares'], ...$this->middlewares];

            foreach ($allMiddlewares as $middleware) {
                $middlewareInstance = $container ? $container->resolve($middleware) :  new $middleware;
                $action = fn () => $middlewareInstance->process($action);
            }
            $action();
            return;
        }
    }

    public function addMiddleware(string $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function addRouteMiddleware(string $middleware)
    {
        $lastRouteKey = array_key_last($this->routes);
        $this->routes[$lastRouteKey]['middlewares'][] = $middleware;
    }
}
