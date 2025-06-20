<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

class EmotionController extends AbstractController
{
    #[Route('/emotion', name: 'emotion_index')]
    public function index(): Response
    {
        return $this->render('emotion/index.html.twig', [
            'flask_video_url' => 'http://localhost:5001/video_feed',
        ]);
    }

    #[Route('/emotion/detect', name: 'emotion_detect', methods: ['GET'])]
    public function detectEmotion(): Response
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'http://localhost:5001/detect_emotion');
        $emotion = $response->getContent();

        return new Response($emotion);
    }
}