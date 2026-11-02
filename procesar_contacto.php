<?php
/* ==========================================================================
   Ecosistema Digital Guemchon Altime — procesar_contacto.php
   Dominio: guemchonaltime.com | Operaciones: Altimeshoper SpA
   Procesamiento Seguro de Formulario de Contacto (Backend PHP / MySQL / Email)
   ========================================================================== */

// Configuración de encabezados y seguridad (CORS & JSON response)
header('Content-Type: application/json; charset=utf-8');

// Validar que la solicitud se realice mediante el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Método no permitido. Utilice la vía POST para enviar el formulario.'
    ]);
    exit;
}

/* --------------------------------------------------------------------------
   1. CAPTURA Y SANITIZACIÓN DE DATOS
   -------------------------------------------------------------------------- */
$nombre   = isset($_POST['nombre'])   ? trim(filter_var($_POST['nombre'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$email    = isset($_POST['email'])    ? trim(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) : '';
$telefono = isset($_POST['telefono']) ? trim(filter_var($_POST['telefono'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$empresa  = isset($_POST['empresa'])  ? trim(filter_var($_POST['empresa'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$mensaje  = isset($_POST['mensaje'])  ? trim(filter_var($_POST['mensaje'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';

/* --------------------------------------------------------------------------
   2. VALIDACIÓN DE CAMPOS OBLIGATORIOS
   -------------------------------------------------------------------------- */
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El campo nombre es obligatorio.';
}

if (!$email) {
    $errores[] = 'Por favor, ingrese un correo electrónico válido.';
}

if (empty($mensaje)) {
    $errores[] = 'El contenido del mensaje no puede estar vacío.';
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Errores de validación en los datos enviados.',
        'errors'  => $errores
    ]);
    exit;
}

/* --------------------------------------------------------------------------
   3. PERSISTENCIA EN BASE DE DATOS (MYSQL / PDO)
   -------------------------------------------------------------------------- */
$db_host = 'localhost';
$db_name = 'guemchon_db';
$db_user = 'root';
$db_pass = '';

$guardado_db = false;

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO contactos (nombre, email, telefono, empresa, mensaje, creado_en)
        VALUES (:nombre, :email, :telefono, :empresa, :mensaje, NOW())
    ");

    $stmt->execute([
        ':nombre'   => $nombre,
        ':email'    => $email,
        ':telefono' => $telefono,
        ':empresa'  => $empresa,
        ':mensaje'  => $mensaje
    ]);

    $guardado_db = true;
} catch (PDOException $e) {
    // Registro interno de error (logs servidor) sin exponer datos sensibles al cliente
    error_log("Error de conexión/inserción en BD: " . $e->getMessage());
}

/* --------------------------------------------------------------------------
   4. NOTIFICACIÓN VÍA CORREO ELECTRÓNICO
   -------------------------------------------------------------------------- */
$destinatario = 'contacto@guemchonaltime.com';
$asunto = "Nuevo Mensaje de Contacto — " . $nombre;

$cuerpo_email  = "Se ha recibido una nueva consulta desde el sitio web guemchonaltime.com:

";
$cuerpo_email .= "Nombre: " . $nombre . "
";
$cuerpo_email .= "Email: " . $email . "
";
$cuerpo_email .= "Teléfono: " . ($telefono ?: 'No especificado') . "
";
$cuerpo_email .= "Empresa: " . ($empresa ?: 'No especificada') . "

";
$cuerpo_email .= "Mensaje:
" . $mensaje . "

";
$cuerpo_email .= "--------------------------------------------------
";
$cuerpo_email .= "Administrado por Altimeshoper SpA — Holding Altime
";

$headers  = "From: no-reply@guemchonaltime.com
";
$headers .= "Reply-To: " . $email . "
";
$headers .= "X-Mailer: PHP/" . phpversion();

$enviado_mail = @mail($destinatario, $asunto, $cuerpo_email, $headers);

/* --------------------------------------------------------------------------
   5. RESPUESTA FINAL AL CLIENTE
   -------------------------------------------------------------------------- */
http_response_code(200);
echo json_encode([
    'status'   => 'success',
    'message'  => '¡Gracias por contactarnos! Tu mensaje ha sido recibido con éxito y nos pondremos en contacto a la brevedad.',
    'data'     => [
        'registrado_bd' => $guardado_db,
        'email_enviado' => $enviado_mail
    ]
]);
?>
