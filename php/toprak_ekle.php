<?php
// Toprak ölçümü kaydı (POST: parsel_id, yontem, olcum_tarihi, ph, nem, tuz, fosfor)
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';

$k = rol_gerekli('Çiftçi');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}

$pid = filter_var($_POST['parsel_id'] ?? null, FILTER_VALIDATE_INT);
if ($pid === false || $pid === null) {
    header('Location: panel.php');
    exit;
}

// Sahiplik: parsel bu kullanıcının değilse panele dön (varlığı sızdırılmaz)
$st = db()->prepare('SELECT 1 FROM PARSEL WHERE parsel_id = :p AND kullanici_id = :k');
$st->execute([':p' => $pid, ':k' => $k['id']]);
if (!$st->fetchColumn()) {
    header('Location: panel.php');
    exit;
}

function toprak_hatasi(int $pid, string $kod): never
{
    header('Location: detay-toprak.php?id=' . $pid . '&hata=' . $kod);
    exit;
}

function bos_sayi(string $v): ?float
{
    $v = str_replace(',', '.', trim($v));
    return $v === '' ? null : (is_numeric($v) ? (float) $v : NAN);
}

$yontem = (string) ($_POST['yontem'] ?? '');
if (!in_array($yontem, ['Laboratuvar', 'Tahmini'], true)) toprak_hatasi($pid, 'yontem');

$tarih = (string) ($_POST['olcum_tarihi'] ?? '');
$t = DateTime::createFromFormat('!Y-m-d', $tarih);
if (!$t || $t->format('Y-m-d') !== $tarih || $t > new DateTime('today')) toprak_hatasi($pid, 'tarih');

$ph  = bos_sayi((string) ($_POST['ph'] ?? ''));
$nem = bos_sayi((string) ($_POST['nem'] ?? ''));
if ($ph !== null && (is_nan($ph) || $ph < 0 || $ph > 14)) toprak_hatasi($pid, 'ph');
if ($nem !== null && (is_nan($nem) || $nem < 0 || $nem > 100)) toprak_hatasi($pid, 'nem');

$seviyeler = ['Düşük', 'Orta', 'Yüksek'];
$tuz    = (string) ($_POST['tuz'] ?? '');
$fosfor = (string) ($_POST['fosfor'] ?? '');
if (($tuz !== '' && !in_array($tuz, $seviyeler, true)) || ($fosfor !== '' && !in_array($fosfor, $seviyeler, true))) {
    toprak_hatasi($pid, 'deger');
}
if ($ph === null && $nem === null && $tuz === '' && $fosfor === '') toprak_hatasi($pid, 'bos');

$st = db()->prepare(
    'INSERT INTO TOPRAK_OLCUM (parsel_id, kaynak_id, yontem, ph, nem, tuz, fosfor, olcum_tarihi)
     VALUES (:p, NULL, :y, :ph, :nem, :tuz, :fos, :t)'
);
$st->execute([':p' => $pid, ':y' => $yontem, ':ph' => $ph, ':nem' => $nem,
              ':tuz' => $tuz !== '' ? $tuz : null, ':fos' => $fosfor !== '' ? $fosfor : null, ':t' => $tarih]);

header('Location: detay-toprak.php?id=' . $pid . '&tamam=1');
exit;
