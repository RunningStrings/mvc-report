<?php

namespace App\Controller;

use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class CardGameController extends AbstractController
{
    #[Route("/card", name: "card")]
    public function card(): Response
    {
        $data = [
            "metadata" => $this->loadMetaData()
        ];
        
        return $this->render('card/home.html.twig', $data);
    }

    #[Route("/card/deck", name: "deck")]
    public function deck(
        SessionInterface $session
    ): Response
    {
        if ($session->has("deck")) {
            $deck = $session->get("deck");
            $deck->sortDeck();
            $session->set("deck", $deck);
        } else {
            $deck = new DeckOfCards();
            $session->set("deck", $deck);
        }
        $data = [
            "deck" => $deck->getDeck(),
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/deck.html.twig', $data);
    }

    #[Route("/card/deck/shuffle", name: "deck_shuffle")]
    public function shuffleDeck(
        SessionInterface $session
    ): Response
    {
        $deck = $session->get("deck");

        if ($session->has("deck")) {
            $deck = $session->get("deck");
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        } else {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $shuffledDeck = $deck;

        $data = [
            "deck" => $shuffledDeck->getDeck(),
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/shuffle.html.twig', $data);
    }

    #[Route("/card/deck/draw", name: "deck_draw")]
    public function draw(): Response
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
