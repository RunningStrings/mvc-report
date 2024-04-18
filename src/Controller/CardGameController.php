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
            "title" => "Spelkort",
            "metadata" => $this->loadMetaData()
        ];
        
        return $this->render('card/home.html.twig', $data);
    }

    #[Route("/card/deck", name: "deck")]
    public function deck(
        SessionInterface $session
    ): Response
    {
        $deck = $session->get("deck");

        if (!$deck) {
            $deck = new DeckOfCards();
            $session->set("deck", $deck);
        } else {
            if (count($deck->getDeck()) === 0) {
                $this->addFlash(
                    'warning',
                    'Alla kort i leken har dragits!'
                );
            } else {
                $deck->sortDeck();
            }
        }

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
    ): Response
    {
        $deck = $session->get("deck");

        if (!$deck || count($deck->getDeck()) === 0) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();
        } else {
            $deck->shuffleDeck();
        }

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
    ): Response
    {
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

        $remainingCards = max(0, $remainingCards -1);

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
    ): Response
    {
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
        for ($i = 0; $i < $number; $i++) {
            $drawnCard = $deck->draw();
            if ($drawnCard) {
                $drawnCards[] = $drawnCard;
            } else {
                break;
            }
        }

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

    #[Route("/card/deck/create-and-shuffle/{source}", name: "create_and_shuffle")]
    public function createAndShuffle(
        string $source,
        SessionInterface $session
    ): Response
    {
        $deck = new DeckOfCards();
        $deck->shuffleDeck();
        $session->set("deck", $deck);

        switch ($source) {
            case 'from_draw':
                return $this->redirectToRoute('deck_draw');
            case 'from_shuffle':
                return $this->redirectToRoute('deck_shuffle');
            default:
                return $this->redirectToRoute('deck_shuffle');
        }
    }

    #[Route("/card/deck/create", name: "create")]
    public function createSorted(
        SessionInterface $session
    ): Response
    {
        $deck = new DeckOfCards();
        $session->set("deck", $deck);

        $data = [
            "deck" => $deck->getDeck(),
            "title" => "Kortlek",
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
