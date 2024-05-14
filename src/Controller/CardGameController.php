<?php

namespace App\Controller;

use App\Card\DeckOfCards;
use App\Game\Game;
use App\Game\Player;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route("/game/doc", name: "game_doc")]
    public function doc(): Response
    {
        $data = [
            "title" => "Dokumentation",
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('game/doc.html.twig', $data);
    }

    #[Route("/game/init", name: "game_init")]
    public function init(SessionInterface $session): Response
    {
        $game = $session->get("game");
        if (!$game || !$game->getDeck() || count($game->getDeck()->getDeck()) === 0 || $game->isGameOver()) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();

            $player = new Player('Player', $deck);

            $bank = new Player('Bank', $deck);

            $game = new Game($deck, $player, $bank);
            $session->set("game", $game);
        } else {
            $game->resetGame();
        }

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/bet", name: "game_bet", methods: ['POST'])]
    public function bet(
        Request $request,
        SessionInterface $session
        ): Response {
            $game = $session->get("game");

            $amount = $request->request->getInt('amount');

            $game->placeBet($amount);
            $session->set("game", $game);

            return $this->redirectToRoute('game_play');
        }

    #[Route("/game/play", name: "game_play", methods: ['GET'])]
    public function play(SessionInterface $session): Response
    {
        $game = $session->get('game');

        $players = $game->getPlayers();

        $data = [
            "game" => $game,
            "title" => "Tjugoett",
            "players" => $players,
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('game/play.html.twig', $data);
    }

    #[Route("/game/hit", name:"game_hit", methods: ['GET'])]
    public function hit(
        SessionInterface $session
    ): Response {
        $game = $session->get("game");

        if ($flashData = $game->playerTurn()) {
            $this->addFlash($flashData['type'], $flashData['message']);
        }

        $session->set("game", $game);

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/stand", name:"game_stand", methods: ['GET'])]
    public function stand(
        SessionInterface $session
    ): Response {
        $game = $session->get("game");

        if ($flashData = $game->bankTurn()) {
            $this->addFlash($flashData['type'], $flashData['message']);
        }

        $session->set("game", $game);

        return $this->redirectToRoute('game_play');
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
