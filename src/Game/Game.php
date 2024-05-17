<?php

namespace App\Game;

use App\Card\DeckOfCards;

class Game
{
    protected DeckOfCards $deck;
    protected Player $player;
    protected Player $bank;
    protected GameStatus $gameStatus;
    protected bool $roundOver;
    protected bool $gameOver;
    protected bool $betPlaced;
    protected int $amount;
    protected array $scoreBoard;

    public function __construct(
        DeckOfCards $deck,
        Player $player,
        Player $bank
    ) {
        $this->deck = $deck;
        $this->player = $player;
        $this->bank = $bank;
        $this->roundOver = false;
        $this->gameOver = false;
        $this->betPlaced = false;
        $this->amount = 0;
        $this->scoreBoard = ['player' => 0, 'bank' => 0];
    }

    public function setGameStatus(GameStatus $gameStatus): void
    {
        $this->gameStatus = $gameStatus;
    }

    // public static function newGame(): Game
    // {
    //     $deck = new DeckOfCards();
    //     $deck->shuffleDeck();

    //     $player = new Player('Spelare', $deck);
    //     $bank = new Player('Bank', $deck);

    //     return new Game($deck, $player, $bank);
    // }

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
        }

        return null;
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

        $gameStatus = $this->gameStatus->getGameStatus($this, $player, $bank, $player->getMoney(), $bank->getMoney());

        return $this->gameStatus->handleGameStatus($this, $gameStatus, $player, $bank);
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

        $gameStatus = $this->gameStatus->getGameStatus($this, $player, $bank, $player->getMoney(), $bank->getMoney());

        return $this->gameStatus->handleGameStatus($this, $gameStatus, $player, $bank);
    }

    // private function handleGameStatus(string $gameStatus, Player $player, Player $bank): ?array
    // {
    //     $endMessage = $this->getEndMessage($gameStatus);
    //     if ($endMessage !== null) {
    //         $scoreBoard = $this->getScoreBoard();
            
    //         $handlers = [
    //             'Player Bust' => function () use (&$scoreBoard, $bank) {
    //                 $scoreBoard['bank']++;
    //                 $bank->win($this->getAmount() * 2);
    //                 $this->setRoundOver(true);
    //                 $this->setAmount(0);
    //             },
    //             'Player Bankrupt' => function () use (&$scoreBoard, $bank) {
    //                 $scoreBoard['bank']++;
    //                 $bank->win($this->getAmount() * 2);
    //                 $this->setGameOver(true);
    //                 $this->setAmount(0);
    //             },
    //             'Bank Bankrupt' => function () use (&$scoreBoard, $player) {
    //                 $scoreBoard['player']++;
    //                 $player->win($this->getAmount() * 2);
    //                 $this->setGameOver(true);
    //                 $this->setAmount(0);
    //             },
    //             'Player Wins (Empty Deck)' => function () use (&$scoreBoard, $player) {
    //                 $scoreBoard['player']++;
    //                 $player->win($this->getAmount() * 2);
    //                 $this->setRoundOver(true);
    //                 $this->setGameOver(true);
    //                 $this->setAmount(0);
    //             },
    //             'Bank Wins (Empty Deck)' => function () use (&$scoreBoard, $bank) {
    //                 $scoreBoard['bank']++;
    //                 $bank->win($this->getAmount() * 2);
    //                 $this->setRoundOver(true);
    //                 $this->setGameOver(true);
    //                 $this->setAmount(0);
    //             },
    //             'Bank Wins (Tie) (Empty Deck)' => function () use (&$scoreBoard, $bank) {
    //                 $scoreBoard['bank']++;
    //                 $bank->win($this->getAmount() * 2);
    //                 $this->setRoundOver(true);
    //                 $this->setGameOver(true);
    //                 $this->setAmount(0);
    //             },
    //             'Bank Wins (Tie)' => function () use (&$scoreBoard, $bank) {
    //                 $bank->win($this->getAmount() * 2);
    //                 $scoreBoard['bank']++;
    //                 $this->setAmount(0);
    //             },
    //             'Bank Wins' => function () use (&$scoreBoard, $bank) {
    //                 $bank->win($this->getAmount() * 2);
    //                 $scoreBoard['bank']++;
    //                 $this->setAmount(0);
    //             },
    //             'Bank Bust' => function () use (&$scoreBoard, $player) {
    //                 $player->win($this->getAmount() * 2);
    //                 $scoreBoard['player']++;
    //                 $this->setAmount(0);
    //             },
    //             'Player Wins' => function () use (&$scoreBoard, $player) {
    //                 $player->win($this->getAmount() * 2);
    //                 $scoreBoard['player']++;
    //                 $this->setAmount(0);
    //             },
    //         ];

    //         if (array_key_exists($gameStatus, $handlers)) {
    //             $handlers[$gameStatus](); // Call the corresponding handler function
    //         }
    
    //         $this->setScoreBoard($scoreBoard);
    //         return $endMessage;
    //     }
        
    //     return null;
    // }

    // private function getEndMessage(string $gameStatus): ?array
    // {
    //     $messages = [
    //         'Player Bust' => ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'],
    //         'Bank Wins' => ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'],
    //         'Bank Wins (Tie)' => ['message' => 'Du förlorade spelomgången!', 'type' => 'lose'],
    //         'Player Bankrupt' => ['message' => 'Dina pengar är slut - du förlorade spelet!', 'type' => 'lose'],
    //         'Bank Bankrupt' => ['message' => 'Banken är tömd - du vann spelet!', 'type' => 'win'],
    //         'Player Wins (Empty Deck)' => ['message' => 'Kortleken är slut - du vann spelet!', 'type' => 'win'],
    //         'Bank Wins (Empty Deck)' => ['message' => 'Kortleken är slut - du förlorade spelet!', 'type' => 'lose'],
    //         'Bank Wins (Tie) (Empty Deck)' => ['message' => 'Kortleken är slut - tie - du förlorade spelet!', 'type' => 'lose'],
    //         'Bank Bust' => ['message' => 'Du vann spelomgången!', 'type' => 'win'],
    //         'Player Wins' => ['message' => 'Du vann spelomgången!', 'type' => 'win'],
    //     ];

    //     return $messages[$gameStatus] ?? null;
    // }

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
                continue;
            }

            if (isset($cardValues[$value])) {
                $total += $cardValues[$value];
                continue;
            }
            $total += (int)$value;

        }

        for ($i = 0; $i < $aceCount; $i++) {
            if ($total + 14 > 21) {
                return $total + 1;
            }
            $total += 14;
        }

        return $total;
    }

//     public function gameStatus(Player $player, Player $bank, int $playerMoney, int $bankMoney): string
//     {
//         $playerScore = $this->calculatePoints($player);
//         $bankScore = $this->calculatePoints($bank);

//         if ($this->isPlayerBustAndBankrupt($playerScore, $playerMoney)) {
//             return 'Player Bankrupt';
//         }

//         if ($this->isPlayerBust($playerScore)) {
//             return 'Player Bust';
//         }

//         if ($this->isAnyBankrupt($playerMoney, $bankMoney)) {
//             return $playerMoney === 0 ? 'Player Bankrupt' : 'Bank Bankrupt';
//         }

//         if ($this->deck->isEmpty()) {
//             return $this->determineEmptyDeckOutcome($playerScore, $bankScore);
//         }

//         if ($this->isRoundOver()) {
//             return $this->determineRoundOverOutcome($playerScore, $bankScore);
//         }

//         return 'Game On';
//     }

//     private function isPlayerBustAndBankrupt(int $playerScore, int $playerMoney): bool
//     {
//         return $playerScore > 21 && ($playerMoney === 0);
//     }

//     private function isPlayerBust(int $playerScore): bool
//     {
//         return $playerScore > 21;
//     }

//     private function isAnyBankrupt(int $playerMoney, int $bankMoney): bool
//     {
//         return $this->isRoundOver() && ($playerMoney === 0 || $bankMoney === 0);
//     }

//     private function determineEmptyDeckOutcome(int $playerScore, int $bankScore): string
//     {
//         if ($playerScore <=21 && ($bankScore > 21 || $playerScore > $bankScore)) {
//             return 'Player Wins (Empty Deck)';
//         } elseif ($bankScore <= 21 && ($playerScore > 21 || $bankScore > $playerScore)) {
//             return 'Bank Wins (Empty Deck)';
//         }

//         return 'Bank Wins (Tie) (Empty Deck)';
//     }

//     private function determineRoundOverOutcome(int $playerScore, int $bankScore): string
//     {
//         if ($bankScore === $playerScore) {
//             return 'Bank Wins (Tie)';
//         } elseif ($bankScore > $playerScore && $bankScore <= 21) {
//             return 'Bank Wins';
//         } elseif ($bankScore > 21) {
//             return 'Bank Bust';
//         } elseif ($playerScore > $bankScore && $playerScore <= 21) {
//             return 'Player Wins';
//         }

//         return 'Game On';
//     }
}
