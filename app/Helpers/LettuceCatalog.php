<?php
namespace App\Helpers;

class LettuceCatalog
{
    public static function get(): array
    {
        return require __DIR__ . '/../../original/includes/lettuce-catalog.php';
    }
}
