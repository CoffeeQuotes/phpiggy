<?php

declare(strict_types=1);

function dd(mixed $val)
{
    echo '<pre>';
    var_dump($val);
    echo '</pre>';
    die();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value);
}
