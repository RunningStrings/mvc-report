<?php

namespace App\Controller;

use App\Card\CardHand;
use App\Card\DeckOfCards;
use App\Game\Game;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CardApiController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route(
        "/api/deck",
        name: "api_deck",
        methods: ['GET'],
        options: ['description' => 'Visar en komplett, sorterad kortlek och lagrar den i sessionen.']
    )]
    public function jsonDeck(
        SessionInterface $session
    ): Response {
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
        options: ['description' => 'Blandar och visar en kortlek, och lagrar den i sessionen.']
    )]
    public function jsonShuffle(
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");
        if (!$deck || $deck->isEmpty()) {
            $deck = new DeckOfCards();
        }
        $deck->shuffleDeck();
        $session->set("deck", $deck);
        // }

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
        options: ['description' => 'Drar 1 kort från kortleken i sessionen.']
    )]
    public function jsonDraw(
        SessionInterface $session,
    ): Response {
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $card = $deck->draw();
        if (!$card) {
            $response = new JsonResponse('Inga kort kvar i leken.');
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
        options: ['description' => 'Drar det valda antalet kort från kortleken i sessionen.']
    )]
    public function jsonDrawNum(
        Request $request,
        SessionInterface $session
    ): Response {
        $number = $request->request->get('number');
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $drawnCards = [];
        for ($i = 0; $i < $number; $i++) {
            if ($deck->isEmpty()) {
                $response = new JsonResponse('Inga kort kvar i leken.');
                $response->setEncodingOptions(
                    $response->getEncodingOptions() | JSON_PRETTY_PRINT
                );
                return $response;
            }

            $drawnCard = $deck->draw();
            if ($drawnCard !== null) {
                $drawnCards[] = $drawnCard->getValue() . ' of ' . $drawnCard->getSuit();
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

    #[Route(
        "/api/deck/deal/{players<\d+>}/{cards<\d+>}",
        name: "api_deal_cards",
        methods: ['POST'],
        options: ['description' => 'Delar ut det valda antalet kort till det valda antalet spelare från kortleken i sessionen.']
    )]
    public function jsonDealCards(
        Request $request,
        SessionInterface $session
    ): Response {
        $cards = $request->request->get('cards');
        $players = $request->request->get('players');
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        $this->logger->info('Value of $cards: ' . $cards);
        $this->logger->info('Value of $players: ' . $players);

        if (!$deck) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $playerHands = [];
        for ($i = 0; $i < $players; $i++) {
            if ($deck instanceof DeckOfCards) {
                $playerHands["Spelare " . ($i + 1)] = new CardHand($deck);
            }
        }

        foreach ($playerHands as $player => $hand) {
            for ($i = 0; $i < $cards; $i++) {
                if ($deck->isEmpty()) {
                    $response = new JsonResponse('Inga kort kvar i leken.');
                    $response->setEncodingOptions(
                        $response->getEncodingOptions() | JSON_PRETTY_PRINT
                    );
                    return $response;
                }

                $card = $deck->draw();
                if ($card !== null) {
                    $hand->addCard($card);
                }
            }
        }

        $remainingCards = count($deck->getDeck());

        foreach ($playerHands as $player => $hand) {
            $playerHands[$player] = $hand->toHandArray();
        }

        $data = [
            "playerHands" => $playerHands,
            "remainingCards" => $remainingCards,
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }

    #[Route(
        "/api/game",
        name: "api_game",
        methods: ['GET'],
        options: ['description' => 'Visar den aktuella ställningen i kortspelet 21.']
    )]
    public function jsonGame(
        SessionInterface $session
    ): Response {
        /** @var Game|null $game */
        $game = $session->get('game');

        if (!$game) {
            return new JsonResponse('Inget Game objekt lagrat i sessionen.');
        }

        $response = new JsonResponse($game->toArray());
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }

}
