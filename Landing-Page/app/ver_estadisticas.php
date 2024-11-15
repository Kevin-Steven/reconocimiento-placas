<?php
require 'config/config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: inicio.php");
    exit;
}

$placa = $_POST['placa'] ?? '';
$intervalo = $_POST['intervalo'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$mes = $_POST['mes'] ?? '';

if ($intervalo === 'fecha' && $fecha) {
    $fechaInicio = $fecha . ' 00:00:00';
    $fechaFin = $fecha . ' 23:59:59';
} elseif ($intervalo === 'mes' && $mes) {
    $fechaInicio = date('Y-m-01 00:00:00', strtotime($mes));
    $fechaFin = date('Y-m-t 23:59:59', strtotime($mes));
} else {
    echo json_encode(['error' => 'No se ha ingresado la placa o fecha.']);
    exit;
}

$query = "SELECT placas.placa, 'Ingreso' AS tipo, COUNT(*) AS total FROM ingresos JOIN placas ON ingresos.placa_id = placas.id WHERE fecha_ingreso BETWEEN ? AND ? ";
$params = [$fechaInicio, $fechaFin];
$types = 'ss';

if ($placa) {
    $query .= "AND placas.placa = ? ";
    $params[] = $placa;
    $types .= 's';
}

$query .= "GROUP BY placas.placa UNION ALL ";

$query .= "SELECT placas.placa, 'Salida' AS tipo, COUNT(*) AS total FROM salidas JOIN ingresos AS ingresos_salidas ON salidas.ingreso_id = ingresos_salidas.id JOIN placas ON ingresos_salidas.placa_id = placas.id WHERE fecha_salida BETWEEN ? AND ? ";
$params = array_merge($params, [$fechaInicio, $fechaFin]);
$types .= 'ss';

if ($placa) {
    $query .= "AND placas.placa = ? ";
    $params[] = $placa;
    $types .= 's';
}

$query .= "GROUP BY placas.placa, tipo ORDER BY placa, tipo";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($data[$row['placa']])) {
        $data[$row['placa']] = ['Ingreso' => 0, 'Salida' => 0];
    }
    $data[$row['placa']][$row['tipo']] = (int) $row['total'];
}

$stmt->close();
$conn->close();

$jsonData = json_encode($data);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Ingresos y Salidas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="/Landing-Page/images/check.png" type="image/png">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f4f5f7;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .chart-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            width: 90%;
            max-width: 900px;
            margin: 20px auto;
        }
        canvas {
            width: 100%;
            height: auto;
        }
        h1{
            text-align: center;
        }
    </style>
</head>
<body>
<h1>Estadísticas de Ingresos y Salidas</h1>
<div class="chart-container">
    <canvas id="chart"></canvas>
</div>
<script>
    const data = <?php echo $jsonData; ?>;
    if (data.error) {
        alert(data.error);
    } else if (Object.keys(data).length === 0) {
        alert('No hay datos disponibles para la placa y fecha seleccionadas.');
    } else {
        const ctx = document.getElementById('chart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'Ingresos',
                    data: Object.keys(data).map(key => data[key]['Ingreso']),
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }, {
                    label: 'Salidas',
                    data: Object.keys(data).map(key => data[key]['Salida']),
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        footerFont: { size: 10 },
                        padding: 10,
                        caretSize: 5,
                        displayColors: false,
                        borderColor: 'rgba(255, 255, 255, 0.5)',
                        borderWidth: 1,
                        borderRadius: 5,
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 20,
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
</script>
</body>
</html>

