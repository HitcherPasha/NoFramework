<?php

declare(strict_types=1);

use App\Seeder\DatabaseSeeder;

$app = require dirname(__DIR__) . '/src/bootstrap.php';

$fresh = in_array('--fresh', $argv ?? [], true);

echo $fresh ? "Seeding database (fresh reset)...\n" : "Seeding database...\n";

$seeder = new DatabaseSeeder($app['pdo']);
$seeder->run($fresh);

echo "Done.\n";
