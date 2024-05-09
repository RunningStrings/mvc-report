<?php

namespace App\Game;

use App\Card\CardHand;

class Player
{
    protected string $name;
    protected CardHand $hand;
    protected int $score;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->score = 0;
    }

    public function addHand(CardHand $hand): void
    {
        $this->hand = $hand;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHand(): CardHand
    {
        return $this->hand;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function addToScore(int $points): void
    {
        $this->score += $points;
    }
}
