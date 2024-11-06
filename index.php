<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validador de Identidad</title>
    <style>
        #video {
            border: 1px solid black;
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        #canvas {
            display: block;
            margin-top: 10px;
            border: 1px solid black;
        }
        #step-container {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h2>Proceso de Verificación de Identidad</h2>
    <div id="step-1">
        <h3>Paso 1: Capturar la Imagen Frontal del Documento</h3>
        <video id="video-front" width="1920" height="1080" autoplay playsinline></video><br>
        <button id="captureFront">Capturar Imagen Frontal</button>
        <canvas id="frontCanvas" width="1920" height="1080"></canvas>
        <p id="frontResult"></p>
        <button id="confirmFront" style="display: none;">Confirmar Imagen Frontal</button>
        <button id="retakeFront" style="display: none;">Volver a Capturar</button>
    </div>

    <div id="step-2" style="display: none;">
        <h3>Paso 2: Capturar la Imagen Trasera del Documento</h3>
        <video id="video-back" width="1920" height="1080" autoplay playsinline></video><br>
        <button id="captureBack">Capturar Imagen Trasera</button>
        <canvas id="backCanvas" width="1920" height="1080"></canvas>
        <p id="backResult"></p>
        <button id="confirmBack" style="display: none;">Confirmar Imagen Trasera</button>
        <button id="retakeBack" style="display: none;">Volver a Capturar</button>
    </div>

    <div id="step-3" style="display: none;">
        <h3>Paso 3: Capturar una Imagen de Rostro en Tiempo Real</h3>
        <video id="video-face" width="1920" height="1080" autoplay playsinline></video><br>
        <button id="captureFace">Capturar Imagen de Rostro</button>
        <canvas id="faceCanvas" width="1920" height="1080"></canvas>
        <p id="faceResult"></p>
        <button id="confirmFace" style="display: none;">Confirmar Imagen de Rostro</button>
        <button id="retakeFace" style="display: none;">Volver a Capturar</button>
        <button id="verifyIdentity" style="display: none;">Verificar Identidad</button>
    </div>

    <p id="finalResult"></p>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoFront = document.getElementById('video-front');
            const videoBack = document.getElementById('video-back');
            const videoFace = document.getElementById('video-face');
            const frontCanvas = document.getElementById('frontCanvas');
            const backCanvas = document.getElementById('backCanvas');
            const faceCanvas = document.getElementById('faceCanvas');
            const frontContext = frontCanvas.getContext('2d');
            const backContext = backCanvas.getContext('2d');
            const faceContext = faceCanvas.getContext('2d');
            const frontResult = document.getElementById('frontResult');
            const backResult = document.getElementById('backResult');
            const faceResult = document.getElementById('faceResult');
            const finalResult = document.getElementById('finalResult');
            const captureFrontButton = document.getElementById('captureFront');
            const captureBackButton = document.getElementById('captureBack');
            const captureFaceButton = document.getElementById('captureFace');
            const confirmFrontButton = document.getElementById('confirmFront');
            const retakeFrontButton = document.getElementById('retakeFront');
            const confirmBackButton = document.getElementById('confirmBack');
            const retakeBackButton = document.getElementById('retakeBack');
            const confirmFaceButton = document.getElementById('confirmFace');
            const retakeFaceButton = document.getElementById('retakeFace');
            const verifyIdentityButton = document.getElementById('verifyIdentity');

            let frontImageData = null;
            let backImageData = null;
            let capturedImageData = null;

            function startCamera(facingMode, videoElement) {
                navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: facingMode,
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        frameRate: { ideal: 30, max: 60 }
                    }
                })
                .then(function(stream) {
                    videoElement.srcObject = stream;
                })
                .catch(function(err) {
                    console.error("Error al acceder a la cámara: ", err);
                    finalResult.textContent = "No se pudo acceder a la cámara. Intentando restablecer permisos...";
                    requestCameraPermission(facingMode, videoElement);
                });
            }

            function requestCameraPermission(facingMode, videoElement) {
                navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: facingMode,
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        frameRate: { ideal: 30, max: 60 }
                    }
                })
                .then(function(stream) {
                    videoElement.srcObject = stream;
                })
                .catch(function(err) {
                    console.error("Permiso de cámara denegado: ", err);
                    finalResult.textContent = "No se pudo acceder a la cámara después de solicitar permiso nuevamente. Intentando de nuevo...";
                    setTimeout(() => requestCameraPermission(facingMode, videoElement), 2000);
                });
            }

            captureFrontButton.addEventListener('click', function() {
                startCamera('environment', videoFront);
                setTimeout(() => {
                    frontCanvas.width = videoFront.videoWidth;
                    frontCanvas.height = videoFront.videoHeight;

                    frontContext.drawImage(videoFront, 0, 0, frontCanvas.width, frontCanvas.height);
                    frontImageData = frontCanvas.toDataURL('image/png', 1.0);
                    frontResult.textContent = "Imagen frontal capturada con éxito.";
                    confirmFrontButton.style.display = 'inline';
                    retakeFrontButton.style.display = 'inline';
                }, 2000);
            });

            confirmFrontButton.addEventListener('click', function() {
                document.getElementById('step-1').style.display = 'none';
                document.getElementById('step-2').style.display = 'block';
                startCamera('environment', videoBack);
            });

            retakeFrontButton.addEventListener('click', function() {
                frontResult.textContent = "";
                confirmFrontButton.style.display = 'none';
                retakeFrontButton.style.display = 'none';
                startCamera('environment', videoFront);
            });

            captureBackButton.addEventListener('click', function() {
                setTimeout(() => {
                    backCanvas.width = videoBack.videoWidth;
                    backCanvas.height = videoBack.videoHeight;

                    backContext.drawImage(videoBack, 0, 0, backCanvas.width, backCanvas.height);
                    backImageData = backCanvas.toDataURL('image/png', 1.0);
                    backResult.textContent = "Imagen trasera capturada con éxito.";
                    confirmBackButton.style.display = 'inline';
                    retakeBackButton.style.display = 'inline';
                }, 2000);
            });

            confirmBackButton.addEventListener('click', function() {
                document.getElementById('step-2').style.display = 'none';
                document.getElementById('step-3').style.display = 'block';
                startCamera('user', videoFace);
            });

            retakeBackButton.addEventListener('click', function() {
                backResult.textContent = "";
                confirmBackButton.style.display = 'none';
                retakeBackButton.style.display = 'none';
                startCamera('environment', videoBack);
            });

            captureFaceButton.addEventListener('click', function() {
                startCamera('user', videoFace);
                setTimeout(() => {
                    faceCanvas.width = videoFace.videoWidth;
                    faceCanvas.height = videoFace.videoHeight;

                    faceContext.drawImage(videoFace, 0, 0, faceCanvas.width, faceCanvas.height);
                    capturedImageData = faceCanvas.toDataURL('image/png', 1.0);
                    faceResult.textContent = "Imagen de rostro capturada con éxito.";
                    confirmFaceButton.style.display = 'inline';
                    retakeFaceButton.style.display = 'inline';
                }, 2000);
            });

            confirmFaceButton.addEventListener('click', function() {
                verifyIdentityButton.style.display = 'block';
            });

            retakeFaceButton.addEventListener('click', function() {
                faceResult.textContent = "";
                confirmFaceButton.style.display = 'none';
                retakeFaceButton.style.display = 'none';
                startCamera('user', videoFace);
            });

            verifyIdentityButton.addEventListener('click', function() {
                const userId = 1;

                fetch('verificar_facial.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        front_image: frontImageData,
                        back_image: backImageData,
                        captured_image: capturedImageData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        finalResult.textContent = "Identidad verificada con éxito.";
                    } else {
                        finalResult.textContent = "Error: " + data.message;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    finalResult.textContent = "Ocurrió un error durante la verificación.";
                });
            });
        });
    </script>
</body>
</html>