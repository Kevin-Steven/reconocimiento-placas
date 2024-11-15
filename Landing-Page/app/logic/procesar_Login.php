<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require '../config/config.php'; // Incluir el archivo de configuración de la base de datos

// Verificar si los datos del formulario están enviados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Preparar y ejecutar la consulta SQL para verificar el usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Verificar la contraseña
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Obtener nombres, apellidos y rol de la tabla registro
            $stmt = $conn->prepare("SELECT nombres, apellidos FROM registro WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                // Iniciar sesión
                $_SESSION['usuario'] = $email;
                $_SESSION['nombres'] = $user['nombres'];
                $_SESSION['apellidos'] = $user['apellidos'];
                $_SESSION['rol'] = $row['rol'];
                
                // Redirigir al usuario a la página principal
                header("Location: ../inicio.php");
                exit;
            } else {
                // Error al obtener nombres y apellidos
                $_SESSION['error'] = "Error al obtener la información del usuario";
                header("Location: ../login.php");
                exit;
            }
        } else {
            // Contraseña incorrecta
            $_SESSION['error'] = "Correo electrónico o contraseña no válidos";
            header("Location: ../login.php");
            exit;
        }
    } else {
        // El usuario no existe
        $_SESSION['error'] = "Correo electrónico o contraseña no válidos";
        header("Location: ../login.php");
        exit;
    }

    // Cerrar la conexión y la declaración
    $stmt->close();
    $conn->close();
} else {
    header("Location: login.php");
    exit;
}

?>
