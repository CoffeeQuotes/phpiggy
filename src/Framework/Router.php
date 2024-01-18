<?php

declare(strict_types=1);

namespace Framework;


class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $errorHandler;

    public function add(string $method, string $path, array $controller): void
    {
        $path = $this->normalizePath($path);
        $regexPath = preg_replace('#{[^/]+}#', '([^/]+)', $path);
        $this->routes[] = [
            'path' => $path,
            'method' => strtoupper($method),
            'controller' => $controller,
            'middlewares' => [],
            'regexPath' => $regexPath
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
        // Convert the HTTP method to uppercase for consistency
        $method = strtoupper($_POST['_METHOD'] ?? $method);

        // Normalize the path by removing any trailing slashes
        $path = $this->normalizePath($path);

        // Iterate through each defined route
        foreach ($this->routes as $route) {
            /**
             * Use preg_match to check if $path matches $route['path']. 
             * We use preg_match to support route parameters in the path.
             * If the path doesn't match or the HTTP method doesn't match, continue to the next route.
             */
            if (!preg_match("#^{$route['regexPath']}$#", $path, $paramValues) || $route['method'] !== $method) {
                continue;
            }

            // Shift the first element from $paramValues as it contains the entire matched path
            array_shift($paramValues);

            // Extract parameter keys from the route path using a regular expression
            preg_match_all('#{([^/]+)}#', $route['path'], $paramKeys);
            $paramKeys = $paramKeys[1];

            // Combine parameter keys with their corresponding values
            $params = array_combine($paramKeys, $paramValues);

            // Extract controller class and method from the route configuration
            $controller = $route['controller'];
            [$class, $function] = $controller;

            // Resolve or instantiate the controller instance using the dependency injection container
            $controllerInstance = $container ? $container->resolve($class) : new $class();

            // Define the action as a closure that calls the specified method on the controller with the parameters
            $action = fn () => $controllerInstance->{$function}($params);

            // Combine route-specific middlewares with global middlewares
            $allMiddlewares = [...$route['middlewares'], ...$this->middlewares];

            // Iterate through each middleware and wrap the action in its processing logic
            foreach ($allMiddlewares as $middleware) {
                $middlewareInstance = $container ? $container->resolve($middleware) : new $middleware;
                $action = fn () => $middlewareInstance->process($action);
            }

            // Execute the final action, which may have been modified by middlewares
            $action();

            // Return to exit the loop after the first matching route is found and processed
            return;
        }

        $this->dispatchNotFound($container);
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

    public function setErrorHandler(array $controller)
    {
        $this->errorHandler = $controller;
    }

    public function dispatchNotFound(?Container $container)
    {
        [$class, $function] = $this->errorHandler;
        $controllerInstance = $container ? $container->resolve($class) : new $class;
        $action = fn () => $controllerInstance->$function();
        foreach ($this->middlewares as $middleware) {
            $middlewareInstance = $container ? $container->resolve($middleware) : new $middleware;
            $action = fn () => $middlewareInstance->process($action);
        }
        $action();
    }
}
