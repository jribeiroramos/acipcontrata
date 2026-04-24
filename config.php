<?php
/**
 * CONFIGURAÇÃO DE SESSÃO UNIFICADA - RAIZ
 */
if (session_status() === PHP_SESSION_NONE) {
    // Configura o cookie para a raiz do IP
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, 
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_rh');
define('DB_USER', 'rh_user');
define('DB_PASS', 'senha_segura');
