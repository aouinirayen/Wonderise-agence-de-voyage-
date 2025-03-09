<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class DeepgramService
{
    private $apiKey;
    private $client;
    private $logger;

    public function __construct(string $apiKey = null, LoggerInterface $logger = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['DEEPGRAM_API_KEY'];
        $this->client = HttpClient::create();
        $this->logger = $logger;
    }

    public function transcribeAudio(string $audioData): ?string
    {
        try {
            $response = $this->client->request('POST', 'https://api.deepgram.com/v1/listen?model=general-enhanced', [
                'headers' => [
                    'Authorization' => 'Token ' . $this->apiKey,
                    'Content-Type' => 'audio/wav',
                ],
                'body' => $audioData,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $errorMessage = sprintf('Deepgram API returned status code %d', $statusCode);
                if ($this->logger) {
                    $this->logger->error($errorMessage, [
                        'response' => $response->getContent(false),
                        'headers' => $response->getHeaders()
                    ]);
                }
                throw new \Exception($errorMessage);
            }

            $data = $response->toArray();
            
            if (!isset($data['results']['channels'][0]['alternatives'][0]['transcript'])) {
                $errorMessage = 'Unexpected response format from Deepgram';
                if ($this->logger) {
                    $this->logger->error($errorMessage, ['response' => $data]);
                }
                throw new \Exception($errorMessage);
            }

            return $data['results']['channels'][0]['alternatives'][0]['transcript'];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Transcription error: ' . $e->getMessage());
            }
            throw $e; // Re-throw to be handled by the controller
        }
    }
}
