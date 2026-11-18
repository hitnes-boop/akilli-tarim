<?php
// Parsel silme (POST: parsel_id) — yalnızca parselin sahibi silebilir
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/oturum.php';

$k = rol_gerekli('Çiftçi');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}

$id = filter_var($_POST['parsel_id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    http_response_code(400);
    exit('Geçersiz parsel.');
}

// kullanici_id koşulu sahiplik denetimidir; ekim, ölçüm vb. kayıtlar ON DELETE CASCADE ile silinir
$st = db()->prepare('DELETE FROM PARSEL WHERE parsel_id = :p AND kullanici_id = :k');
$st->execute([':p' => $id, ':k' => $k['id']]);

if ($st->rowCount() === 0) {
    http_response_code(404);
    exit('Parsel bulunamadı.');
}

header('Location: panel.php');
exit;
