<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MyController extends AbstractController
{
    // Inject CacheInterface
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    #[Route("/", name: "home")]
    public function home(): Response
    {
        $metadata = $this->loadMetaData();
        return $this->render('home.html.twig', [
            'metadata' => $metadata,
        ]);
    }

    #[Route("/about", name: "about")]
    public function about(): Response
    {
        $metadata = $this->loadMetaData();
        return $this->render('about.html.twig', [
            'metadata' => $metadata,
        ]);
    }

    #[Route("/report", name: "report")]
    public function report(): Response
    {
        $reportDir = "../templates/report";

        $finder = new Finder();
        $finder->files()->in($reportDir)->name('*.markdown.twig');

        $reportFiles = [];
        foreach ($finder as $file) {
            $reportFiles[] =$file->getFilename();
        }

        sort($reportFiles);

        $metadata = $this->loadMetaData();
        return $this->render('report.html.twig', [
            'metadata' => $metadata,
            'reportFiles' => $reportFiles,
        ]);
    }

    #[Route("/lucky", name: "lucky")]
    public function lucky(): Response
    {
        $number = random_int(0, 100);

        $metadata = $this->loadMetaData();
        $data = [
            'number' => $number,
            'metadata' => $metadata,
        ];

        return $this->render('lucky.html.twig', $data);
    }

    #[Route("/api", name: "api")]
    public function api(RouterInterface $router): Response
    {
        $routes = $router->getRouteCollection()->all();

        $jsonRoutes = [];
        foreach ($routes as $name => $route) {
            $controller = $route->getDefault('_controller');
            if (str_contains($controller, 'json')) {
                $jsonRoutes[] = [
                    'name' => $name,
                    'path' => $route->getPath(),
                ];
            }
        }

        $metadata = $this->loadMetaData();

        return $this->render('api.html.twig', [
            'jsonRoutes' => $jsonRoutes,
            'metadata' => $metadata,
        ]);
    }

    #[Route("/api/quote", name: "api_quote")]
    public function jsonQuote(): Response
    {
        // Generate cache key
        $cacheKey = 'quote_of_the_day_' . date('Y-m-d');

        // Fetch today's cached quote
        $cachedQuote = $this->cache->get($cacheKey, function (ItemInterface $item) {
            // Generate new quote if none in cache
            $quotes = $this->loadQuotes();
            $randomQuote = $quotes[array_rand($quotes)];
            $quoteData = [
                'quote' => $randomQuote['text'],
                'author' => $randomQuote['author'],
                'timestamp' => date('Y-m-d H:i:s'),
            ];
            // Store generated quote in cache until end of day
            $item->expiresAt(new \DateTime('tomorrow'));
            $item->set($quoteData);

            return $quoteData;
        });

        $cachedQuote['date'] = date('Y-m-d H:i:s');

        $response = new JsonResponse($cachedQuote);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }

    private function loadQuotes(): array
    {
        $quotesFile = __DIR__ . '/../../config/quotes.yaml';
        $quotesData = Yaml::parseFile($quotesFile);

        return $quotesData['quotes'] ?? [];
    }

    private function loadMetaData()
    {
        $metadata = Yaml::parseFile('../config/metadata.yaml');
        return $metadata['metadata'] ?? [];
    }
}