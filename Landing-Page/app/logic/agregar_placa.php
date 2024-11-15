<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require '../config/config.php'; // Incluir el archivo de configuración de la base de datos

// Verificar si el usuario es admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../inicio.php");
    exit;
}

// Verificar si los datos del formulario están enviados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $placa = $_POST['placas'];
    $propietario = $_POST['propietario'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    
    // Validar los datos ingresados
    if ($placa && strlen($placa) >= 6 && strlen($placa) <= 7) {
        $placa = strtoupper($placa); // Transformar a mayúsculas

        // Verificar si la placa ya existe en la base de datos
        $stmt = $conn->prepare("SELECT COUNT(*) FROM placas WHERE placa = ?");
        $stmt->bind_param("s", $placa);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $_SESSION['error_placa'] = "La placa ya existe en la base de datos.";
        } else {
            // Preparar y ejecutar la consulta SQL
            $stmt = $conn->prepare("INSERT INTO placas (placa, propietario, marca, modelo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $placa, $propietario, $marca, $modelo);

            try {
                $stmt->execute();
                // Registro exitoso, establecer mensaje de confirmación
                $_SESSION['mensaje_placa'] = "Placa agregada exitosamente.";
            } catch (mysqli_sql_exception $e) {
                $_SESSION['error_placa'] = "Error al agregar la placa. Intente nuevamente.";
            } finally {
                // Cerrar la conexión y la declaración
                $stmt->close();
                $conn->close();
            }
        }
    } else {
        $_SESSION['error_placa'] = "La placa debe tener entre 6 y 7 caracteres.";
    }
    header("Location: ../inicio.php#agregar-placa");
    exit;
} else {
    header("Location: ../inicio.php#agregar-placa");
    exit;
}
?>
