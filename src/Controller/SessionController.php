<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;

class SessionController extends AbstractController
{
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

    private function loadMetaData()
    {
        $metadata = Yaml::parseFile('../config/metadata.yaml');
        return $metadata['metadata'] ?? [];
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
