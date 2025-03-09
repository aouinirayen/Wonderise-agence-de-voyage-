<?php

namespace App\Service;

class SentimentAnalysisService
{
    private array $positiveWords = [
        // Très positif (poids: 2)
        'excellent' => 2, 'extraordinaire' => 2, 'fantastique' => 2, 'incroyable' => 2,
        'parfait' => 2, 'exceptionnel' => 2, 'merveilleux' => 2, 'formidable' => 2,
        
        // Positif (poids: 1)
        'bien' => 1, 'super' => 1, 'génial' => 1, 'agréable' => 1, 'bon' => 1,
        'content' => 1, 'satisfait' => 1, 'heureux' => 1, 'sympa' => 1, 'cool' => 1,
        'aime' => 1, 'adore' => 1, 'recommande' => 1, 'bravo' => 1, 'magnifique' => 1,
        'beau' => 1, 'belle' => 1, 'propre' => 1, 'confortable' => 1, 'pratique' => 1,
        'utile' => 1, 'efficace' => 1, 'rapide' => 1, 'professionnel' => 1,
        'accueillant' => 1, 'chaleureux' => 1, 'souriant' => 1, 'attentif' => 1,
        'disponible' => 1, 'compétent' => 1, 'fiable' => 1, 'ponctuel' => 1
    ];

    private array $negativeWords = [
        // Très négatif (poids: -2)
        'horrible' => -2, 'catastrophique' => -2, 'désastreux' => -2, 'détestable' => -2,
        'inadmissible' => -2, 'inacceptable' => -2, 'scandaleux' => -2,
        
        // Négatif (poids: -1)
        'mauvais' => -1, 'nul' => -1, 'décevant' => -1, 'médiocre' => -1,
        'désagréable' => -1, 'déplorable' => -1, 'déteste' => -1, 'ennuyeux' => -1,
        'pénible' => -1, 'insatisfait' => -1, 'déçu' => -1, 'éviter' => -1,
        'problème' => -1, 'difficile' => -1, 'pauvre' => -1, 'insuffisant' => -1,
        'sale' => -1, 'malpropre' => -1, 'inconfortable' => -1, 'inutile' => -1,
        'inefficace' => -1, 'lent' => -1, 'incompétent' => -1, 'impoli' => -1,
        'désorganisé' => -1, 'retard' => -1, 'cher' => -1, 'arnaque' => -1
    ];

    private array $intensifiers = [
        'très' => 1.5, 'trop' => 1.5, 'vraiment' => 1.5, 'totalement' => 1.5,
        'complètement' => 1.5, 'absolument' => 1.5, 'extrêmement' => 1.5,
        'particulièrement' => 1.5
    ];

    private array $negators = [
        'ne', 'pas', 'plus', 'jamais', 'rien', 'aucun', 'sans'
    ];

    public function analyzeSentiment(string $text): array
    {
        $text = mb_strtolower($text);
        $words = preg_split('/\s+/', $text);
        
        $score = 0;
        $wordCount = count($words);
        $lastIntensifier = 1;
        $negationActive = false;
        
        for ($i = 0; $i < $wordCount; $i++) {
            $word = $words[$i];
            
            // Vérifier les négations
            if (in_array($word, $this->negators)) {
                $negationActive = true;
                continue;
            }
            
            // Vérifier les intensificateurs
            if (isset($this->intensifiers[$word])) {
                $lastIntensifier = $this->intensifiers[$word];
                continue;
            }
            
            // Calculer le score du mot
            $wordScore = 0;
            if (isset($this->positiveWords[$word])) {
                $wordScore = $this->positiveWords[$word];
            } elseif (isset($this->negativeWords[$word])) {
                $wordScore = $this->negativeWords[$word];
            }
            
            if ($wordScore !== 0) {
                // Appliquer la négation et l'intensification
                if ($negationActive) {
                    $wordScore *= -1;
                    $negationActive = false;
                }
                $wordScore *= $lastIntensifier;
                $lastIntensifier = 1;
                
                $score += $wordScore;
            }
        }
        
        // Normaliser le score entre -1 et 1
        $normalizedScore = $wordCount > 0 ? $score / ($wordCount * 2) : 0;
        $normalizedScore = max(-1, min(1, $normalizedScore));
        
        return [
            'score' => $normalizedScore,
            'sentiment' => $this->getSentimentCategory($normalizedScore),
            'raw_score' => $score,
            'word_count' => $wordCount
        ];
    }

    private function getSentimentCategory(float $score): string
    {
        if ($score > 0.2) {
            return 'positif';
        } elseif ($score < -0.2) {
            return 'négatif';
        }
        return 'neutre';
    }
}