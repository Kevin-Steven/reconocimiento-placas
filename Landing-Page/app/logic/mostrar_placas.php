<?php
require dirname(__DIR__) . '/config/config.php'; // Asegúrate de ajustar la ruta a tu archivo de configuración

// Obtener los parámetros de filtrado
$placa_filter = isset($_GET['placa']) ? $_GET['placa'] : '';
$fecha_filter = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Consulta base para obtener las placas y propietarios
$query_base = "
    SELECT p.placa, 
           p.propietario,
           i.fecha_ingreso AS fecha_ingreso, 
           (SELECT s.fecha_salida 
            FROM salidas s 
            WHERE s.ingreso_id = i.id 
            ORDER BY s.fecha_salida ASC 
            LIMIT 1) AS fecha_salida 
    FROM placas p
    LEFT JOIN ingresos i ON p.id = i.placa_id
    WHERE 1=1"; // 1=1 es para poder agregar condicionales fácilmente

// Agregar condicionales a la consulta
$params = [];
$types = '';

if ($fecha_filter) {
    $query_base .= " AND (DATE(i.fecha_ingreso) = ? OR DATE(
               (SELECT s.fecha_salida 
                FROM salidas s 
                WHERE s.ingreso_id = i.id 
                ORDER BY s.fecha_salida ASC 
                LIMIT 1)) = ?)";
    $params[] = $fecha_filter;
    $params[] = $fecha_filter;
    $types .= 'ss';
} else {
    $query_base .= " AND (DATE(i.fecha_ingreso) = CURDATE() OR DATE(
               (SELECT s.fecha_salida 
                FROM salidas s 
                WHERE s.ingreso_id = i.id 
                ORDER BY s.fecha_salida ASC 
                LIMIT 1)) = CURDATE())";
}

if ($placa_filter) {
    $query_base .= " AND p.placa LIKE ?";
    $params[] = '%' . $placa_filter . '%';
    $types .= 's';
}

$query_base .= " ORDER BY i.fecha_ingreso ASC";
$stmt = $conn->prepare($query_base);

// Si hay parámetros, vincularlos a la consulta
if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Almacenar los resultados en arrays
$records = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Mostrar los resultados en una tabla HTML
if (empty($records)) {
    echo 'No se encontraron registros para los criterios de búsqueda.';
} else {
    echo '<table>';
    echo '<tr><th>Placa</th><th>Propietario</th><th>Fecha de Ingreso</th><th>Hora de Ingreso</th><th>Fecha de Salida</th><th>Hora de Salida</th></tr>';
    foreach ($records as $record) {
        echo '<tr>';
        echo '<td>' . $record['placa'] . '</td>';
        echo '<td>' . $record['propietario'] . '</td>';

        if ($record['fecha_ingreso']) {
            $fecha_ingreso = new DateTime($record['fecha_ingreso']);
            echo '<td>' . $fecha_ingreso->format('Y-m-d') . '</td>';
            echo '<td>' . $fecha_ingreso->format('H:i:s') . '</td>';
        } else {
            echo '<td>N/A</td>';
            echo '<td>N/A</td>';
        }

        if ($record['fecha_salida']) {
            $fecha_salida = new DateTime($record['fecha_salida']);
            echo '<td>' . $fecha_salida->format('Y-m-d') . '</td>';
            echo '<td>' . $fecha_salida->format('H:i:s') . '</td>';
        } else {
            echo '<td>N/A</td>';
            echo '<td>N/A</td>';
        }

        echo '</tr>';
    }
    echo '</table>';
}

$conn->close();

?>
