<?php

namespace App\Game;

use App\Game\Game;

class GameStatus
{
    protected Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    /**
     * Returns the current game status.
     */
    public function getGameStatus(Game $game, Player $player, Player $bank, int $playerMoney, int $bankMoney): string
    {
        $playerScore = $game->calculatePoints($player);
        $bankScore = $game->calculatePoints($bank);

        // Check if player is bust and bankrupt, if true - end round
        if ($this->isPlayerBustAndBankrupt($playerScore, $playerMoney)) {
            $game->setRoundOver(true);
            return 'Player Bankrupt';
        }

        if ($this->isPlayerBust($playerScore)) {
            return 'Player Bust';
        }

        // Check if the deck is empty
        if ($this->game->getDeck()->isEmpty()) {
            $emptyDeckOutcome = $this->determineEmptyDeckOutcome($playerScore, $bankScore);
            $this->handleGameStatus($game, $emptyDeckOutcome, $player, $bank);

            // After handling empty deck outcome, check for bankruptcy
            $playerMoney = $player->getMoney();
            $bankMoney = $bank->getMoney();
            if ($this->isAnyBankrupt($playerMoney, $bankMoney)) {
                return $playerMoney === 0 ? 'Player Bankrupt' : 'Bank Bankrupt';
            }

            return $emptyDeckOutcome;
        }

        // Determine outcome of the round if round is over
        if ($this->game->isRoundOver()) {
            $roundOutcome = $this->determineRoundOverOutcome($playerScore, $bankScore);

            // Handle the round outcome and update money and scoreboard
            $this->handleGameStatus($game, $roundOutcome, $player, $bank);

            // After handling the round outcome, check for bankruptcy
            $playerMoney = $player->getMoney();
            $bankMoney = $bank->getMoney();
            if ($this->isAnyBankrupt($playerMoney, $bankMoney)) {
                return $playerMoney === 0 ? 'Player Bankrupt' : 'Bank Bankrupt';
            }

            // If neither is bankrupt, return the round outcome
            return $roundOutcome;
        }

        return 'Game On';
    }

    /**
     * Handles the game status and updates the game state accordingly.
     *
     * @param Game $game            The game object.
     * @param string $gameStatus    The current game status.
     * @param Player $player        The player object.
     * @param Player $bank          The bank object.
     *
     * @return array<string>|null   Returns an array containing game status
     *                              information, or null if no specific
     *                              action is needed.
     */
    public function handleGameStatus(Game $game, string $gameStatus, Player $player, Player $bank): ?array
    {
        $endMessage = $this->getEndMessage($gameStatus);
        if ($endMessage !== null) {
            $scoreBoard = $game->getScoreBoard();

            $handlers = [
                'Player Bust' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getBetAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setBetAmount(0);
                },
                'Player Bankrupt' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getBetAmount() * 2);
                    $game->setGameOver(true);
                    $game->setBetAmount(0);
                },
                'Bank Bankrupt' => function () use (&$scoreBoard, $player, $game) {
                    $scoreBoard['player']++;
                    $player->win($game->getBetAmount() * 2);
                    $game->setGameOver(true);
                    $game->setBetAmount(0);
                },
                'Player Wins (Empty Deck)' => function () use (&$scoreBoard, $player, $game) {
                    $scoreBoard['player']++;
                    $player->win($game->getBetAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setGameOver(true);
                    $game->setBetAmount(0);
                },
                'Bank Wins (Empty Deck)' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getBetAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setGameOver(true);
                    $game->setBetAmount(0);
                },
                'Bank Wins (Tie) (Empty Deck)' => function () use (&$scoreBoard, $bank, $game) {
                    $scoreBoard['bank']++;
                    $bank->win($game->getBetAmount() * 2);
                    $game->setRoundOver(true);
                    $game->setGameOver(true);
                    $game->setBetAmount(0);
                },
                'Bank Wins (Tie)' => function () use (&$scoreBoard, $bank, $game) {
                    $bank->win($game->getBetAmount() * 2);
                    $scoreBoard['bank']++;
                    $game->setBetAmount(0);
                },
                'Bank Wins' => function () use (&$scoreBoard, $bank, $game) {
                    $bank->win($game->getBetAmount() * 2);
                    $scoreBoard['bank']++;
                    $game->setBetAmount(0);
                },
                'Bank Bust' => function () use (&$scoreBoard, $player, $game) {
                    $player->win($game->getBetAmount() * 2);
                    $scoreBoard['player']++;
                    $game->setBetAmount(0);
                },
                'Player Wins' => function () use (&$scoreBoard, $player, $game) {
                    $player->win($game->getBetAmount() * 2);
                    $scoreBoard['player']++;
                    $game->setBetAmount(0);
                },
            ];

            if (array_key_exists($gameStatus, $handlers)) {
                // Call the corresponding handler function
                $handlers[$gameStatus]();
            }

            $game->setScoreBoard($scoreBoard);
            return $endMessage;
        }

        return null;
    }

    /**
     * Returns the end message based on the game status.
     *
     * @param string $gameStatus            The current game status.
     * @return array<string, string>|null   Returns an associative array
     *                                      containing the end message
     *                                      and type, or null if the game
     *                                      status is not recognized.
     */
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
            'Bank Wins (Tie) (Empty Deck)' => ['message' => 'Kortleken är slut- du förlorade spelet!', 'type' => 'lose'],
            'Bank Bust' => ['message' => 'Du vann spelomgången!', 'type' => 'win'],
            'Player Wins' => ['message' => 'Du vann spelomgången!', 'type' => 'win'],
        ];

        return $messages[$gameStatus] ?? null;
    }

    /**
     * Checks if the player is bust and bankrupt.
     */
    public function isPlayerBustAndBankrupt(int $playerScore, int $playerMoney): bool
    {
        return $playerScore > 21 && ($playerMoney === 0);
    }

    /**
     * Checks if the player is bust.
     */
    public function isPlayerBust(int $playerScore): bool
    {
        return $playerScore > 21;
    }

    /**
     * Checks if any player is bankrupt.
     */
    public function isAnyBankrupt(int $playerMoney, int $bankMoney): bool
    {
        return $this->game->isRoundOver() && ($playerMoney === 0 || $bankMoney === 0);
    }

    /**
     * Determines the outcome when the deck is empty.
     */
    public function determineEmptyDeckOutcome(int $playerScore, int $bankScore): string
    {
        if ($playerScore <= 21 && ($bankScore > 21 || $playerScore > $bankScore)) {
            return 'Player Wins (Empty Deck)';
        } elseif ($bankScore <= 21 && ($playerScore > 21 || $bankScore > $playerScore)) {
            return 'Bank Wins (Empty Deck)';
        }

        return 'Bank Wins (Tie) (Empty Deck)';
    }

    public function determineRoundOverOutcome(int $playerScore, int $bankScore): string
    {
        if ($bankScore > 21) {
            return 'Bank Bust';
        } elseif ($bankScore === $playerScore) {
            return 'Bank Wins (Tie)';
        } elseif ($bankScore > $playerScore) {
            return 'Bank Wins';
        } elseif ($playerScore > $bankScore && $playerScore <= 21) {
            return 'Player Wins';
        }

        return 'Game On';
    }
}
