<?php

namespace App\Card;

use App\Card\CardGraphic;

class DeckOfCards
{
    protected $cards = [];

    public function __construct()
    {
        $this->createDeck();
    }

    private function createDeck()
    {
        $suits = ['Hearts', 'Diamonds', 'Clubs', 'Spades'];
        $values = ['1','2', '3', '4', '5', '6', '7', '8', '9', '10', 'Jack', 'Queen', 'King'];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $this->cards[] = new CardGraphic($suit, $value);
            }
        }
    }

    public function getDeck()
    {
        return $this->cards;
    }

    public function getDeckArray()
    {
        $deck = [];
        foreach ($this->cards as $card) {
            $deck[] = $card->toArray();
        }
        return $deck;
    }
}