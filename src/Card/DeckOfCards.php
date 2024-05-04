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

    private function createDeck(): void
    {
        $suits = ['Hearts', 'Diamonds', 'Clubs', 'Spades'];
        $values = ['Ace','2', '3', '4', '5', '6', '7', '8', '9', '10', 'Jack', 'Queen', 'King'];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $this->cards[] = new CardGraphic($suit, $value);
            }
        }
    }

    public function getDeck(): array
    {
        return $this->cards;
    }

    public function getDeckStringArray()
    {
        $deck = [];
        foreach ($this->cards as $card) {
            $deck[] = $card->getValue() . ' of ' . $card->getSuit();
        }
        return $deck;
    }

    public function shuffleDeck(): void
    {
        shuffle($this->cards);
    }

    public function sortDeck(): void
    {
        $suitMap = [
                'Hearts' => 1, 'Diamonds' => 2, 'Clubs' => 3, 'Spades' => 4,
            ];


        $valueMap = [
                'Ace' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
                '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10,
                'Jack' => 11, 'Queen' => 12, 'King' => 13,
            ];


        usort($this->cards, function ($cardA, $cardB) use ($suitMap, $valueMap) {
            $suitValueA = $suitMap[$cardA->getSuit()] ?? 0;
            $suitValueB = $suitMap[$cardB->getSuit()] ?? 0;

            $numValueA = $valueMap[$cardA->getValue()] ?? 0;
            $numValueB = $valueMap[$cardB->getValue()] ?? 0;

            if ($suitValueA != $suitValueB) {
                return $suitValueA - $suitValueB;
            }

            if ($numValueA != $numValueB) {
                return $numValueA - $numValueB;
            }

            return 0;
        });
    }

    public function draw(): ?Card
    {
        return array_shift($this->cards);
    }

    public function isEmpty(): bool
    {
        return empty($this->cards);
    }
}
