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
        if (!$game || !$game->getDeck() || count($game->getDeck()->getDeck()) === 0) {
            $deck = new DeckOfCards();
            $deck->shuffleDeck();

            $player = new Player('Player', $deck);

            $bank = new Player('Bank', $deck);

            $game = new Game($deck, $player, $bank);
            $session->set("game", $game);
        } else {
            $deck = $game->getDeck();
            $player = $game->getPlayers()['player'];
            $bank = $game->getPlayers()['bank'];

            $player->getHand()->clearHand();
            $bank->getHand()->clearHand();
            $player->setScore(0);
            $bank->setScore(0);
        }

        $session->set("roundOver", false);
        $session->set("betPlaced", false);

        $amount = 0;
        $session->set("amount", $amount);

        $scoreBoard = $session->get("scoreBoard");
        if (!$scoreBoard) {
            $scoreBoard = [
                'player' => 0,
                'bank' => 0,
            ];

            $session->set("scoreBoard", $scoreBoard);
        }

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/bet", name: "game_bet", methods: ['POST'])]
    public function bet(
        Request $request,
        SessionInterface $session
        ): Response {
            $game = $session->get("game");
            $players = $game->getPlayers();
            $player = $players['player'];

            $amount = $request->request->getInt('amount');

            if (!$player->bet($amount)) {
                $this->addFlash('warning', 'Täckning saknas för beloppet.');
            } else {
                $session->set("amount", $amount);
                $session->set("betPlaced", true);
                $session->set("game", $game);
            }

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
        $players = $game->getPlayers();
        $player = $players['player'];
        $bank = $players['bank'];

        $amount = $session->get("amount");

        $gameStatus = $game->gameStatus($player, $bank);

        $game->playerTurn();

        $score = $game->calculatePoints($player);
        $player->setScore($score);

        $session->set("game", $game);

        $gameStatus = $game->gameStatus($player, $bank);

        if ($gameStatus === 'Player Bust') {
            $scoreBoard = $session->get("scoreBoard");
            $scoreBoard['bank']++;
            $session->set("scoreBoard", $scoreBoard);
            $bank->win($amount);
            $session->set("roundOver", true);
            $this->addFlash('lose', 'Du förlorade spelomgången!');
        }

        return $this->redirectToRoute('game_play');
    }

    #[Route("/game/stand", name:"game_stand", methods: ['GET'])]
    public function stand(
        SessionInterface $session
    ): Response {
        $game = $session->get("game");
        $players = $game->getPlayers();
        $player = $players['player'];
        $bank = $players['bank'];

        $amount = $session->get("amount");

        $gameStatus = $game->gameStatus($player, $bank);

        $game->bankTurn();

        $bankScore = $game->calculatePoints($bank);
        $bank->setScore($bankScore);

        $session->set("game", $game);

        $gameStatus = $game->gameStatus($player, $bank);

        switch ($gameStatus) {
            case 'Bank Wins (Tie)':
            case 'Bank Wins':
                $scoreBoard = $session->get("scoreBoard");
                $scoreBoard['bank']++;
                $session->set("scoreBoard", $scoreBoard);
                $bank->win($amount);
                $session->set("roundOver", true);
                $this->addFlash('lose', 'Du förlorade spelomgången!');
                break;
            case 'Bank Bust':
            case 'Player Wins':
                $scoreBoard = $session->get("scoreBoard");
                $scoreBoard['player']++;
                $session->set("scoreBoard", $scoreBoard);
                $player->win($amount * 2);
                $bank->setMoney($bank->getMoney() - $amount);
                $session->set("roundOver", true);
                $this->addFlash('win', 'Du vann spelomgången!');
                break;
            default:
                return $this->redirectToRoute('game_play');
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