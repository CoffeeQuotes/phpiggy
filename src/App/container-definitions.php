<?php

// Enable strict type checking for this file
declare(strict_types=1);

// Import classes from namespaces for better readability
use Framework\{TemplateEngine, Database, Container};
use App\Config\Paths;
use App\Services\{ValidatorService, UserService};

// Define and return an associative array representing a dependency injection container configuration
return [
    // Define a factory function for creating TemplateEngine instances
    TemplateEngine::class => fn () => new TemplateEngine(Paths::VIEW),

    // Define a factory function for creating ValidatorService instances
    ValidatorService::class => fn () => new ValidatorService(),

    // Define a factory function for creating Database instances with configuration from environment variables
    Database::class => fn () => new Database(
        $_ENV['DB_DRIVER'],
        ['host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'], 'dbname' => $_ENV['DB_NAME']],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    ),

    // Define a factory function for creating UserService instances, with dependencies injected from the container
    UserService::class => function (Container $container) {
        // Get an instance of Database from the container
        $db = $container->get(Database::class);

        // Create and return a new UserService instance with the Database instance as a dependency
        return new UserService($db);
    }
];
