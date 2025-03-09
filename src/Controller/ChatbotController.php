<?php

namespace App\Controller;

use App\Service\BotManFactoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    private BotManFactoryService $botManFactory;

    public function __construct(BotManFactoryService $botManFactory)
    {
        $this->botManFactory = $botManFactory;
    }

    #[Route('/chatbot/api', name: 'app_chatbot', methods: ['POST'])]
    public function handle(Request $request): JsonResponse
    {
        $botman = $this->botManFactory->createBotMan();
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        // 🔥 Debug : Vérifier si la requête arrive bien
        file_put_contents('chatbot_debug.log', "Message reçu : " . $userMessage . PHP_EOL, FILE_APPEND);

        // Message par défaut
        $responseMessage = "Je n'ai pas compris votre question.";

        // Définition des réponses
        $botman->hears('bonjour', function ($bot) use (&$responseMessage) {
            $responseMessage = "Salut ! Comment puis-je vous aider ?";
        });

        $botman->hears('réclamation', function ($bot) use (&$responseMessage) {
            $responseMessage = "Vous pouvez déposer une réclamation dans la section 'Nouvelle réclamation'.";
        });

        $botman->fallback(function ($bot) use (&$responseMessage) {
            $responseMessage = "Désolé, je ne comprends pas encore cette question.";
        });

        // Écoute du message utilisateur
        $botman->listen();

        // 🔥 Debug : Vérifier la réponse générée
        file_put_contents('chatbot_debug.log', "Réponse envoyée : " . $responseMessage . PHP_EOL, FILE_APPEND);

        return new JsonResponse(['message' => $responseMessage]);
    }

    #[Route('/chatbot', name: 'app_reclamation_chatbot', methods: ['GET'])]
    public function chatbot()
    {
        return $this->render('front_office/reclamation/chatbot.html.twig');
    }
}
