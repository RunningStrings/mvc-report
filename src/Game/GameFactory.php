<?php

namespace App\Game;

use App\Card\DeckOfCards;
use App\Game\Game;
use App\Game\GameStatus;
use App\Game\Player;

class GameFactory
{
    public static function createNewGame(): Game
    {
        $deck = new DeckOfCards();
        $deck->shuffleDeck();

        $player = new Player('Spelare', $deck);
        $bank = new Player('Bank', $deck);

        $game = new Game($deck, $player, $bank);

        $gameStatus = new GameStatus($game);

        $game->setGameStatus($gameStatus);

        return $game;
    }
}
