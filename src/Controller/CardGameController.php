<?php

namespace App\Controller;

use App\Game\Game;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class CardGameController extends AbstractController
{
    private Yaml $yamlParser;

    public function __construct(Yaml $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    #[Route("/game", name: "game")]
    public function game(): Response
    {
        $data = [
            "title" => "The Game",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('game/home.html.twig', $data);
    }

    #[Route("/game/doc", name: "doc")]
    public function doc(): Response
    {
        $data = [
            "title" => "Dokumentation",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('game/doc.html.twig', $data);
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