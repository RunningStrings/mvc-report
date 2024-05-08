<?php

namespace App\Controller;

use App\Card\CardHand;
use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class CardController extends AbstractController
{
    private Yaml $yamlParser;

    public function __construct(Yaml $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    #[Route("/card", name: "card")]
    public function card(): Response
    {
        $data = [
            "title" => "Spelkort",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/home.html.twig', $data);
    }

    #[Route("/card/deck", name: "deck")]
    public function deck(
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $session->set("deck", $deck);
        }
        if ($deck->isEmpty()) {
            $this->addFlash(
                'warning',
                'Alla kort i leken har dragits!'
            );
        }
        $deck->sortDeck();

        $data = [
            "deck" => $deck->getDeck(),
            "title" => "Kortlek",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/deck.html.twig', $data);
    }

    #[Route("/card/deck/shuffle", name: "deck_shuffle")]
    public function shuffleDeck(
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        if (!$deck || $deck->isEmpty()) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
        }
        $deck->shuffleDeck();

        $session->set("deck", $deck);

        $data = [
            "deck" => $deck->getDeck(),
            "title" => "Blanda kortleken",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/shuffle.html.twig', $data);
    }

    #[Route("/card/deck/draw", name: "deck_draw")]
    public function draw(
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $session->set("deck", $deck);
        }

        $remainingCards = count($deck->getDeck());

        if ($remainingCards === 0) {
            $this->addFlash(
                'warning',
                'Alla kort i leken har dragits!'
            );
        }

        $remainingCards = max(0, $remainingCards - 1);

        $drawnCards = $deck->draw();

        $session->set("deck", $deck);

        $data = [
            "deck" => $deck->getDeck(),
            "drawnCards" => $drawnCards,
            "remainingCards" => $remainingCards,
            "title" => "Dra ett kort",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/draw.html.twig', $data);
    }

    #[Route("/card/deck/draw/{number<\d+>}", name: "draw_many")]
    public function drawMany(
        int $number,
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $session->set("deck", $deck);
        }

        $remainingCards = count($deck->getDeck());

        if ($remainingCards === 0) {
            $this->addFlash(
                'warning',
                'Alla kort i leken har dragits!'
            );
        }

        $remainingCards = max(0, $remainingCards - $number);

        $drawnCards = [];
        for ($i = 0; $i < $number && ($drawnCard = $deck->draw()); $i++) {
            $drawnCards[] = $drawnCard;
        }
        // for ($i = 0; $i < $number; $i++) {
        //     $drawnCard = $deck->draw();
        //     if ($drawnCard) {
        //         $drawnCards[] = $drawnCard;
        //     } else {
        //         break;
        //     }
        // }

        $session->set("deck", $deck);

        $data = [
            "deck" => $deck->getDeck(),
            "drawnCards" => $drawnCards,
            "remainingCards" => $remainingCards,
            "title" => "Dra kort ur leken",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/draw.html.twig', $data);
    }

    #[Route("/card/deck/deal/{players}/{cards}", name: "deal_cards")]
    public function dealCards(
        int $players,
        int $cards,
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards|null $deck */
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
            $session->set("deck", $deck);
        }

        $playerHands = [];
        for ($i = 0; $i < $players; $i++) {
            $playerHands[] = new CardHand($deck);
        }

        for ($i = 0; $i < $cards; $i++) {
            foreach ($playerHands as $playerHand) {
                if ($deck->isEmpty()) {
                    $this->addFlash(
                        'warning',
                        'Alla kort i leken har dragits!'
                    );
                    break 2;
                }
                $card = $deck->draw();
                if ($card !== null) {
                    $playerHand->addCard($card);
                }
            }
        }
        // for ($i = 0; $i < $cards; $i++) {
        //     foreach ($playerHands as $playerHand) {
        //         $card = $deck->draw();
        //         if ($card) {
        //             $playerHand->addCard($card);
        //         } else {
        //             $this->addFlash(
        //                 'warning',
        //                 'Alla kort i leken har dragits!'
        //             );
        //             break 2;
        //         }
        //     }
        // }

        $remainingCards = count($deck->getDeck());

        $data = [
            "playerHands" => $playerHands,
            "remainingCards" => $remainingCards,
            "players" => $players,
            "cards" => $cards,
            "title" => "Utdelade kort",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/deal.html.twig', $data);
    }

    #[Route("/card/deck/create-and-shuffle/{source}", name: "create_and_shuffle")]
    public function createAndShuffle(
        string $source,
        SessionInterface $session
    ): Response {
        $deck = new DeckOfCards();
        $deck->shuffleDeck();
        $session->set("deck", $deck);

        switch ($source) {
            case 'from_draw':
                return $this->redirectToRoute('deck_draw');
            case 'from_deal':
                return $this->redirectToRoute('deal_cards');
            case 'from_shuffle':
                return $this->redirectToRoute('deck_shuffle');
            default:
                return $this->redirectToRoute('deck_shuffle');
        }
    }

    #[Route("/card/deck/create", name: "create")]
    public function createSorted(
        SessionInterface $session
    ): Response {
        $deck = new DeckOfCards();
        $session->set("deck", $deck);

        $data = [
            "deck" => $deck->getDeck(),
            "title" => "Kortlek",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('card/deck.html.twig', $data);
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
