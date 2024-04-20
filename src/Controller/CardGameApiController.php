<?php

namespace App\Controller;

use App\Card\CardHand;
use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CardGameApiController extends AbstractController
{

    #[Route(
        "/api/deck",
        name: "api_deck",
        methods: ['GET'],
        options: ['description' => 'Get a sorted deck of cards.']
        )]
    public function jsonDeck(
        SessionInterface $session
    ): Response
    {
        $deck = new DeckOfCards();
        $session->set("deck", $deck);

        $data = [
        "deck" => $deck->getDeckStringArray()
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }

    #[Route(
        "/api/deck/shuffle",
        name: "api_shuffle",
        methods: ['POST'],
        options: ['description' => 'Shuffles and returns a deck of cards and saves it to the session.']
    )]
    public function jsonShuffle(
        SessionInterface $session
    ): Response
    {
        $deck = $session->get("deck");
        if (!$deck || count($deck->getDeck()) === 0) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        } else {
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $data = [
            "deck" => $deck->getDeckStringArray()
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }

    #[Route(
        "/api/deck/draw",
        name: "api_draw",
        methods: ['POST'],
        options: ['description' => 'Draws 1 card from the deck of cards in the session.']
    )]
    public function jsonDraw(
        SessionInterface $session,
    ): Response
    {
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $card = $deck->draw();
        if (!$card) {
            $response = new JsonResponse('No cards left in deck.');
                $response->setEncodingOptions(
                    $response->getEncodingOptions() | JSON_PRETTY_PRINT
                );
        
                return $response;
        }

        $session->set("deck", $deck);

        $data = [
            'drawnCard' => $card->getValue() . ' of ' . $card->getSuit(),
            'remainingCards' => count($deck->getDeck())
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }

    #[Route(
        "/api/deck/draw/{number<\d+>}",
        name:"api_draw_number",
        methods: ['POST'],
        options: ['description' => 'Draws the specified number of cards from the deck of cards in the session.'])]
    public function jsonDrawNum(
        Request $request,
        SessionInterface $session
    ): Response
    {
        $number = $request->request->get('number');
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $drawnCards = [];
        for ($i = 0; $i < $number; $i++) {
            $drawnCard = $deck->draw();
            if ($drawnCard) {
                $drawnCards[] = $drawnCard->getValue() . ' of ' . $drawnCard->getSuit();
            } else {
                $response = new JsonResponse('No cards left in deck.');
                $response->setEncodingOptions(
                    $response->getEncodingOptions() | JSON_PRETTY_PRINT
                );
        
                return $response;
            }
        }

        $session->set("deck", $deck);

        $data = [
            'drawnCards' => $drawnCards,
            'remainingCards' => count($deck->getDeck())
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

            return $response;
        }


    }
