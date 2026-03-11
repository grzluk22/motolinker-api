<?php

use App\Kernel;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__).'/vendor/autoload.php';

if (!isset($_SERVER['APP_ENV'])) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();
$container = $kernel->getContainer();

// public services are not always available directly
// We can try to get 'doctrine' or entity manager
if ($container->has('doctrine')) {
    $doctrine = $container->get('doctrine');
    $categoryRepo = $doctrine->getRepository(App\Entity\Category::class);
    $articleRepo = $doctrine->getRepository(App\Entity\Article::class);
} else {
    // Fallback or error
    die("Doctrine service not found in container. Make sure services are public or use a different approach.\n");
}

echo "--- Testing CategoryRepository::getDescendantIds ---\n";

// Find a category with children
$categories = $categoryRepo->findAll();
$parents = [];
foreach ($categories as $cat) {
    if ($cat->getIdParent()) {
        $parents[$cat->getIdParent()][] = $cat->getId();
    }
}

if (empty($parents)) {
    echo "No categories with children found. Cannot test nesting logic deeply.\n";
} else {
    $parentId = array_key_first($parents);
    $directChildren = $parents[$parentId];
    echo "Testing Parent ID: $parentId (Direct Children: " . implode(', ', $directChildren) . ")\n";
    
    $descendants = $categoryRepo->getDescendantIds([$parentId]);
    echo "Descendants found: " . implode(', ', $descendants) . "\n";
    
    $missing = array_diff($directChildren, $descendants);
    if (empty($missing)) {
        echo "SUCCESS: Direct children found.\n";
    } else {
        echo "FAILURE: Missing direct children: " . implode(', ', $missing) . "\n";
    }
    
    if (in_array($parentId, $descendants)) {
        echo "SUCCESS: Parent ID included in result.\n";
    } else {
        echo "FAILURE: Parent ID missing from result.\n";
    }
}

echo "\n--- Testing ArticleRepository::findByExtended ---\n";

$criteria = ['id_category' => [2, 1]]; 
// Just ensuring no crash and logic runs
try {
    $articles = $articleRepo->findByExtended($criteria, [], 1, 0);
    echo "Query executed successfully. Result count (limit 1): " . count($articles) . "\n";
} catch (\Exception $e) {
    echo "ERROR executing query: " . $e->getMessage() . "\n";
}
