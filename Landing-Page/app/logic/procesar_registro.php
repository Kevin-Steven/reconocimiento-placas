<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require '../config/config.php'; // Incluir el archivo de configuración de la base de datos

// Verificar si los datos del formulario están enviados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash de la contraseña
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Preparar y ejecutar la consulta SQL
    $stmt = $conn->prepare("INSERT INTO registro (nombres, apellidos, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombres, $apellidos, $email, $hashed_password);

    try {
        $stmt->execute();
        // Registro exitoso, redirigir al usuario a la página de inicio de sesión
        header("Location: ../login.php");
    } catch (mysqli_sql_exception $e) {
        // Verificar si el error es por duplicación de correo
        if ($e->getCode() == 1062) {
            // Error de correo duplicado
            $_SESSION['error'] = "Correo electrónico en uso";
            header("Location: ../registrar.php");
        } else {
            // Otros errores
            $_SESSION['error'] = "Error en el registro. Intente nuevamente.";
            header("Location: ../registrar.php");
        }
    }

    // Cerrar la conexión y la declaración
    $stmt->close();
    $conn->close();
} else {
    header("Location: registrar.php");
    exit;
}
?>
