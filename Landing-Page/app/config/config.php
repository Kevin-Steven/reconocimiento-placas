<?php
$host = "localhost"; 
$usuario = "root";
$contrasena = "root12345";
$base_datos = "placas_db";
$puerto = 3306; 

$conn = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);

if ($conn->connect_error) { 
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}else {
    //echo "Conexión exitosa";  // Mensaje de éxito
}

// Configuración de caracteres
$conn->set_charset("utf8");

?>
