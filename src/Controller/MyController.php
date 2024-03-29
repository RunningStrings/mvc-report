<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class MyController extends AbstractController
{
    #[Route("/", name: "home")]
    public function home(): Response
    {
        $metadata = $this->loadMetaData();
        return $this->render('home.html.twig', [
            'metadata' => $metadata,
        ]);
    }

    #[Route("/about", name: "about")]
    public function about(): Response
    {
        $metadata = $this->loadMetaData();
        return $this->render('about.html.twig', [
            'metadata' => $metadata,
        ]);
    }

    #[Route("/report", name: "report")]
    public function report(): Response
    {
        $metadata = $this->loadMetaData();
        return $this->render('report.html.twig', [
            'metadata' => $metadata,
        ]);
    }

    #[Route("/lucky", name: "lucky")]
    public function lucky(): Response
    {
        $number = random_int(0, 100);

        $metadata = $this->loadMetaData();
        $data = [
            'number' => $number,
            'metadata' => $metadata,
        ];

        return $this->render('lucky.html.twig', $data);
    }

    private function loadMetaData()
    {
        $metadata = Yaml::parseFile('../config/metadata.yaml');
        return $metadata['metadata'] ?? [];
    }
}