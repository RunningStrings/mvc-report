<?php

namespace App\Game;

use App\Card\DeckOfCards;


class Game
{
    protected DeckOfCards $deck;
    protected Player $player;
    protected Player $bank;
    protected bool $roundOver;
    protected bool $gameOver;
    protected bool $betPlaced;
    protected int $amount;
    protected array $scoreBoard;

    public function __construct(
        DeckOfCards $deck,
        Player $player,
        Player $bank
    )
    {
        $this->deck = $deck;
        $this->player = $player;
        $this->bank = $bank;
        $this->roundOver = false;
        $this->gameOver = false;
        $this->betPlaced = false;
        $this->amount = 0;
        $this->scoreBoard = ['player' => 0, 'bank' => 0];
    }

    public static function newGame(): Game
    {
        $deck = new DeckOfCards();
        $deck->shuffleDeck();

        $player = new Player('Spelare', $deck);
        $bank = new Player('Bank', $deck);

        return new Game($deck, $player, $bank);
    }

    public function resetGame(): void
    {
        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $player->getHand()->clearHand();
        $bank->getHand()->clearHand();
        $player->setScore(0);
        $bank->setScore(0);
        $this->setRoundOver(false);
        $this->setBetPlaced(false);
        $this->setAmount(0);
    }

    public function getDeck(): DeckOfCards
    {
        return $this->deck;
    }

    public function setPlayers(?Player $player = null, ?Player $bank = null): void
    {
        if ($player !== null) {
            $this->player = $player;
        }
        if ($bank !== null) {
            $this->bank = $bank;
        }
    }

    public function getPlayers(?string $playerType = null): array
    {
        $players = [];
        if ($playerType === 'player' || $playerType === null) {
            $players['player'] = $this->player;
        }
        if ($playerType === 'bank' || $playerType === null) {
            $players['bank'] = $this->bank;
        }
        return $players;
    }

    public function setRoundOver(bool $roundOver): void
    {
        $this->roundOver = $roundOver;
    }

    public function isRoundOver(): bool
    {
        return $this->roundOver;
    }

    public function setGameOver(bool $gameOver): void
    {
        $this->gameOver = $gameOver;
    }

    public function isGameOver(): bool
    {
        return $this->gameOver;
    }

    public function setBetPlaced(bool $betPlaced): void
    {
        $this->betPlaced = $betPlaced;
    }

    public function isBetPlaced(): bool
    {
        return $this->betPlaced;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setScoreBoard(array $scoreBoard): void
    {
        $this->scoreBoard = $scoreBoard;
    }

    public function getScoreBoard(): array
    {
        return $this->scoreBoard;
    }

    public function placeBet(int $amount): ?array
    {
        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        if ($amount > $player->getMoney() || $amount > $bank->getMoney()) {
            return ['type' => 'warning', 'message' => 'Täckning saknas för insatsen - välj ett mindre belopp!'];
        }

        if ($player->bet($amount) && $bank->bet($amount)) {
            $this->setAmount($amount);
            $this->setBetPlaced(true);
            return null;
        } else {
            return null;
        }
        // if ($player->bet($amount) && $bank->bet($amount)) {
        //     $this->setAmount($amount);
        //     $this->setBetPlaced(true);
        // } else {
        //     if ($this->isBetPlaced()) {
        //         $player->setMoney($player->getMoney() + $amount);
        //         $bank->setMoney($bank->getMoney() + $amount);
        //         $this->setBetPlaced(false);
        //     }
        // }
    }

    public function playerTurn(): ?array
    {
        if (!$this->deck->isEmpty()) {
            $this->player->getHand()->addCard($this->deck->draw());
        }

        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $score = $this->calculatePoints($player);
        $player->setScore($score);

        $gameStatus = $this->gameStatus($player, $bank, $player->getMoney(), $bank->getMoney());

        return $this->handleGameStatus($gameStatus, $player, $bank);

        // switch ($gameStatus) {
        //     case 'Player Bust':
        //         // player bust
        //         $scoreBoard = $this->getScoreBoard();
        //         $scoreBoard['bank']++;
        //         $bank->win($this->getAmount() * 2);
        //         $this->setRoundOver(true);
        //         $this->setAmount(0);
        //         return ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'];
        //     case 'Player Bankrupt':
        //         // player bankrupt
        //         // $bank->win($this->getAmount());
        //         $bank->win($this->getAmount() * 2);
        //         $this->setGameOver(true);
        //         $this->setAmount(0);
        //         return ['message' => 'Dina pengar är slut - du förlorade spelet!', 'type' => 'lose'];
        //     case 'Bank Bankrupt':
        //         // bank bankrupt
        //         $player->win($this->getAmount() * 2);
        //         $this->setGameOver(true);
        //         $this->setAmount(0);
        //         return ['message' => 'Banken är tömd - du vann spelet!', 'type' => 'win'];
        //     case 'Player Wins (Empty Deck)':
        //         // player win empty deck
        //         $this->setRoundOver(true);
        //         $this->setGameOver(true);
        //         $player->win($this->getAmount() * 2);
        //         $this->setAmount(0);
        //         return ['message' => 'Kortleken är slut - du vann spelet!', 'type' => 'win'];
        //     case 'Bank Wins (Empty Deck)':
        //         // bank win empty deck
        //         $player->win($this->getAmount());
        //         $this->setRoundOver(true);
        //         $this->setGameOver(true);
        //         $bank->win($this->getAmount() * 2);
        //         $this->setAmount(0);
        //         return ['message' => 'Kortleken är slut - du förlorade spelet!', 'type' => 'lose'];
        //     case 'Bank Wins (Tie) (Empty Deck)':
        //         // bank win tie empty deck
        //         $bank->win($this->getAmount());
        //         $this->setRoundOver(true);
        //         $this->setGameOver(true);
        //         $bank->win($this->getAmount() * 2);
        //         $this->setAmount(0);
        //         return ['message' => 'Kortleken är slut - tie - du förlorade spelet!', 'type' => 'lose'];
        //     default:
        //         return null;
        // }
    }

    public function bankTurn(): ?array
    {
        while ($this->calculatePoints($this->bank) < 17 && !$this->deck->isEmpty()) {
            $this->bank->getHand()->addCard($this->deck->draw());
        }

        $player = $this->getPlayers()['player'];
        $bank = $this->getPlayers()['bank'];

        $bankScore = $this->calculatePoints($bank);
        $bank->setScore($bankScore);

        $this->setRoundOver(true);

        $gameStatus = $this->gameStatus($player, $bank, $player->getMoney(), $bank->getMoney());

        
        
        return $this->handleGameStatus($gameStatus, $player, $bank);

        // switch ($gameStatus) {
        //     case 'Bank Wins (Tie)':
        //     case 'Bank Wins':
        //         $scoreBoard = $this->getScoreBoard();
        //         $scoreBoard['bank']++;
        //         $bank->win($this->getAmount() * 2);
        //         // $this->setRoundOver(true);
        //         $this->setAmount(0);
        //         return ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'];
        //     case 'Bank Bust':
        //     case 'Player Wins':
        //         $scoreBoard = $this->getScoreBoard();
        //         $scoreBoard['player']++;
        //         $player->win($this->getAmount() * 2);
        //         // $this->setRoundOver(true);
        //         $this->setAmount(0);
        //         return ['message' => 'Du vann spelomgången!', 'type' => 'win'];
        //     case 'Player Bankrupt':
        //         $this->setGameOver(true);
        //         $this->setAmount(0);
        //         return ['message' => 'Dina pengar är slut - du förlorade spelet!', 'type' => 'lose'];
        //     case 'Bank Bankrupt':
        //         $this->setGameOver(true);
        //         $this->setAmount(0);
        //         return ['message' => 'Banken är tömd - du vann spelet!', 'type' => 'win'];
        //     case 'Player Wins (Empty Deck)':
        //         $this->setRoundOver(true);
        //         $this->setGameOver(true);
        //         $player->win($this->getAmount() * 2);
        //         $this->setAmount(0);
        //         return ['message' => 'Kortleken är slut - du vann spelet!', 'type' => 'win'];
        //     case 'Bank Wins (Empty Deck)':
        //         $this->setRoundOver(true);
        //         $this->setGameOver(true);
        //         $bank->win($this->getAmount() * 2);
        //         $this->setAmount(0);
        //         return ['message' => 'Kortleken är slut - du förlorade spelet!', 'type' => 'lose'];
        //     case 'Bank Wins (Tie) (Empty Deck)':
        //         $this->setRoundOver(true);
        //         $this->setGameOver(true);
        //         $bank->win($this->getAmount() * 2);
        //         $this->setAmount(0);
        //         return ['message' => 'Kortleken är slut - tie - du förlorade spelet!', 'type' => 'lose'];
        //     default:
        //         return null;
        // }
    }

    private function handleGameStatus(string $gameStatus, Player $player, Player $bank): ?array
    {
        switch ($gameStatus) {
            case 'Player Bust':
                // player bust
                $scoreBoard = $this->getScoreBoard();
                $scoreBoard['bank']++;
                $bank->win($this->getAmount() * 2);
                $this->setRoundOver(true);
                $this->setAmount(0);
                return ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'];
            case 'Player Bankrupt':
                // player bankrupt
                // $bank->win($this->getAmount());
                $bank->win($this->getAmount() * 2);
                $this->setGameOver(true);
                $this->setAmount(0);
                return ['message' => 'Dina pengar är slut - du förlorade spelet!', 'type' => 'lose'];
            case 'Bank Bankrupt':
                // bank bankrupt
                $player->win($this->getAmount() * 2);
                $this->setGameOver(true);
                $this->setAmount(0);
                return ['message' => 'Banken är tömd - du vann spelet!', 'type' => 'win'];
            case 'Player Wins (Empty Deck)':
                // player win empty deck
                $this->setRoundOver(true);
                $this->setGameOver(true);
                $player->win($this->getAmount() * 2);
                $this->setAmount(0);
                return ['message' => 'Kortleken är slut - du vann spelet!', 'type' => 'win'];
            case 'Bank Wins (Empty Deck)':
                // bank win empty deck
                $player->win($this->getAmount());
                $this->setRoundOver(true);
                $this->setGameOver(true);
                $bank->win($this->getAmount() * 2);
                $this->setAmount(0);
                return ['message' => 'Kortleken är slut - du förlorade spelet!', 'type' => 'lose'];
            case 'Bank Wins (Tie) (Empty Deck)':
                // bank win tie empty deck
                $bank->win($this->getAmount());
                $this->setRoundOver(true);
                $this->setGameOver(true);
                $bank->win($this->getAmount() * 2);
                $this->setAmount(0);
                return ['message' => 'Kortleken är slut - tie - du förlorade spelet!', 'type' => 'lose'];
            case 'Bank Wins (Tie)':
            case 'Bank Wins':
                // bank win
                $scoreBoard = $this->getScoreBoard();
                $scoreBoard['bank']++;
                $bank->win($this->getAmount() * 2);
                // $this->setRoundOver(true);
                $this->setAmount(0);
                return ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'];
            case 'Bank Bust':
            case 'Player Wins':
                // player win
                $scoreBoard = $this->getScoreBoard();
                $scoreBoard['player']++;
                $player->win($this->getAmount() * 2);
                // $this->setRoundOver(true);
                $this->setAmount(0);
                return ['message' => 'Du vann spelomgången!', 'type' => 'win'];
            default:
                return null;
        }
    }

    /**
     * Calculate the total points in a hand.
     * Aces are worth 1 or 14 points depending on which brings the
     * total to <=21. Jacks are worth 11 points, Queens are worth
     * 12 points, Kings are worth 13 points. The point value of 
     * remaining cards correlate to their respective numeric value.
     * 
     * @param Player $player The player for whom to calculate hand points.
     * 
     * @return int
     */
    public function calculatePoints(Player $player): int
    {
        $total = 0;
        $aceCount = 0;
        $cards = $player->getHand()->getHand();
        $cardValues = [
            'Ace' => [1, 14],
            'Jack' => 11,
            'Queen' => 12,
            'King' => 13,
        ];

        foreach ($cards as $card) {
            $value = $card->getValue();
            if ($value === 'Ace') {
                $aceCount++;
            } else {
                if (isset($cardValues[$value])) {
                    $total += $cardValues[$value];
                } else {
                    $total += (int)$value;
                }
            }
        }

        for ($i = 0; $i < $aceCount; $i++) {
            if ($total + 14 > 21) {
                $total += 1;
            } else {
                $total += 14;
            }
        }

        return $total;
    }

    public function gameStatus(Player $player, Player $bank, int $playerMoney, int $bankMoney): string
    {
        $playerScore = $this->calculatePoints($player);
        $bankScore = $this->calculatePoints($bank);

        if ($playerScore > 21 && ($playerMoney === 0)) {
            return 'Player Bankrupt';
        }

        if ($playerScore > 21) {
            return 'Player Bust';
        }

        if ($this->isRoundOver() && ($playerMoney === 0 || $bankMoney === 0)) {
            return $playerMoney === 0 ? 'Player Bankrupt' : 'Bank Bankrupt';
        }

        if ($this->deck->isEmpty()) {
            if ($playerScore <= 21 && ($bankScore > 21 || $playerScore > $bankScore)) {
                return 'Player Wins (Empty Deck)';
            } elseif ($bankScore <= 21 && ($playerScore > 21 || $bankScore > $playerScore)) {
                return 'Bank Wins (Empty Deck)';
            } else {
                return 'Bank Wins (Tie) (Empty Deck)';
            }
        }

        if ($this->isRoundOver()) {
            if ($bankScore === $playerScore) {
                return 'Bank Wins (Tie)';
            } elseif ($bankScore > $playerScore && $bankScore <= 21) {
                return 'Bank Wins';
            } elseif ($bankScore > 21) {
                return 'Bank Bust';
            } elseif ($playerScore > $bankScore && $playerScore <= 21) {
                return 'Player Wins';
            }
        }

        return 'Game On';
    }
}
