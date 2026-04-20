<?php
declare(strict_types=1);

require_once __DIR__ . '/components/conf/conf.php';

if (Auth::check()) {
    redirect('pages/inicio.php');
}

redirect('pages/catalogo.php');