<?php
// Archivo: dbconeccion.php

define('DB_HOST', 'localhost'); // Cambia por tu host
define('DB_NAME', 'nombre_de_tu_base_de_datos'); // Cambia por el nombre de tu base de datos
define('DB_USER', 'tu_usuario'); // Cambia por tu usuario de base de datos
define('DB_PASS', 'tu_contraseña'); // Cambia por tu contraseña de base de datos

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa a la base de datos.";
} catch (PDOException $e) {
    echo "Error al conectar a la base de datos: " . $e->getMessage();
}
?>
