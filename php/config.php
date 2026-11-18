<?php
// Uygulama ayarları
declare(strict_types=1);

const DB_YOLU = __DIR__ . '/../veritabani/akilli_tarim_v1.db';
const UYGULAMA_ADI = 'Akıllı Tarım';
const OTURUM_ADI = 'akilli_tarim';

// Yönlendirme hedefleri (rol → sayfa)
const ROL_SAYFA = [
    'Çiftçi'   => 'panel.php',
    'Danışman' => 'danisman.php',
];

// Geliştirme sırasında hataları göster
ini_set('display_errors', '1');
error_reporting(E_ALL);
