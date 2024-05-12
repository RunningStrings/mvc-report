<?php

namespace App\Controller;

use App\Card\CardHand;
use App\Card\DeckOfCards;
use App\Game\Game;
use App\Game\Player;

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
        $deck = new DeckOfCards();
        $deck->shuffleDeck();
        $session->set("deck", $deck);

        $player = new Player('Player');
        $playerHand = new CardHand($deck);
        $player->addHand($playerHand);
        $session->set("player", $player);

        $bank = new Player('Bank');
        $bankHand = new CardHand($deck);
        $bank->addHand($bankHand);
        $session->set("bank", $bank);

        $game = new Game($deck, $player, $bank);
        $session->set("game", $game);

        $session->set("gameOver", false);

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/play", name: "game_play", methods: ['GET'])]
    public function play(SessionInterface $session): Response
    {
        $game = $session->get('game');

        $players = $game->getPlayers();

        $data = [
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

        $game->playerTurn();

        $players = $game->getPlayers();
        $player = $players['player'];
        $score = $game->calculatePoints($player);
        $player->setScore($score);

        $session->set("game", $game);

        $gameStatus = $game->gameStatus($player, $players['bank']);

        if ($gameStatus === 'Player Bust') {
            $session->set("gameOver", true);
            $this->addFlash('lose', 'Du förlorade spelomgången!');
        }

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/stand", name:"game_stand", methods: ['GET'])]
    public function stand(
        SessionInterface $session
    ): Response {
        $game = $session->get("game");

        $game->bankTurn();

        $players = $game->getPlayers();
        $player = $players['player'];
        $bank = $players['bank'];
        $score = $game->calculatePoints($bank);
        $bank->setScore($score);

        $session->set("game", $game);

        $gameStatus = $game->gameStatus($player, $bank);

        switch ($gameStatus) {
            case 'Bank Wins (Tie)':
                $session->set("gameOver", true);
                $this->addFlash('lose', 'Du förlorade spelomgången!');
                break;
            case 'Bank Wins':
                $session->set("gameOver", true);
                $this->addFlash('lose', 'Du förlorade spelomgången!');
                break;
            case 'Bank Bust':
                $session->set("gameOver", true);
                $this->addFlash('win', 'Du vann spelomgången!');
                break;
            case 'Player Win':
                $session->set("gameOver", true);
                $this->addFlash('win', 'Du vann spelomgången!');
                break;
            default:
                return $this->redirectToRoute('game_play');
        }

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