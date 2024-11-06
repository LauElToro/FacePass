<?php

require_once 'config.php';
require_once 'db.php';
header('Content-Type: application/json');

// Datos enviados desde el frontend
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;
$front_image = $input['front_image'] ?? null;
$back_image = $input['back_image'] ?? null;
$captured_image = $input['captured_image'] ?? null;

// Verificar que todos los datos estén presentes
if (!$user_id || !$front_image || !$back_image || !$captured_image) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

// Configuración de la API de reconocimiento facial (utilizando Face++)
$face_api_url_compare = 'https://api-us.faceplusplus.com/facepp/v3/compare';
$face_api_url_detect = 'https://api-us.faceplusplus.com/facepp/v3/detect';
$api_key = 'aCA9CJQ65P2qls1cm14TnpT5J9E2kan7';
$api_secret = 'C08eO20qj0DyUI7KFrDJL5wSNA4vwQ3X';

// Funciones para hacer solicitudes a la API de Face++
function makeFaceDetectRequest($image_base64, $api_key, $api_secret, $api_url)
{
    $image_file = base64ToImage($image_base64, true); // Redimensionar la imagen

    $post_fields = [
        'api_key' => $api_key,
        'api_secret' => $api_secret,
        'image_file' => curl_file_create($image_file)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Deshabilitar la verificación SSL (solo para pruebas, no recomendado en producción)
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(['success' => false, 'message' => 'Error en la solicitud cURL: ' . curl_error($ch)]);
        curl_close($ch);
        unlink($image_file);
        exit;
    }

    curl_close($ch);

    // Eliminar archivo temporal
    unlink($image_file);

    return json_decode($response, true);
}

function makeFaceCompareRequest($image_base64_1, $image_base64_2, $api_key, $api_secret, $api_url)
{
    $image_file_1 = base64ToImage($image_base64_1, true); // Redimensionar la imagen
    $image_file_2 = base64ToImage($image_base64_2, true); // Redimensionar la imagen

    $post_fields = [
        'api_key' => $api_key,
        'api_secret' => $api_secret,
        'image_file1' => curl_file_create($image_file_1),
        'image_file2' => curl_file_create($image_file_2)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Deshabilitar la verificación SSL (solo para pruebas, no recomendado en producción)
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(['success' => false, 'message' => 'Error en la solicitud cURL: ' . curl_error($ch)]);
        curl_close($ch);
        unlink($image_file_1);
        unlink($image_file_2);
        exit;
    }

    curl_close($ch);

    // Eliminar archivos temporales
    unlink($image_file_1);
    unlink($image_file_2);

    return json_decode($response, true);
}

// Función para convertir base64 a archivo de imagen temporal, con opción para redimensionar
function base64ToImage($base64_string, $resize = false)
{
    $data = explode(',', $base64_string);
    $image_data = base64_decode($data[1]);
    $temp_file = tempnam(sys_get_temp_dir(), 'face') . '.png';
    file_put_contents($temp_file, $image_data);

    if ($resize) {
        list($width, $height) = getimagesize($temp_file);
        $new_width = $width > 800 ? 800 : $width;
        $new_height = ($height / $width) * $new_width;

        $image = imagecreatefrompng($temp_file);
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Guardar la imagen redimensionada
        imagepng($resized_image, $temp_file);
        imagedestroy($image);
        imagedestroy($resized_image);
    }

    return $temp_file;
}

// Detectar rostros en la imagen del documento
$detect_response = makeFaceDetectRequest($front_image, $api_key, $api_secret, $face_api_url_detect);
if (isset($detect_response['error_message'])) {
    echo json_encode(['success' => false, 'message' => 'Error en la API de detección: ' . $detect_response['error_message']]);
    exit;
}

if (empty($detect_response['faces'])) {
    echo json_encode(['success' => false, 'message' => 'No se detectó ningún rostro en la imagen del documento. Por favor, asegúrese de que el documento esté bien iluminado y el rostro sea claramente visible.']);
    exit;
}

// Comparar la imagen del documento con la imagen del rostro
$compare_response = makeFaceCompareRequest($front_image, $captured_image, $api_key, $api_secret, $face_api_url_compare);
if (isset($compare_response['error_message'])) {
    echo json_encode(['success' => false, 'message' => 'Error en la API de comparación: ' . $compare_response['error_message']]);
    exit;
}

if (!isset($compare_response['confidence'])) {
    echo json_encode(['success' => false, 'message' => 'No se detectó ningún rostro en una o ambas imágenes. Por favor, asegúrese de que el documento esté bien iluminado y el rostro sea claramente visible.']);
    exit;
}

$confidence = $compare_response['confidence'];
$threshold = 75; // Umbral de confianza para determinar coincidencia

if ($confidence >= $threshold) {
    // Guardar los datos en la base de datos
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("INSERT INTO usuarios (documento, estado_verificacion, foto_documento, foto_capturada) VALUES (?, 'pendiente', ?, ?)");
        $stmt->execute([$user_id, $front_image, $captured_image]);

        echo json_encode(['success' => true, 'message' => 'Identidad verificada con éxito.']);

        // Redirigir a success.php
        header("Location: success.php");
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Las imágenes no coinciden con suficiente precisión.']);
}

?>