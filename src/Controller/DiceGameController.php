<?php

namespace App\Controller;

use App\Dice\Dice;
use App\Dice\DiceGraphic;
use App\Dice\DiceHand;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class DiceGameController extends AbstractController
{
    private Yaml $yamlParser;

    public function __construct(Yaml $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    #[Route("/game/pig", name: "pig_start")]
    public function home(): Response
    {
        $metadata = $this->loadMetaData();

        return $this->render('pig/home.html.twig', ['metadata' => $metadata]);
    }

    /**
     * @return array<string>
     */
    private function loadMetaData():array
    {
        $metadata = $this->yamlParser->parseFile('../config/metadata.yaml');
        if (is_array($metadata)) {
            return $metadata['metadata'] ?? [];
        }
        return [];
    }

    #[Route("/game/pig/test/roll", name: "test_roll_dice")]
    public function testRollDice(): Response
    {
        $die = new Dice();

        $data = [
            "dice" => $die->roll(),
            "diceString" => $die->getAsString(),
            "metadata" => $this->loadMetaData(),
        ];

        return $this->render('pig/test/roll.html.twig', $data);
    }

    #[Route("/game/pig/test/roll/{num<\d+>}", name: "test_roll_num_dices")]
    public function testRollDices(int $num): Response
    {
        if ($num > 99) {
            throw new Exception("Can not roll more than 99 dices!");
        }

        $diceRoll = [];
        for ($i = 1; $i <= $num; $i++) {
            $die = new DiceGraphic();
            $die->roll();
            $diceRoll[] = $die->getAsString();
        }

        $data = [
            "num_dices" => count($diceRoll),
            "diceRoll" => $diceRoll,
            "metadata" => $this->loadMetaData(),
        ];

        return $this->render('pig/test/roll_many.html.twig', $data);
    }

    #[Route("/game/pig/test/dicehand/{num<\d+>}", name: "test_dicehand")]
    public function testDiceHand(int $num): Response
    {
        if ($num > 99) {
            throw new Exception("Can not roll more than 99 dices!");
        }

        $hand = new DiceHand();
        for ($i = 1; $i <= $num; $i++) {
            $hand->add($i % 2 === 1 ? new DiceGraphic() : new Dice());
        }

        $hand->roll();

        $data = [
            "num_dices" => $hand->getNumberDices(),
            "diceRoll" => $hand->getString(),
            "metadata" => $this->loadMetaData(),
        ];

        return $this->render('pig/test/dicehand.html.twig', $data);
    }

    #[Route("/game/pig/init", name: "pig_init_get", methods: ['GET'])]
    public function init(): Response
    {
        $data = [
            "metadata" => $this->loadMetaData(),
        ];
        return $this->render('pig/init.html.twig', $data);
    }

    #[Route("/game/pig/init", name: "pig_init_post", methods: ['POST'])]
    public function initCallback(
        Request $request,
        SessionInterface $session
    ): Response {
        $numDice = $request->request->get('num_dices');

        $hand = new DiceHand();
        for ($i = 1; $i <= $numDice; $i++) {
            $hand->add(new DiceGraphic());
        }
        $hand->roll();

        $session->set("pig_dicehand", $hand);
        $session->set("pig_dices", $numDice);
        $session->set("pig_round", 0);
        $session->set("pig_total", 0);

        $data = [
            "metadata" => $this->loadMetaData(),
        ];

        return $this->redirectToRoute('pig_play', $data);
    }

    #[Route("/game/pig/play", name: "pig_play", methods: ['GET'])]
    public function play(
        SessionInterface $session,
        DiceHand $dicehand
    ): Response {
        $dicehand = $session->get("pig_dicehand");
        if ($dicehand instanceof DiceHand) {
            $diceValues = $dicehand->getString();
        }
        $diceValues = [];

        $data = [
            "pigDices" => $session->get("pig_dices"),
            "pigRound" => $session->get("pig_round"),
            "pigTotal" => $session->get("pig_total"),
            "diceValues" => $diceValues,
            "metadata" => $this->loadMetaData(),
        ];

        return $this->render('pig/play.html.twig', $data);
    }

    #[Route("/game/pig/roll", name: "pig_roll", methods: ['POST'])]
    public function roll(
        SessionInterface $session,
        DiceHand $hand
    ): Response {
        $hand = $session->get("pig_dicehand");
        if ($hand instanceof DiceHand) {
            $hand->roll();
        }

        $roundTotal = $session->get("pig_round");
        $round = 0;
        if ($hand instanceof DiceHand) {
            $values = $hand->getValues();

            foreach ($values as $value) {
                if ($value === 1) {
                    $round = 0;
                    $roundTotal = 0;
                    $this->addFlash(
                        'warning',
                        'You got a 1 and you lost the round points!'
                    );
                    break;
                }
                $round += $value;
            }
        }

        $session->set("pig_round", $roundTotal + $round);

        return $this->redirectToRoute('pig_play');
    }

    #[Route("/game/pig/save", name: "pig_save", methods: ['POST'])]
    public function save(
        SessionInterface $session
    ): Response {
        $roundTotal = $session->get("pig_round");
        $gameTotal = $session->get("pig_total");
        $session->set("pig_round", 0);
        $session->set("pig_total", $roundTotal + $gameTotal);
        $this->addFlash(
            'notice',
            'Your round was saved to the total!'
        );

        return $this->redirectToRoute('pig_play');
    }
}
