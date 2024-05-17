<?php

namespace App\Game;

use App\Card\DeckOfCards;

class Game
{
    protected DeckOfCards $deck;
    protected Player $player;
    protected Player $bank;
    protected GameStatus $gameStatus;
    protected bool $roundOver;
    protected bool $gameOver;
    protected bool $betPlaced;
    protected int $amount;
    /** @var int[] */
    protected array $scoreBoard;

    public function __construct(
        DeckOfCards $deck,
        Player $player,
        Player $bank
    ) {
        $this->deck = $deck;
        $this->player = $player;
        $this->bank = $bank;
        $this->roundOver = false;
        $this->gameOver = false;
        $this->betPlaced = false;
        $this->amount = 0;
        $this->scoreBoard = ['player' => 0, 'bank' => 0];
    }

    public function setGameStatus(GameStatus $gameStatus): void
    {
        $this->gameStatus = $gameStatus;
    }

    public function resetGame(): void
    {
        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $player->getHand()->clearHand();
        $bank->getHand()->clearHand();
        $player->setScore(0);
        $bank->setScore(0);
        $this->setRoundOver(false);
        $this->setBetPlaced(false);
        $this->setAmount(0);
    }

    public function getDeck(): DeckOfCards
    {
        return $this->deck;
    }

    public function setPlayers(?Player $player = null, ?Player $bank = null): void
    {
        if ($player !== null) {
            $this->player = $player;
        }
        if ($bank !== null) {
            $this->bank = $bank;
        }
    }

    /**
     * Retrieves the players.
     *
     * @param string|null $playerType   The type of player ('player',
     *                                  'bank'), or null to get both.
     * @return array<string, Player>    Associative array containing
     *                                  players, indexed by player type.
     */
    public function getPlayers(?string $playerType = null): array
    {
        $players = [];
        if ($playerType === 'player' || $playerType === null) {
            $players['player'] = $this->player;
        }
        if ($playerType === 'bank' || $playerType === null) {
            $players['bank'] = $this->bank;
        }
        return $players;
    }

    public function setRoundOver(bool $roundOver): void
    {
        $this->roundOver = $roundOver;
    }

    public function isRoundOver(): bool
    {
        return $this->roundOver;
    }

    public function setGameOver(bool $gameOver): void
    {
        $this->gameOver = $gameOver;
    }

    public function isGameOver(): bool
    {
        return $this->gameOver;
    }

    public function setBetPlaced(bool $betPlaced): void
    {
        $this->betPlaced = $betPlaced;
    }

    public function isBetPlaced(): bool
    {
        return $this->betPlaced;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Sets the score board.
     *
     * @param array{player: int, bank: int} $scoreBoard An associative array containing scores for player and bank.
     * @return void
     */
    public function setScoreBoard(array $scoreBoard): void
    {
        $this->scoreBoard = $scoreBoard;
    }

    /**
     * Gets the score board.
     *
     * @return array<int> An associative array containing scores for player and bank.
     */
    public function getScoreBoard(): array
    {
        return $this->scoreBoard;
    }

    /**
     * Places a bet.
     *
     * @param int $amount   The amount to bet.
     * @return array<string>|null   An array with flash message data if the bet
     *                      cannot be placed, or null if bet is
     *                      successfully placed.
     */
    public function placeBet(int $amount): ?array
    {
        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        if ($amount > $player->getMoney() || $amount > $bank->getMoney()) {
            return ['type' => 'warning', 'message' => 'Täckning saknas för insatsen - välj ett mindre belopp!'];
        }

        if ($player->bet($amount) && $bank->bet($amount)) {
            $this->setAmount($amount);
            $this->setBetPlaced(true);
            return null;
        }

        return null;
    }

    /**
     * Executes the player's turn in the game.
     *
     * @return array<string>|null Returns an array containing game status information, or null if the round continues.
     */
    public function playerTurn(): ?array
    {
        $drawnCard = $this->deck->draw();

        if (!$this->deck->isEmpty() && $drawnCard !== null) {
            $this->player->getHand()->addCard($drawnCard);
        }

        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $score = $this->calculatePoints($player);
        $player->setScore($score);

        $gameStatus = $this->gameStatus->getGameStatus($this, $player, $bank, $player->getMoney(), $bank->getMoney());

        return $this->gameStatus->handleGameStatus($this, $gameStatus, $player, $bank);
    }

    /**
     * Executes the bank's turn in the game.
     *
     * @return array<string> Returns an array containing game status information.
     */
    public function bankTurn(): ?array
    {
        $drawnCard = $this->deck->draw();

        while ($this->calculatePoints($this->bank) < 17 && !$this->deck->isEmpty() && $drawnCard !== null) {
            $this->bank->getHand()->addCard($drawnCard);
        }

        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $bankScore = $this->calculatePoints($bank);
        $bank->setScore($bankScore);

        $this->setRoundOver(true);

        $gameStatus = $this->gameStatus->getGameStatus($this, $player, $bank, $player->getMoney(), $bank->getMoney());

        return $this->gameStatus->handleGameStatus($this, $gameStatus, $player, $bank);
    }

    /**
     * Calculate the total points in a hand.
     * Aces are worth 1 or 14 points depending on which brings the
     * total to <=21. Jacks are worth 11 points, Queens are worth
     * 12 points, Kings are worth 13 points. The point value of
     * remaining cards correlate to their respective numeric value.
     *
     * @param Player $player The player for whom to calculate hand points.
     *
     * @return int
     */
    public function calculatePoints(Player $player): int
    {
        $total = 0;
        $aceCount = 0;
        $cards = $player->getHand()->getHand();
        $cardValues = [
            'Ace' => [1, 14],
            'Jack' => 11,
            'Queen' => 12,
            'King' => 13,
        ];

        foreach ($cards as $card) {
            $value = $card->getValue();
            if ($value === 'Ace') {
                $aceCount++;
                continue;
            }

            if (isset($cardValues[$value])) {
                $total += $cardValues[$value];
                continue;
            }
            $total += (int)$value;

        }

        for ($i = 0; $i < $aceCount; $i++) {
            if ($total + 14 > 21) {
                return $total + 1;
            }
            $total += 14;
        }

        return $total;
    }
}
