<?php
// verificar_documento.php

require_once 'config.php';
require_once 'db.php';

try {
    // Obtener y validar el contenido enviado desde el front-end
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['front_image']) || !isset($data['back_image']) || !isset($data['user_id'])) {
        throw new Exception('Datos insuficientes para la verificación.');
    }

    $userId = (int)$data['user_id'];
    $frontImage = $data['front_image'];
    $backImage = $data['back_image'];

    // Guardar las imágenes del documento
    $frontImagePath = 'uploads/document_front_user_' . $userId . '.png';
    $backImagePath = 'uploads/document_back_user_' . $userId . '.png';
    
    guardarImagen($frontImage, $frontImagePath);
    guardarImagen($backImage, $backImagePath);

    // Actualizar la base de datos con las rutas de las imágenes del documento
    $pdo = getDatabaseConnection();
    $sql = "UPDATE usuarios SET foto_documento = :foto_documento WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':foto_documento' => json_encode(['front' => $frontImagePath, 'back' => $backImagePath]), ':user_id' => $userId]);

    enviarRespuesta(true, 'Imágenes del documento guardadas con éxito.');
} catch (Exception $e) {
    enviarRespuesta(false, $e->getMessage());
}

function enviarRespuesta($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
}
?>
