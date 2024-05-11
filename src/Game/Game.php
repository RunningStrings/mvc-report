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

    public function getPlayers(): array
    {
        return [
            'player' => $this->player,
            'bank' => $this->bank,
        ];
    }

    public function playerTurn(): void
    {
        $this->player->getHand()->addCard($this->deck->draw());
        // $this->showPlayerHand();
        // Prompt hit or stand
    }

    public function bankTurn(): void
    {
        while ($this->bank->getHand()->$this->calculatePoints() < 17) {
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
        $cards = $player->getHand();
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
            } 
            if (isset($cardValues[$value])) {
                $total += is_array($cardValues[$value]) ? $cardValues[$value][$aceCount > 0 ? 1 : 0] : $cardValues[$value];
            }
            $total += (int)$value;
        }

        for ($i = 0; $i < $aceCount; $i++) {
            $total += ($total + 14 <= 21) ? 14 : 1;
        }

        return $total;
    }

}
