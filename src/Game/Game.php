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
        Player $bank)
    {
        $this->deck = $deck;
        $this->player = $player;
        $this->bank = $bank;
    }
}
