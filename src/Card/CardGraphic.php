<?php

namespace App\Card;

class CardGraphic extends Card
{
    public function __construct(string $suit, string $value)
    {
        parent::__construct($suit, $value);
    }

    public function __toString(): string
    {
        $value = strtolower($this->getValue());
        $suit = strtolower($this->getSuit());

        $cardClass = "card-" . $suit . $value;

        return sprintf(
            '<i class="card-sprite %s"></i>',
            $cardClass
        );
    }

}
