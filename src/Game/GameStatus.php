<?php

namespace App\Game;

use App\Card\DeckOfCards;
use App\Game\Game;

class GameStatus
{
    protected Game $game;

    public function __construct($game)
    {
        $this->game = $game;
    }

    public function handleGameStatus(Game $game, string $gameStatus, Player $player, Player $bank): ?array
    {
        $endMessage = $this->getEndMessage($gameStatus);
        if ($endMessage !== null) {
            $scoreBoard = $game->getScoreBoard();
            
            $handlers = [
                'Player Bust' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setAmount(0);
                },
                'Player Bankrupt' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getAmount() * 2);
                    $game->setGameOver(true);
                    $game->setAmount(0);
                },
                'Bank Bankrupt' => function () use (&$scoreBoard, $player, $game) {
                    $scoreBoard['player']++;
                    $player->win($game->getAmount() * 2);
                    $game->setGameOver(true);
                    $game->setAmount(0);
                },
                'Player Wins (Empty Deck)' => function () use (&$scoreBoard, $player, $game) {
                    $scoreBoard['player']++;
                    $player->win($game->getAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setGameOver(true);
                    $game->setAmount(0);
                },
                'Bank Wins (Empty Deck)' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setGameOver(true);
                    $game->setAmount(0);
                },
                'Bank Wins (Tie) (Empty Deck)' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setGameOver(true);
                    $game->setAmount(0);
                },
                'Bank Wins (Tie)' => function () use (&$scoreBoard, $bank, $game) {
                    $bank->win($game->getAmount() * 2);
                    $scoreBoard['bank']++;
                    $game->setAmount(0);
                },
                'Bank Wins' => function () use (&$scoreBoard, $bank, $game) {
                    $bank->win($game->getAmount() * 2);
                    $scoreBoard['bank']++;
                    $game->setAmount(0);
                },
                'Bank Bust' => function () use (&$scoreBoard, $player, $game) {
                    $player->win($game->getAmount() * 2);
                    $scoreBoard['player']++;
                    $game->setAmount(0);
                },
                'Player Wins' => function () use (&$scoreBoard, $player, $game) {
                    $player->win($game->getAmount() * 2);
                    $scoreBoard['player']++;
                    $game->setAmount(0);
                },
            ];

            if (array_key_exists($gameStatus, $handlers)) {
                $handlers[$gameStatus](); // Call the corresponding handler function
            }
    
            $game->setScoreBoard($scoreBoard);
            return $endMessage;
        }
        
        return null;
    }

    public function getEndMessage(string $gameStatus): ?array
    {
        $messages = [
            'Player Bust' => ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'],
            'Bank Wins' => ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'],
            'Bank Wins (Tie)' => ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'],
            'Player Bankrupt' => ['message' => 'Dina pengar är slut - du förlorade spelet!', 'type' => 'lose'],
            'Bank Bankrupt' => ['message' => 'Banken är tömd - du vann spelet!', 'type' => 'win'],
            'Player Wins (Empty Deck)' => ['message' => 'Kortleken är slut - du vann spelet!', 'type' => 'win'],
            'Bank Wins (Empty Deck)' => ['message' => 'Kortleken är slut - du förlorade spelet!', 'type' => 'lose'],
            'Bank Wins (Tie) (Empty Deck)' => ['message' => 'Kortleken är slut - tie - du förlorade spelet!', 'type' => 'lose'],
            'Bank Bust' => ['message' => 'Du vann spelomgången!', 'type' => 'win'],
            'Player Wins' => ['message' => 'Du vann spelomgången!', 'type' => 'win'],
        ];

        return $messages[$gameStatus] ?? null;
    }

    public function getGameStatus(Game $game, Player $player, Player $bank, int $playerMoney, int $bankMoney): string
    {
        $playerScore = $game->calculatePoints($player);
        $bankScore = $game->calculatePoints($bank);

        if ($this->isPlayerBustAndBankrupt($playerScore, $playerMoney)) {
            return 'Player Bankrupt';
        }

        if ($this->isPlayerBust($playerScore)) {
            return 'Player Bust';
        }

        if ($this->isAnyBankrupt($playerMoney, $bankMoney)) {
            return $playerMoney === 0 ? 'Player Bankrupt' : 'Bank Bankrupt';
        }

        if ($this->game->getDeck()->isEmpty()) {
            return $this->determineEmptyDeckOutcome($playerScore, $bankScore);
        }

        if ($this->game->isRoundOver()) {
            return $this->determineRoundOverOutcome($playerScore, $bankScore);
        }

        return 'Game On';
    }

    public function isPlayerBustAndBankrupt(int $playerScore, int $playerMoney): bool
    {
        return $playerScore > 21 && ($playerMoney === 0);
    }

    public function isPlayerBust(int $playerScore): bool
    {
        return $playerScore > 21;
    }

    public function isAnyBankrupt(int $playerMoney, int $bankMoney): bool
    {
        return $this->game->isRoundOver() && ($playerMoney === 0 || $bankMoney === 0);
    }

    public function determineEmptyDeckOutcome(int $playerScore, int $bankScore): string
    {
        if ($playerScore <=21 && ($bankScore > 21 || $playerScore > $bankScore)) {
            return 'Player Wins (Empty Deck)';
        } elseif ($bankScore <= 21 && ($playerScore > 21 || $bankScore > $playerScore)) {
            return 'Bank Wins (Empty Deck)';
        }

        return 'Bank Wins (Tie) (Empty Deck)';
    }

    public function determineRoundOverOutcome(int $playerScore, int $bankScore): string
    {
        if ($bankScore === $playerScore) {
            return 'Bank Wins (Tie)';
        } elseif ($bankScore > $playerScore && $bankScore <= 21) {
            return 'Bank Wins';
        } elseif ($bankScore > 21) {
            return 'Bank Bust';
        } elseif ($playerScore > $bankScore && $playerScore <= 21) {
            return 'Player Wins';
        }

        return 'Game On';
    }
}
