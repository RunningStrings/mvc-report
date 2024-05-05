<?php

namespace App\Card;

class CardHand
{
    /**
     * @var Card[]
     */
    protected array $cards = [];
    protected DeckOfCards $deck;

    public function __construct(DeckOfCards $deck)
    {
        $this->deck = $deck;
    }

    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    /**
     * @return Card[]
     */
    public function getHand(): array
    {
        return $this->cards;
    }

    public function clearHand(): void
    {
        $this->cards = [];
    }

    /**
     * @return string[]
     */
    public function toHandArray(): array
    {
        $handArray = [];
        foreach ($this->cards as $card) {
            $handArray[] = $card->getValue() . ' of ' . $card->getSuit();
        }
        return $handArray;
    }
}
