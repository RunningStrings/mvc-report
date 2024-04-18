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

    public function shuffleDeck()
    {
        shuffle($this->cards);
    }

    public function sortDeck()
    {
        $suitMap = [
                'Hearts' => 1, 'Diamonds' => 2, 'Clubs' => 3, 'Spades' => 4,
            ];


        $valueMap = [
                '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' =>5,
                '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10,
                'Jack' => 11, 'Queen' => 12, 'King'=> 13,
            ];

        
        usort($this->cards, function($a, $b) use ($suitMap, $valueMap) {
            $suitValueA = $suitMap[$a->getSuit()] ?? 0;
            $suitValueB = $suitMap[$b->getSuit()] ?? 0;

            $numValueA = $valueMap[$a->getValue()] ?? 0;
            $numValueB = $valueMap[$b->getValue()] ?? 0;

            if ($suitValueA != $suitValueB) {
                return $suitValueA - $suitValueB;
            }

            if ($numValueA != $numValueB) {
                return $numValueA -$numValueB;
            }

            return 0;
        });
    }

    public function draw()
    {
        return array_shift($this->cards);
    }
}