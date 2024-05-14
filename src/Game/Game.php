<?php

namespace App\Game;

use App\Card\DeckOfCards;


class Game
{
    protected DeckOfCards $deck;
    protected Player $player;
    protected Player $bank;
    protected bool $roundOver;
    protected bool $gameOver;
    protected bool $betPlaced;
    protected int $amount;
    protected array $scoreBoard;

    public function __construct(
        DeckOfCards $deck,
        Player $player,
        Player $bank
    )
    {
        $this->deck = $deck;
        $this->player = $player;
        $this->bank = $bank;
        $this->roundOver = false;
        $this->gameOver = false;
        $this->betPlaced = false;
        $this->amount = 0;
        $this->scoreBoard = ['player' => 0, 'bank' => 0];
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

    public function setScoreBoard(array $scoreBoard): void
    {
        $this->scoreBoard = $scoreBoard;
    }

    public function getScoreBoard(): array
    {
        return $this->scoreBoard;
    }

    public function placeBet(int $amount): void
    {
        $player = $this->getPlayers()['player'];
        if ($player->bet($amount)) {
            $this->setAmount($amount);
            $this->setBetPlaced(true);
        }
    }

    public function playerTurn(): ?array
    {
        if (!$this->deck->isEmpty()) {
            $this->player->getHand()->addCard($this->deck->draw());
        }

        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $score = $this->calculatePoints($player);
        $player->setScore($score);

        $gameStatus = $this->gameStatus($player, $bank, $player->getMoney(), $bank->getMoney());

        switch ($gameStatus) {
            case 'Player Bust':
                // player bust
                $scoreBoard = $this->getScoreBoard();
                $scoreBoard['bank']++;
                $bank->win($this->getAmount());
                $this->setRoundOver(true);
                return ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'];
            case 'Player Bankrupt':
                // player bankrupt
                $this->setGameOver(true);
                return ['message' => 'Dina pengar är slut - du förlorade spelet!', 'type' => 'lose'];
            case 'Bank Bankrupt':
                // bank bankrupt
                $this->setGameOver(true);
                return ['message' => 'Banken är tömd - du vann spelet!', 'type' => 'win'];
            case 'Player Wins (Empty Deck)':
                // player win empty deck
                $this->setRoundOver(true);
                $this->setGameOver(true);
                return ['message' => 'Kortleken är slut - du vann spelet!', 'type' => 'win'];
            case 'Bank Wins (Empty Deck)':
                // bank win empty deck
                $this->setRoundOver(true);
                $this->setGameOver(true);
                return ['message' => 'Kortleken är slut - du förlorade spelet!', 'type' => 'lose'];
            case 'Bank Wins (Tie) (Empty Deck)':
                // bank win tie empty deck
                $this->setRoundOver(true);
                $this->setGameOver(true);
                return ['message' => 'Kortleken är slut - tie - du förlorade spelet!', 'type' => 'lose'];
            default:
                return null;
        }
    }

    public function bankTurn(): ?array
    {
        while ($this->calculatePoints($this->bank) < 17 && !$this->deck->isEmpty()) {
            $this->bank->getHand()->addCard($this->deck->draw());
        }

        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $bankScore = $this->calculatePoints($bank);
        $bank->setScore($bankScore);

        $gameStatus = $this->gameStatus($player, $bank, $player->getMoney(), $bank->getMoney());

        switch ($gameStatus) {
            case 'Bank Wins (Tie)':
            case 'Bank Wins':
                $scoreBoard = $this->getScoreBoard();
                $scoreBoard['bank']++;
                $bank->win($this->getAmount());
                $this->setRoundOver(true);
                return ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'];
            case 'Bank Bust':
            case 'Player Wins':
                $scoreBoard = $this->getScoreBoard();
                $scoreBoard['player']++;
                $player->win($this->getAmount() * 2);
                $bank->setMoney($bank->getMoney() - $this->getAmount());
                $this->setRoundOver(true);
                return ['message' => 'Du vann spelomgången!', 'type' => 'win'];
            case 'Player Bankrupt':
                $this->setGameOver(true);
                return ['message' => 'Dina pengar är slut - du förlorade spelet!', 'type' => 'lose'];
            case 'Bank Bankrupt':
                $this->setGameOver(true);
                return ['message' => 'Banken är tömd - du vann spelet!', 'type' => 'win'];
            case 'Player Wins (Empty Deck)':
                $this->setRoundOver(true);
                $this->setGameOver(true);
                return ['message' => 'Kortleken är slut - du vann spelet!', 'type' => 'win'];
            case 'Bank Wins (Empty Deck)':
                $this->setRoundOver(true);
                $this->setGameOver(true);
                return ['message' => 'Kortleken är slut - du förlorade spelet!', 'type' => 'lose'];
            case 'Bank Wins (Tie) (Empty Deck)':
                $this->setRoundOver(true);
                $this->setGameOver(true);
                return ['message' => 'Kortleken är slut - tie - du förlorade spelet!', 'type' => 'lose'];
            default:
                return null;
        }
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
            } else {
                if (isset($cardValues[$value])) {
                    $total += $cardValues[$value];
                } else {
                    $total += (int)$value;
                }
            }
        }

        for ($i = 0; $i < $aceCount; $i++) {
            if ($total + 14 > 21) {
                $total += 1;
            } else {
                $total += 14;
            }
        }

        return $total;
    }

    public function gameStatus(Player $player, Player $bank, int $playerMoney, int $bankMoney): string
    {
        $playerScore = $this->calculatePoints($player);
        $bankScore = $this->calculatePoints($bank);

        if ($playerMoney === 0 || $bankMoney === 0) {
            return $playerMoney === 0 ? 'Player Bankrupt' : 'Bank Bankrupt';
        }

        if ($this->deck->isEmpty()) {
            if ($playerScore <= 21 && ($bankScore > 21 || $playerScore > $bankScore)) {
                return 'Player Wins (Empty Deck)';
            } elseif ($bankScore <= 21 && ($playerScore > 21 || $bankScore > $playerScore)) {
                return 'Bank Wins (Empty Deck)';
            } else {
                return 'Bank Wins (Tie) (Empty Deck)';
            }
        }

        if ($playerScore > 21) {
            return 'Player Bust';
        } elseif ($bankScore === $playerScore) {
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
