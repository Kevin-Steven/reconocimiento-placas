<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirigir al usuario a la página de inicio de sesión si no está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Cabeceras para evitar el almacenamiento en caché
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

$nombres = isset($_SESSION['nombres']) ? $_SESSION['nombres'] : 'Usuario';
$apellidos = isset($_SESSION['apellidos']) ? $_SESSION['apellidos'] : '';
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'user';
$mensaje_acceso = isset($_SESSION['mensaje_acceso']) ? $_SESSION['mensaje_acceso'] : '';
unset($_SESSION['mensaje_acceso']);
$error_acceso = isset($_SESSION['error_acceso']) ? $_SESSION['error_acceso'] : '';
unset($_SESSION['error_acceso']);
$mensaje_placa = isset($_SESSION['mensaje_placa']) ? $_SESSION['mensaje_placa'] : '';
unset($_SESSION['mensaje_placa']);
$error_placa = isset($_SESSION['error_placa']) ? $_SESSION['error_placa'] : '';
unset($_SESSION['error_placa']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Acceso Vehicular</title>
    <link rel="stylesheet" href="../css/inicio.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="/Landing-Page/images/check.png" type="image/png">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuIcon = document.querySelector('.menu-icon');
            const navbar = document.querySelector('.navbar');
            const navLinks = document.querySelectorAll('.navbar a');
            const logoutLink = document.querySelector('a[href="logic/logout.php"]');
            const modal = document.getElementById('logoutModal');
            const closeModal = document.getElementById('closeModal');
            const confirmLogout = document.getElementById('confirmLogout');

            menuIcon.addEventListener('click', () => {
                navbar.classList.toggle('active');
            });

            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navbar.classList.remove('active');
                });
            });

            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                modal.style.display = 'flex';
            });

            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            confirmLogout.addEventListener('click', () => {
                window.location.href = 'logic/logout.php';
            });

            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>


</head>
<body class="pag-principal">
    <!-- Encabezado -->
    <header class="header">
        <a href="#" class="logo"><img src="/Landing-Page/images/check.png" alt="Logo">AutoAccess</a>
        <i class='bx bx-menu menu-icon'></i>
        <nav class="navbar">
            <a href="#inicio">Inicio</a>
            <a href="#lista">Lista de Vehículos</a>
            <?php if ($rol === 'admin') { ?>
                <a href="#generar-estadisticas">Generar Estadísticas</a>
                <a href="#agregar-placa">Agregar placa</a>
                <a href="#admin-form">Acceso manual</a>
            <?php } ?>
            <a href="logic/logout.php">Cerrar Sesión</a>
        </nav>
    </header>

    <section class="inicio" id="inicio">
        <div class="inicio-content">
            <h1>Gestión de <br>Acceso Vehicular</h1>
            <div class="text-secund">
                <h3>Automatización y Seguridad en Tiempo Real</h3>
            </div>
            <p>
                Bienvenido <strong><?php echo htmlspecialchars($nombres . ' ' . $apellidos); ?></strong> a nuestra plataforma dedicada a la gestión automatizada de 
                acceso vehicular mediante el reconocimiento de placas. Nuestro sistema 
                asegura un control de acceso eficiente y seguro, facilitando el ingreso 
                de vehículos autorizados y manteniendo un registro detallado de cada 
                acceso.
            </p>
        </div>
    </section>
    
    <!-- Sección Lista de Vehículos -->
    <section class="lista" id="lista">
        <h2 class="heading">Lista de <span>Vehículos</span></h2>
        
        <!-- Formulario de filtrado -->
        <form class="filtros" method="GET" action="#lista">
            <label for="placa">Filtrar por Placa:</label>
            <input type="text" id="placa" name="placa" placeholder="Ingrese la placa" value="<?php echo isset($_GET['placa']) ? htmlspecialchars($_GET['placa']) : ''; ?>">
            
            <label for="fecha">Filtrar por Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="<?php echo isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : ''; ?>">
            
            <button type="submit">Filtrar</button>
        </form>

        <div class="tabla">
            <div class="tabla-container">
                <!-- Aquí se incluye el contenido de mostrar_placas.php -->
                <?php include 'logic/mostrar_placas.php'; ?>
            </div>
        </div>

         <!-- Mostrar el botón de Generar Reporte solo si el usuario es admin -->
         <?php if ($rol === 'admin'): ?>
            <form method="GET" action="generar_reporte.php" target="_blank" class="busqueda">
                <!-- Campos ocultos para pasar parámetros adicionales -->
                <input type="hidden" id="hidden_placa" name="hidden_placa" value="<?php echo isset($_GET['placa']) ? htmlspecialchars($_GET['placa']) : ''; ?>">
                <input type="hidden" id="hidden_fecha" name="hidden_fecha" value="<?php echo isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : ''; ?>">

                <!-- Selector de intervalo -->
                <div class="input-box">
                    <label for="reporte_intervalo">Seleccione el intervalo:</label>
                    <select id="reporte_intervalo" name="reporte_intervalo" required onchange="updateReportDateInput()">
                        <option value="fecha">Día específico</option>
                        <option value="mes">Mes</option>
                    </select>
                </div>

                <!-- Entrada para fecha específica -->
                <div class="input-box" id="reporte_fecha-box">
                    <label for="reporte_fecha">Seleccione la fecha:</label>
                    <input type="date" id="reporte_fecha" name="reporte_fecha">
                </div>

                <!-- Entrada para mes -->
                <div class="input-box" id="reporte_mes-box" style="display:none;">
                    <label for="reporte_mes">Seleccione el mes:</label>
                    <input type="month" id="reporte_mes" name="reporte_mes">
                </div>

                <!-- Botón para generar el reporte -->
                <button type="submit" name="action" value="generar_reporte">Generar Reporte</button>
            </form>

            <script>
                function updateReportDateInput() {
                    var intervalo = document.getElementById('reporte_intervalo').value;
                    document.getElementById('reporte_fecha-box').style.display = intervalo === 'fecha' ? 'block' : 'none';
                    document.getElementById('reporte_mes-box').style.display = intervalo === 'mes' ? 'block' : 'none';
                }
            </script>
        <?php endif;?>
    </section>

    <!-- Sección Generar Estadísticas para Admin -->
    <?php if ($rol === 'admin') { ?>
        <section class="generar-estadisticas agregar-placa" id="generar-estadisticas">
            <h2 class="heading">Generar <span>Estadísticas</span></h2>

            <form method="POST" action="ver_estadisticas.php" target="_blank">
                <div class="input-box">
                    <label for="placa">Ingrese la placa (opcional):</label>
                    <input type="text" id="placa" name="placa" maxlength="7" placeholder="Ingrese la placa">
                </div>
                <div class="input-box">
                    <label for="intervalo">Seleccione el intervalo:</label>
                    <select id="intervalo" name="intervalo" required onchange="updateDateInput()">
                        <option value="fecha">Día específico</option>
                        <option value="mes">Mes</option>
                    </select>
                </div>
                <div class="input-box" id="fecha-box">
                    <label for="fecha">Seleccione la fecha:</label>
                    <input type="date" id="fecha" name="fecha">
                </div>
                <div class="input-box" id="mes-box" style="display:none;">
                    <label for="mes">Seleccione el mes:</label>
                    <input type="month" id="mes" name="mes">
                </div>
                <button type="submit">Generar Estadísticas</button>
            </form>
        </section>
        <script>
            function updateDateInput() {
                var intervalo = document.getElementById('intervalo').value;
                document.getElementById('fecha-box').style.display = intervalo === 'fecha' ? 'block' : 'none';
                document.getElementById('mes-box').style.display = intervalo === 'mes' ? 'block' : 'none';
            }
        </script>
    <?php } ?>


    <?php if ($rol === 'admin') { ?>
    <section class="agregar-placa" id="agregar-placa">
        <h2 class="heading">Agregar <span>Placa</span></h2>
        
        <form method="POST" action="logic/agregar_placa.php">
            
            <div class="input-box">
                <label for="propietario">Propietario:</label>
                <input type="text" id="propietario" name="propietario" required placeholder="Ingrese el nombre" >
            </div>

            <div class="input-box">
                <label for="marca">Marca:</label>
                <input type="text" id="marca" name="marca" required placeholder="Ingrese la marca del vehiculo" >
            </div>

            <div class="input-box">
                <label for="modelo">Modelo:</label>
                <input type="text" id="modelo" name="modelo" required placeholder="Ingrese el modelo del vehiculo" >
            </div>

            <div class="input-box">
                <label for="placas">Ingrese la placa:</label>
                <input type="text" id="placas" name="placas" maxlength="7" minlength="6" required placeholder="Ingrese la placa" >
            </div>
            <button type="submit">Agregar Placa</button>
            <?php if (!empty($mensaje_placa)) { ?>
                <div class="mensaje"><?php echo htmlspecialchars($mensaje_placa); ?></div>
            <?php } ?>
            <?php if (!empty($error_placa)) { ?>
                <div class="error"><?php echo htmlspecialchars($error_placa); ?></div>
            <?php } ?>
        </form>
    </section>
    
    <?php } ?>


    <?php if ($rol === 'admin') { ?>
        <section class="admin-form" id="admin-form">
            <h2 class="heading">Administrar <span>Acceso Vehicular</span></h2>
            
            <form method="POST" action="logic/agregar_acceso.php">
                <div class="input-box">
                    <label for="placa">Seleccione la Placa:</label>
                    <select id="placa" name="placa">
                        <!-- Opciones de placas, deben ser llenadas dinámicamente desde la base de datos -->
                        <?php
                        include 'config/config.php'; // Incluir el archivo de configuración de la base de datos

                        $query = "SELECT id, placa FROM placas";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['placa']) . '</option>';
                        }
                        $conn->close();
                        ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="fecha_ingreso">Fecha de Ingreso:</label>
                    <input type="date" id="fecha_ingreso" name="fecha_ingreso">
                </div>

                <div class="input-box">
                    <label for="hora_ingreso">Hora de Ingreso:</label>
                    <input type="time" id="hora_ingreso" name="hora_ingreso">
                </div>

                <div class="input-box">
                    <label for="fecha_salida">Fecha de Salida (opcional):</label>
                    <input type="date" id="fecha_salida" name="fecha_salida">
                </div>

                <div class="input-box">
                    <label for="hora_salida">Hora de Salida (opcional):</label>
                    <input type="time" id="hora_salida" name="hora_salida">
                </div>

                <button type="submit">Enviar</button>
            </form>

            <?php if (!empty($mensaje_acceso)) { ?>
                <div class="mensaje"><?php echo htmlspecialchars($mensaje_acceso); ?></div>
            <?php } ?>
            <?php if (!empty($error_acceso)) { ?>
                <div class="error"><?php echo htmlspecialchars($error_acceso); ?></div>
            <?php } ?>
        </section>
    <?php } ?>


    <!-- Ventana Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h2>¿Estás seguro de que deseas cerrar sesión?</h2>
            <button id="closeModal" class="btn-cancel">Cancelar</button>
            <button id="confirmLogout" class="btn-confirm">Cerrar Sesión</button>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-text">
            <p>&copy; 2024 - 5to A TDS | Todos los derechos reservados.</p>
        </div>
        <div class="footer-iconoFlecha">
            <a href="#"><i class='bx bx-up-arrow-alt'></i></a>
        </div>
    </footer>
</body>
</html>
