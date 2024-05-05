<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;

class SessionController extends AbstractController
{
    private Yaml $yamlParser;

    public function __construct(Yaml $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    #[Route("/session", name: "session")]
    public function printSession(
        SessionInterface $session
    ): Response {
        $data = [
            "session" => $session->all(),
            "metadata" => $this->loadMetaData(),
        ];

        return $this->render('session.html.twig', $data);
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

    #[Route("/session/delete", name: "session_delete")]
    public function deleteSession(
        SessionInterface $session
    ): Response {
        $session->clear();
        $this->addFlash(
            'notice',
            'Sessionen har raderats!'
        );

        return $this->redirectToRoute('session');
    }
}
