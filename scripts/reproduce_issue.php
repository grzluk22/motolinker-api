<?php

use App\Kernel;
use App\Entity\Article;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__).'/vendor/autoload.php';

if (!isset($_SERVER['APP_ENV'])) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();
$container = $kernel->getContainer();

if ($container->has('doctrine')) {
    $doctrine = $container->get('doctrine');
    $articleRepo = $doctrine->getRepository(Article::class);
} else {
    // If private, try generic approach or 'doctrine.orm.entity_manager'
    // But typically 'doctrine' is alias.
    // In test env, we might need to boot separate container.
    // Let's assume standard Symfony app structure.
    try {
        $articleRepo = $container->get('App\\Repository\\ArticleRepository');
    } catch (\Throwable $e) {
         $doctrine = $container->get('doctrine');
         $articleRepo = $doctrine->getRepository(Article::class);
    }
}

echo "--- Testing ArticleRepository::countByExtended with Array Criteria ---\n";

// Emulate the request payload: "criteria":{"id_category":[4,5]}
// We don't strictly need real IDs to test the SQL generation SYNTAX error.
// The error was SQLSTATE[42000]: Syntax error... near ', 5'
// This happens during query PREPARATION/EXECUTION. If IDs don't exist, it just returns 0 count.
$criteria = ['id_category' => [4, 5]]; 

try {
    $count = $articleRepo->countByExtended($criteria);
    echo "SUCCESS: Query executed. Count: $count\n";
} catch (\Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
