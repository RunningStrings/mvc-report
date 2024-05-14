<?php

namespace App\Game;

use App\Card\DeckOfCards;

class Game
{
    protected DeckOfCards $deck;
    protected Player $player;
    protected Player $bank;

    public function __construct(
        DeckOfCards $deck,
        Player $player,
        Player $bank
    )
    {
        $this->deck = $deck;
        $this->player = $player;
        $this->bank = $bank;
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

    public function playerTurn(): void
    {
        if (!$this->deck->isEmpty()) {
            $this->player->getHand()->addCard($this->deck->draw());
        }
    }

    public function bankTurn(): void
    {
        while ($this->calculatePoints($this->bank) < 17 && !$this->deck->isEmpty()) {
            $this->bank->getHand()->addCard($this->deck->draw());
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
