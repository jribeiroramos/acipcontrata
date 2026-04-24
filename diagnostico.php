<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Diagnóstico do Servidor - Sistema RH</h2>";

// 1. Teste de Versão do PHP
echo "<b>Versão do PHP:</b> " . PHP_VERSION . "<br>";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "✅ PHP OK (Versão recomendada)<br>";
} else {
    echo "⚠️ PHP Desatualizado (Recomendado >= 7.4)<br>";
}

// 2. Verificação de Extensões Necessárias
$extensoes = ['pdo_mysql', 'gd', 'mbstring', 'openssl'];
echo "<h3>Extensões do Sistema:</h3>";
foreach ($extensoes as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext: Instalada<br>";
    } else {
        echo "❌ $ext: <b>FALTANDO!</b> (Instale com: sudo apt install php-$ext)<br>";
    }
}

// 3. Teste de Conexão com o Banco de Dados
echo "<h3>Teste de Conexão MySQL:</h3>";
$host = 'localhost';
$db   = 'sistema_rh';
$user = 'root'; 
$pass = ''; // COLOQUE SUA SENHA AQUI

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    echo "✅ Conexão com o banco <b>'$db'</b> estabelecida com sucesso!<br>";
    
    // Teste de consulta rápida nas cidades
    $res = $pdo->query("SELECT COUNT(*) FROM cidades")->fetchColumn();
    echo "📊 Total de cidades encontradas no banco: <b>$res</b><br>";

} catch (PDOException $e) {
    echo "❌ Erro ao conectar: " . $e->getMessage() . "<br>";
}

// 4. Verificação de Permissões de Pasta (Para as fotos 3x4)
echo "<h3>Permissões de Escrita:</h3>";
$upload_dir = 'assets/fotos';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (is_writable($upload_dir)) {
    echo "✅ Pasta '$upload_dir' tem permissão de escrita (Para uploads de fotos).<br>";
} else {
    echo "❌ Pasta '$upload_dir' <b>SEM PERMISSÃO!</b> (Use: chmod 777 $upload_dir)<br>";
}
?>
