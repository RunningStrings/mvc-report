<?php

namespace App\Controller;

use DateTime;
use Exception;
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
    private CacheInterface $cache;
    private Yaml $yamlParser;

    public function __construct(CacheInterface $cache, Yaml $yamlParser)
    {
        $this->cache = $cache;
        $this->yamlParser = $yamlParser;
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
        $sidebarItems = [];
        $markdownContents = [];
        foreach ($finder as $file) {
            $reportFiles[] = $file->getFilename();
            $kmomId = str_replace('.markdown.twig', '', $file->getFilename());
            $header = $this->getHeaderFromFile($file->getPathname());
            $sidebarItems[] = [
                'kmomId' => $kmomId,
                'header' => $header,
            ];
            $markdownContents[] = file_get_contents($file->getPathname());
        }

        sort($reportFiles);
        sort($sidebarItems);
        sort($markdownContents);

        $metadata = $this->loadMetaData();
        return $this->render('report.html.twig', [
            'metadata' => $metadata,
            'reportFiles' => $reportFiles,
            'sidebarItems' => $sidebarItems,
            'markdownContents' => $markdownContents,
        ]);
    }

    private function getHeaderFromFile(string $filePath): string
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new Exception('Kunde inte läsa filen: $filepath');
        }

        $lines = explode("\n", $content);
        $headerLine = $lines[0];
        $header = trim(strip_tags($headerLine));
        return $header;
    }

    #[Route("/lucky", name: "lucky")]
    public function lucky(): Response
    {
        $number = random_int(0, 100);

        $imageMap = [
            0 => 'img1.jpg',
            21 => 'img2.jpg',
            41 => 'img3.jpg',
            61 => 'img4.jpg',
            81 => 'img5.gif',
        ];

        $imagePath = '';
        foreach ($imageMap as $min => $image) {
            if ($number >= $min && $number <= $min + 20) {
                $imagePath = './img/' . $image;
                break;
            }
        }

        $cssClass = $number > 50 ? 'magic-number-high' : 'magic-number-low';

        $metadata = $this->loadMetaData();
        $data = [
            'number' => $number,
            'css_class' => $cssClass,
            'image_path' => $imagePath,
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
            if (is_string($controller) && str_contains($controller, 'json')) {
                $description = $route->getOption('description');
                $jsonRoutes[] = [
                    'name' => $name,
                    'path' => $route->getPath(),
                    'description' => $description,
                    'method' => implode(', ', $route->getMethods()),
                ];
            }
        }

        $metadata = $this->loadMetaData();

        $data = [
            'jsonRoutes' => $jsonRoutes,
            'metadata' => $metadata
        ];

        return $this->render('api.html.twig', $data);
    }

    #[Route(
        "/api/quote",
        name: "api_quote",
        options: ['description' => 'Visa dagens citat.']
    )]
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
            $item->expiresAt(new DateTime('tomorrow'));
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

    /**
     * @return array<array<string, mixed>>
     */
    private function loadQuotes(): array
    {
        $quotesData = $this->yamlParser->parseFile('../config/quotes.yaml');

        if(is_array($quotesData) && isset($quotesData['quotes'])) {
            return $quotesData['quotes'];
        }
        return [];
    }

    /**
     * @return array<string>
     */
    private function loadMetaData(): array
    {
        $metadata = $this->yamlParser->parseFile('../config/metadata.yaml');
        if (is_array($metadata)) {
            return $metadata['metadata'] ?? [];
        }
        return [];
    }
}
