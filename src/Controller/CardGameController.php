<?php

namespace App\Controller;

use App\Game\Game;
use App\Game\GameFactory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class CardGameController extends AbstractController
{
    private GameFactory $gameFactory;
    private Yaml $yamlParser;

    public function __construct(GameFactory $gameFactory, Yaml $yamlParser)
    {
        $this->gameFactory = $gameFactory;
        $this->yamlParser = $yamlParser;
    }

    #[Route("/game", name: "game")]
    public function game(): Response
    {
        $data = [
            "title" => "Kortspel: 21",
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
        /** @var Game|null $game */
        $game = $session->get("game");
        if (!$game || count($game->getDeck()->getDeck()) === 0 || $game->isGameOver()) {
            $game = $this->gameFactory->createNewGame();
            $session->set("game", $game);
            return $this->redirectToRoute('game_play');
        }

        $game->resetGame();

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/bet", name: "game_bet", methods: ['POST'])]
    public function bet(
        Request $request,
        SessionInterface $session
    ): Response {
        /** @var Game|null $game */
        $game = $session->get("game");

        if ($game === null) {
            return $this->redirectToRoute('game');
        }

        $betAmount = $request->request->getInt('betAmount');

        $flashData = $game->placeBet($betAmount);

        if ($flashData !== null) {
            $this->addFlash($flashData['type'], $flashData['message']);
            return $this->redirectToRoute('game_play');
        }

        $session->set("game", $game);
        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/play", name: "game_play", methods: ['GET'])]
    public function play(SessionInterface $session): Response
    {
        /** @var Game|null $game */
        $game = $session->get('game');

        if ($game === null) {
            return $this->redirectToRoute('game');
        }

        $players = $game->getPlayers();
        $betAmount = $game->getBetAmount();

        $data = [
            "game" => $game,
            "title" => "21",
            "players" => $players,
            "betAmount" => $betAmount,
            "metadata" => $this->loadMetaData()
        ];

        return $this->render('game/play.html.twig', $data);
    }

    #[Route("/game/hit", name:"game_hit", methods: ['GET'])]
    public function hit(
        SessionInterface $session
    ): Response {
        /** @var Game|null $game */
        $game = $session->get("game");

        if ($game === null) {
            return $this->redirectToRoute('game');
        }

        $flashData = $game->playerTurn();
        if ($flashData) {
            $this->addFlash($flashData['type'], $flashData['message']);
        }

        $session->set("game", $game);

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/stand", name:"game_stand", methods: ['GET'])]
    public function stand(
        SessionInterface $session
    ): Response {
        /** @var Game|null $game */
        $game = $session->get("game");

        if ($game === null) {
            return $this->redirectToRoute('game');
        }

        $flashData = $game->bankTurn();
        if ($flashData) {
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
