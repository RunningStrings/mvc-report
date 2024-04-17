<?php

namespace App\Card;

class CardGraphic extends Card
{
    public function __construct($suit, $value)
    {
        parent::__construct($suit, $value);
    }

    public function __toString()
    {
        $value = strtolower($this->getValue());
        $suit = strtolower($this->getSuit());

        $cardClass = "card-" . $suit . $value;
        $backgroundImage = "url('/img/cards_deck.png')";

        return sprintf('<i class="card-sprite %s" style="background-image: %s"></i>',
        $cardClass,
        $backgroundImage);
    }

}