<?php

namespace App\Card;

class CardHand
{
    protected $cards = [];
    protected $deck;

    public function __construct(DeckOfCards $deck)
    {
        $this->deck = $deck;
    }

    public function addCard(Card $card)
    {
        $this->cards[] = $card;
    }

    public function getHand()
    {
        return $this->cards;
    }

    public function clearHand()
    {
        $this->cards = [];
    }
}
