<?php

declare(strict_types=1);

namespace Framework;


class Router
{
    private array $routes = [];

    public function add(string $method, string $path, array $controller): void
    {
        $this->routes[] = [
            'path' => $this->normalizePath($path),
            'method' => strtoupper($method),
            'controller' => $controller
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

    public function dispatch(string $path, string $method): void
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
            $controllerInstance = new $class();
            $controllerInstance->{$function}();
            return;
        }

        // http_response_code(404);
        // echo "Page not found";
    }
}
