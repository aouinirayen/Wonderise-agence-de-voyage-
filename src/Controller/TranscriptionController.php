<?php

namespace App\Controller;

use App\Service\DeepgramService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/api')]
class TranscriptionController extends AbstractController
{
    private $deepgramService;
    private $logger;

    public function __construct(DeepgramService $deepgramService, LoggerInterface $logger)
    {
        $this->deepgramService = $deepgramService;
        $this->logger = $logger;
    }

    #[Route('/transcribe', name: 'app_transcribe', methods: ['POST'])]
    public function transcribe(Request $request): JsonResponse
    {
        try {
            $audioFile = $request->files->get('audio');
            
            if (!$audioFile) {
                throw new \Exception('No audio file received');
            }

            // Log file information
            $this->logger->info('Received audio file', [
                'originalName' => $audioFile->getClientOriginalName(),
                'mimeType' => $audioFile->getMimeType(),
                'size' => $audioFile->getSize()
            ]);

            // Read the audio file content
            $audioData = file_get_contents($audioFile->getPathname());
            
            if (!$audioData) {
                throw new \Exception('Failed to read audio file');
            }

            // Get the transcription from Deepgram
            $transcription = $this->deepgramService->transcribeAudio($audioData);

            return new JsonResponse(['text' => $transcription]);
        } catch (\Exception $e) {
            $this->logger->error('Transcription error: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Transcription failed: ' . $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
