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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $placa_id = $_POST['placa'];
    $fecha_ingreso = !empty($_POST['fecha_ingreso']) && !empty($_POST['hora_ingreso']) ? $_POST['fecha_ingreso'] . ' ' . $_POST['hora_ingreso'] : null;
    $fecha_salida = !empty($_POST['fecha_salida']) && !empty($_POST['hora_salida']) ? $_POST['fecha_salida'] . ' ' . $_POST['hora_salida'] : null;

    if ($fecha_ingreso || $fecha_salida) {
        try {
            // Iniciar una transacción
            $conn->begin_transaction();

            if ($fecha_ingreso) {
                // Verificar si ya existe un ingreso sin salida para esta placa
                $stmt = $conn->prepare("
                    SELECT i.id 
                    FROM ingresos i 
                    LEFT JOIN salidas s ON i.id = s.ingreso_id 
                    WHERE i.placa_id = ? 
                      AND s.id IS NULL
                ");
                $stmt->bind_param("i", $placa_id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 0) {
                    $stmt->close();
                    // Insertar nuevo ingreso
                    $stmt = $conn->prepare("INSERT INTO ingresos (placa_id, fecha_ingreso) VALUES (?, ?)");
                    $stmt->bind_param("is", $placa_id, $fecha_ingreso);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $_SESSION['error_acceso'] = "Ya existe un ingreso sin salida para esta placa.";
                    $stmt->close();
                    $conn->rollback();
                    header("Location: ../inicio.php#admin-form");
                    exit;
                }
            }

            if ($fecha_salida) {
                // Buscar el último ingreso sin salida para esta placa
                $stmt = $conn->prepare("
                    SELECT i.id 
                    FROM ingresos i 
                    LEFT JOIN salidas s ON i.id = s.ingreso_id 
                    WHERE i.placa_id = ? 
                      AND s.id IS NULL 
                    ORDER BY i.fecha_ingreso DESC 
                    LIMIT 1
                ");
                $stmt->bind_param("i", $placa_id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($ingreso_id);
                    $stmt->fetch();
                    $stmt->close();

                    // Insertar la salida correspondiente
                    $stmt = $conn->prepare("INSERT INTO salidas (ingreso_id, fecha_salida) VALUES (?, ?)");
                    $stmt->bind_param("is", $ingreso_id, $fecha_salida);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $_SESSION['error_acceso'] = "No se encontró un ingreso sin salida para esta placa.";
                    $conn->rollback();
                    header("Location: ../inicio.php#admin-form");
                    exit;
                }
            }

            // Confirmar la transacción
            $conn->commit();
            $_SESSION['mensaje_acceso'] = "Acceso registrado con éxito.";
        } catch (mysqli_sql_exception $e) {
            $conn->rollback(); // Revertir la transacción en caso de error
            $_SESSION['error_acceso'] = "Error al registrar el acceso. Intente nuevamente. " . $e->getMessage();
        } finally {
            $conn->close();
        }
    } else {
        $_SESSION['error_acceso'] = "Debe proporcionar al menos la fecha y hora de ingreso.";
    }

    header("Location: ../inicio.php#admin-form");
    exit;
} else {
    header("Location: ../inicio.php#admin-form");
    exit;
}
?>
