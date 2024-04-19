<?php

namespace App\Controller;

use App\Card\CardHand;
use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CardGameApiController extends AbstractController
{
    #[Route("/api/deck", name: "deck_api", methods: ['GET'], options: ['description' => 'Get a sorted deck of cards.'])]
    public function jsonDeck(
        SessionInterface $session
    ): Response
    {
        $deck = new DeckOfCards();
        $session->set("deck", $deck);

        $data = [
        'deck' => $deck->getDeckStringArray()
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }
}
