<?php
// Oturum yönetimi ve yetki kontrolü
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function oturum_baslat(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(OTURUM_ADI);
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function kullanici(): ?array
{
    oturum_baslat();
    return $_SESSION['kullanici'] ?? null;
}

function oturum_ac(array $k): void
{
    oturum_baslat();
    session_regenerate_id(true); // oturum sabitleme saldırısına karşı
    $_SESSION['kullanici'] = [
        'id'  => (int) $k['kullanici_id'],
        'ad'  => $k['ad'],
        'rol' => $k['rol'],
    ];
}

function oturum_kapat(): void
{
    oturum_baslat();
    $_SESSION = [];
    session_destroy();
}

function giris_gerekli(): array
{
    $k = kullanici();
    if ($k === null) {
        header('Location: index.php');
        exit;
    }
    return $k;
}

function rol_gerekli(string $rol): array
{
    $k = giris_gerekli();
    if ($k['rol'] !== $rol) {
        http_response_code(403);
        exit('Bu sayfaya erişim yetkiniz yok.');
    }
    return $k;
}

function h(?string $s): string // çıktıyı HTML'e karşı kaçışla
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
