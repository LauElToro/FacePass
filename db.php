<?php
require_once 'config.php';

function getDatabaseConnection() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die('Error al conectar con la base de datos: ' . $e->getMessage());
    }
}

function guardarImagen($imageData, $path) {
    $imageData = preg_replace('#^data:image/\\w+;base64,#i', '', $imageData);
    $image = base64_decode($imageData);
    if ($image === false) {
        throw new Exception('La imagen no pudo ser decodificada correctamente.');
    }

    if (file_put_contents($path, $image) === false) {
        throw new Exception('No se pudo guardar la imagen en ' . $path);
    }
}

?>
