<?php
// Parola özeti (veritabani/demo_parola.py ile aynı algoritma)
declare(strict_types=1);

const PBKDF2_TUR = 100000;

function parola_ozeti(string $parola, string $tuz): string
{
    return hash_pbkdf2('sha256', $parola, $tuz, PBKDF2_TUR);
}

function yeni_tuz(): string
{
    return bin2hex(random_bytes(16));
}
