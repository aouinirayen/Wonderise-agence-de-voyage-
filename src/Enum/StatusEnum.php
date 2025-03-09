<?php

namespace App\Enum;

enum StatusEnum: string
{
    case Traitee = 'traitée';
    case Rejetee = 'rejetée';
    case EnCours = 'en cours'; // Garde l'espace
    case Envoyee = 'envoyée';

    public static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
