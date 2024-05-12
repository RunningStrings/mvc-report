<?php

namespace App\Game;

use App\Card\CardHand;

class Player
{
    protected string $name;
    protected CardHand $hand;
    protected int $score;
    protected int $money;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->score = 0;
        $this->money = 100;
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

    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function addToScore(int $points): void
    {
        $this->score += $points;
    }

    public function getMoney(): int
    {
        return $this->money;
    }

    public function setMoney(int $money): void
    {
        $this->money = $money;
    }

    public function bet(int $amount): bool
    {
        if ($amount > $this->money) {
            return false;
        }

        $this->money -= $amount;
        return true;
    }

    public function win(int $amount): void
    {
        $this->money += $amount;
    }
}
