<?php
require('fpdf/fpdf.php');
require('config/config.php');

// Obtener parámetros de filtrado
$placa = isset($_GET['hidden_placa']) ? $conn->real_escape_string($_GET['hidden_placa']) : '';
$propietario = isset($_GET['propietario']) ? $conn->real_escape_string($_GET['propietario']) : '';
$fecha = isset($_GET['reporte_fecha']) ? $conn->real_escape_string($_GET['reporte_fecha']) : '';
$mes = isset($_GET['reporte_mes']) ? $conn->real_escape_string($_GET['reporte_mes']) : '';
$intervalo = isset($_GET['reporte_intervalo']) ? $conn->real_escape_string($_GET['reporte_intervalo']) : '';
$tipo_reporte = isset($_GET['tipo_reporte']) ? $conn->real_escape_string($_GET['tipo_reporte']) : 'general';
$action = isset($_GET['action']) ? $conn->real_escape_string($_GET['action']) : '';

// Construir la consulta SQL basada en los filtros
$sql = "SELECT p.placa, p.propietario, i.fecha_ingreso, MAX(s.fecha_salida) as fecha_salida
        FROM ingresos i
        LEFT JOIN salidas s ON i.id = s.ingreso_id
        LEFT JOIN placas p ON i.placa_id = p.id
        WHERE 1=1";

// Aplicar filtros
if ($placa) {
    $sql .= " AND p.placa = '$placa'";
}

if ($propietario) {
    $sql .= " AND p.propietario = '$propietario'";
}

if ($intervalo === 'fecha' && $fecha) {
    $sql .= " AND DATE(i.fecha_ingreso) = '$fecha'";
} elseif ($intervalo === 'mes' && $mes) {
    $sql .= " AND DATE_FORMAT(i.fecha_ingreso, '%Y-%m') = '$mes'";
} elseif ($intervalo === 'semanal') {
    if (!$fecha) {
        $fecha_fin = date('Y-m-d'); // Fecha actual
        $fecha_inicio = date('Y-m-d', strtotime('-7 days', strtotime($fecha_fin))); // 7 días antes de la fecha actual
        $sql .= " AND DATE(i.fecha_ingreso) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}

if ($tipo_reporte === 'hoy') {
    $sql .= " AND DATE(i.fecha_ingreso) = CURDATE()";
}

$sql .= " GROUP BY p.placa, i.fecha_ingreso
          ORDER BY i.fecha_ingreso";

// Ejecutar la consulta
$result = $conn->query($sql);

$registros = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }
}

// Cerrar la conexión a la base de datos
$conn->close();

class PDF extends FPDF
{
    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $txt = utf8_decode($txt);
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    function Header()
    {
        $this->Image('../images/black_check.png', 16, 5, 15);
        $this->SetFont('Arial', 'B', 19);
        $this->SetXY(30, 1);
        $this->Cell(20, 30, 'AutoAccess', 25, 5, 'L');
        $this->SetFont('Arial', 'B', 12);
        $this->SetY(30);
        $this->Cell(0, 10, 'Reporte de Ingresos y Salidas de Vehiculos', 0, 1, 'C');
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(0, 190, 280);
        $this->SetX(15);
        $this->Cell(25, 10, 'Placa', 1, 0, 'C', true);
        $this->Cell(35, 10, 'Propietario', 1, 0, 'C', true);
        $this->Cell(31, 10, 'Fecha Ingreso', 1, 0, 'C', true);
        $this->Cell(28, 10, 'Hora Ingreso', 1, 0, 'C', true);
        $this->Cell(31, 10, 'Fecha Salida', 1, 0, 'C', true);
        $this->Cell(28, 10, 'Hora Salida', 1, 0, 'C', true);
        $this->Ln();
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }

    function LoadData($registros)
    {
        return $registros;
    }

    function BasicTable($data)
    {
        $this->SetFont('Arial', '', 10);
        foreach ($data as $row) {
            $this->SetX(15);
            $fecha_ingreso = new DateTime($row['fecha_ingreso']);
            $fecha_salida = $row['fecha_salida'] ? new DateTime($row['fecha_salida']) : null;

            $this->Cell(25, 10, $row['placa'], 1, 0, 'C');
            $this->Cell(35, 10, $row['propietario'], 1, 0, 'C');
            $this->Cell(31, 10, $fecha_ingreso->format('Y-m-d'), 1, 0, 'C');
            $this->Cell(28, 10, $fecha_ingreso->format('H:i:s'), 1, 0, 'C');
            $this->Cell(31, 10, $fecha_salida ? $fecha_salida->format('Y-m-d') : 'N/A', 1, 0, 'C');
            $this->Cell(28, 10, $fecha_salida ? $fecha_salida->format('H:i:s') : 'N/A', 1, 0, 'C');
            $this->Ln();
        }
    }
}

if ($action == 'generar_reporte') {
    $pdf = new PDF();
    $pdf->AddPage();
    $data = $pdf->LoadData($registros);
    $pdf->BasicTable($data);
    $pdf->Output();
}
?>
