<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class CardGameController extends AbstractController
{
    #[Route("/game/card", name: "card")]
    public function card(): Response
    {
        $data = [
            "metadata" => $this->loadMetaData()
        ];
        
        return $this->render('card/home.html.twig', $data);
    }

    #[Route("/game/card/deck", name: "card_deck")]
    public function home(): Response
    {
        $data = [
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/deck.html.twig', $data);
    }

    private function loadMetaData()
    {
        $metadata = Yaml::parseFile('../config/metadata.yaml');
        return $metadata['metadata'] ?? [];
    }
}
