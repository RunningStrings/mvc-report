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

    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    public function getHand(): array
    {
        return $this->cards;
    }

    public function clearHand(): void
    {
        $this->cards = [];
    }

    public function toHandArray(): array
    {
        $handArray = [];
        foreach ($this->cards as $card) {
            $handArray[] = $card->getValue() . ' of ' . $card->getSuit();
        }
        return $handArray;
    }
}
