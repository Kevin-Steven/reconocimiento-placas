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
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$nombres        = isset($_SESSION['nombres']) ? $_SESSION['nombres'] : 'Usuario';
$apellidos      = isset($_SESSION['apellidos']) ? $_SESSION['apellidos'] : '';
$rol            = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'user';
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
    <link rel="icon" href="/Landing-Page/images/check.png" type="image/png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Tailwind CSS v4 (browser) -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="min-h-screen bg-black text-white antialiased scroll-smooth">
    <!-- Fondo mesh gradient global -->
    <div class="fixed inset-0 -z-10 bg-black overflow-hidden">
        <!-- Elipse púrpura superior -->
        <div class="pointer-events-none absolute -top-40 left-1/2 h-[420px] w-[420px] -translate-x-1/2 rounded-full bg-purple-700/20 blur-[120px]"></div>
        <!-- Elipse azul lateral izquierda -->
        <div class="pointer-events-none absolute inset-y-0 -left-40 h-[420px] w-[420px] rounded-full bg-blue-700/20 blur-[120px]"></div>
        <!-- Elipse azul lateral derecha -->
        <div class="pointer-events-none absolute inset-y-0 -right-40 h-[420px] w-[420px] rounded-full bg-blue-500/20 blur-[120px]"></div>
        <!-- Sutil gradiente radial oscuro para profundidad -->
        <div class="absolute inset-0 bg-radial from-white/5 via-black to-black"></div>
    </div>

    <!-- Layout principal -->
    <div class="relative flex min-h-screen flex-col">
        <!-- Navbar -->
        <header class="sticky top-0 z-40 border-b border-white/10 bg-black/70 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 md:py-4">
                <!-- Logo -->
                <a href="#inicio" class="flex items-center gap-2 text-sm font-semibold tracking-tight">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/5 ring-1 ring-white/10">
                        <img src="/Landing-Page/images/check.png" alt="Logo" class="h-6 w-6 object-contain">
                    </div>
                    <span class="text-base font-semibold text-white">AutoAccess</span>
                </a>

                <!-- Links desktop -->
                <nav class="hidden items-center gap-8 text-xs text-gray-400 md:flex">
                    <a href="#inicio" class="transition hover:text-white">Inicio</a>
                    <a href="#lista" class="transition hover:text-white">Lista de Vehículos</a>
                    <?php if ($rol === 'admin') { ?>
                        <a href="#generar-estadisticas" class="transition hover:text-white">Generar Estadísticas</a>
                        <a href="#agregar-placa" class="transition hover:text-white">Agregar placa</a>
                        <a href="#admin-form" class="transition hover:text-white">Acceso manual</a>
                    <?php } ?>
                </nav>

                <!-- CTA + menú -->
                <div class="flex items-center gap-3">
                    <a
                        href="logic/logout.php"
                        class="hidden items-center gap-2 rounded-full border border-white/20 bg-white/5 px-4 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-white/10 md:inline-flex"
                        id="logoutLink">
                        <span>Cerrar sesión</span>
                        <i class='bx bx-log-out text-sm'></i>
                    </a>
                    <!-- Icono menú móvil -->
                    <button class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 p-2 text-xl text-white md:hidden" id="menuToggle" aria-label="Abrir menú">
                        <i class='bx bx-menu'></i>
                    </button>
                </div>
            </div>

            <!-- Menú móvil -->
            <nav id="mobileNav" class="hidden border-t border-white/10 bg-black/95 px-4 pb-4 pt-2 md:hidden">
                <div class="flex flex-col gap-3 text-sm text-gray-300">
                    <a href="#inicio" class="hover:text-white">Inicio</a>
                    <a href="#lista" class="hover:text-white">Lista de Vehículos</a>
                    <?php if ($rol === 'admin') { ?>
                        <a href="#generar-estadisticas" class="hover:text-white">Generar Estadísticas</a>
                        <a href="#agregar-placa" class="hover:text-white">Agregar placa</a>
                        <a href="#admin-form" class="hover:text-white">Acceso manual</a>
                    <?php } ?>
                    <button
                        class="mt-2 inline-flex items-center justify-center rounded-full border border-white/20 bg-white/5 px-4 py-2 text-xs font-medium text-white"
                        id="mobileLogoutButton">
                        Cerrar sesión
                    </button>
                </div>
            </nav>
        </header>

        <!-- Hero -->
        <section id="inicio" class="relative flex items-center justify-center px-4 pb-12 pt-20 md:pb-20 md:pt-28">
            <div class="mx-auto flex max-w-6xl flex-col items-center text-center">
                <!-- Badge superior -->
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs text-gray-300">
                    <span class="h-2 w-2 rounded-full bg-fuchsia-500 shadow-[0_0_12px_rgba(244,114,182,0.9)]"></span>
                    <span>Controlando accesos para más de 150+ vehículos autorizados</span>
                </div>

                <!-- H1 -->
                <h1 class="font-bold tracking-[-0.05em] text-white drop-shadow-[0_0_40px_rgba(255,255,255,0.12)] leading-[0.9] text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-[110px]">
                    Control de acceso
                    <br class="hidden sm:block">
                    <span class="bg-gradient-to-r from-zinc-100 via-zinc-300 to-zinc-100 bg-clip-text text-transparent">
                        vehicular inteligente
                    </span>
                </h1>

                <!-- Subtítulo -->
                <p class="mt-6 max-w-2xl text-balance text-base text-gray-400 sm:text-lg">
                    Bienvenido,
                    <strong class="font-semibold text-gray-100">
                        <?php echo htmlspecialchars($nombres . ' ' . $apellidos); ?>
                    </strong>.
                    Gestiona accesos vehiculares y reportes en tiempo real.
                </p>

                <!-- CTAs -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a
                        href="#lista"
                        class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3 text-sm font-semibold text-black shadow-lg shadow-white/20 transition hover:bg-gray-100">
                        Ver accesos en tiempo real
                    </a>
                    <a
                        href="#generar-estadisticas"
                        class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-7 py-3 text-sm font-medium text-white backdrop-blur-md transition hover:bg-white/10">
                        Ver estadísticas y reportes
                    </a>
                </div>

                <!-- Social / Contact -->
                <div class="mt-10 flex flex-col items-center gap-4">
                    <span class="text-xs font-medium uppercase tracking-[0.2em] text-gray-500">
                        Conecta con el desarrollador
                    </span>
                    <div class="flex flex-wrap items-center justify-center gap-4 text-2xl text-white/60">
                        <!-- GitHub -->
                        <a
                            href="https://github.com/TU_USUARIO"
                            target="_blank"
                            class="flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/5 transition hover:bg-white/10 hover:text-white"
                            aria-label="GitHub">
                            <i class='bx bxl-github'></i>
                        </a>
                        <!-- LinkedIn -->
                        <a
                            href="https://www.linkedin.com/in/TU_PERFIL"
                            target="_blank"
                            class="flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/5 transition hover:bg-white/10 hover:text-white"
                            aria-label="LinkedIn">
                            <i class='bx bxl-linkedin-square'></i>
                        </a>
                        <!-- Gmail (mailto) -->
                        <a
                            href="mailto:tu_correo@gmail.com"
                            class="flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/5 transition hover:bg-white/10 hover:text-white"
                            aria-label="Gmail">
                            <i class='bx bxl-gmail'></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Lista de vehículos -->
        <section id="lista" class="border-t border-white/10 bg-black/80 px-4 py-12 md:py-16">
            <div class="mx-auto max-w-6xl">
                <!-- Header de sección -->
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-muted">
                            Monitoreo de accesos
                        </p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight md:text-3xl">
                            Lista de
                            <span class="bg-gradient-to-r text-white to-teal-300 bg-clip-text text-transparent">
                                vehículos
                            </span>
                        </h2>
                        <p class="mt-1 text-sm text-gray-400">
                            Consulta los registros de entrada y salida filtrando por placa o fecha.
                        </p>
                    </div>

                    <!-- Resumen rápido -->
                    <div class="flex gap-3 text-xs text-gray-400">
                        <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                            <span class="block text-[10px] uppercase tracking-[0.18em] text-gray-500">Filtros activos</span>
                            <span>
                                <?php echo isset($_GET['placa']) && $_GET['placa'] !== '' ? 'Placa: ' . htmlspecialchars($_GET['placa']) : 'Placa: todas'; ?>
                                ·
                                <?php echo isset($_GET['fecha']) && $_GET['fecha'] !== '' ? 'Fecha: ' . htmlspecialchars($_GET['fecha']) : 'Fechas: todas'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <form
                    class="mt-6 flex flex-wrap items-end gap-4 rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 via-white/[0.03] to-transparent p-4 text-sm shadow-[0_0_40px_rgba(0,0,0,0.6)]"
                    method="GET"
                    action="#lista">
                    <div class="flex flex-col gap-1">
                        <label for="placa" class="text-xs font-medium text-gray-300">
                            Placa
                        </label>
                        <div class="relative">
                            <i class='bx bx-car absolute left-2 top-1/2 -translate-y-1/2 text-xs text-gray-500'></i>
                            <input
                                type="text"
                                id="placa"
                                name="placa"
                                placeholder="Ej: ABC1234"
                                value="<?php echo isset($_GET['placa']) ? htmlspecialchars($_GET['placa']) : ''; ?>"
                                class="h-10 w-44 rounded-lg border border-white/10 bg-black/60 pl-7 pr-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="fecha" class="text-xs font-medium text-gray-300">
                            Fecha
                        </label>
                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            value="<?php echo isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : ''; ?>"
                            class="h-10 w-44 rounded-lg border border-white/10 bg-black/60 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-emerald-400">
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-blue-500 px-5 py-2.5 text-xs text-white font-semibold text-black shadow-sm shadow-sky-500/40 transition hover:bg-sky-400">
                            <i class='bx bx-filter-alt mr-2 text-sm'></i>
                            Aplicar filtros
                        </button>

                        <?php if (!empty($_GET['placa']) || !empty($_GET['fecha'])): ?>
                            <a
                                href="#lista"
                                class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-xs font-medium text-gray-200 transition hover:bg-white/10">
                                <i class='bx bx-x mr-1 text-sm'></i>
                                Limpiar filtros
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Tabla -->
                <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-black/70 shadow-xl">
                    <div class="max-h-[420px] overflow-auto">
                        <?php include 'logic/mostrar_placas.php'; ?>
                    </div>
                </div>

                <!-- Acciones de reporte (solo admin) -->
                <?php if ($rol === 'admin'): ?>
                    <div class="mt-8 flex flex-col gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4 text-sm">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-300">
                                    <i class='bx bx-line-chart text-lg'></i>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-emerald-300">
                                        Herramientas para administradores
                                    </p>
                                    <p class="text-xs text-emerald-100/70">
                                        Genera reportes PDF por día o mes usando los filtros actuales.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form
                            method="GET"
                            action="generar_reporte.php"
                            target="_blank"
                            class="mt-2 flex flex-wrap items-end gap-3 text-xs">
                            <input
                                type="hidden"
                                id="hidden_placa"
                                name="hidden_placa"
                                value="<?php echo isset($_GET['placa']) ? htmlspecialchars($_GET['placa']) : ''; ?>">
                            <input
                                type="hidden"
                                id="hidden_fecha"
                                name="hidden_fecha"
                                value="<?php echo isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : ''; ?>">

                            <div class="flex flex-col gap-1">
                                <label for="reporte_intervalo" class="text-[11px] font-medium text-emerald-100">
                                    Intervalo
                                </label>
                                <select
                                    id="reporte_intervalo"
                                    name="reporte_intervalo"
                                    required
                                    onchange="updateReportDateInput()"
                                    class="h-9 rounded-lg border border-emerald-500/40 bg-black/70 px-3 text-[11px] text-emerald-50 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                                    <option value="fecha">Día específico</option>
                                    <option value="mes">Mes</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1" id="reporte_fecha-box">
                                <label for="reporte_fecha" class="text-[11px] font-medium text-emerald-100">
                                    Fecha
                                </label>
                                <input
                                    type="date"
                                    id="reporte_fecha"
                                    name="reporte_fecha"
                                    class="h-9 rounded-lg border border-emerald-500/40 bg-black/70 px-3 text-[11px] text-emerald-50 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                            </div>

                            <div class="hidden flex-col gap-1" id="reporte_mes-box">
                                <label for="reporte_mes" class="text-[11px] font-medium text-emerald-100">
                                    Mes
                                </label>
                                <input
                                    type="month"
                                    id="reporte_mes"
                                    name="reporte_mes"
                                    class="h-9 rounded-lg border border-emerald-500/40 bg-black/70 px-3 text-[11px] text-emerald-50 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                            </div>

                            <button
                                type="submit"
                                name="action"
                                value="generar_reporte"
                                class="inline-flex items-center justify-center rounded-full bg-emerald-500 px-5 py-2 text-[11px] font-semibold text-black shadow-sm shadow-emerald-500/40 transition hover:bg-emerald-400">
                                <i class='bx bx-file mr-2 text-sm'></i>
                                Generar reporte
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Generar estadísticas (admin) -->
        <?php if ($rol === 'admin') { ?>
            <section id="generar-estadisticas" class="border-t border-white/10 bg-black/80 px-4 py-12 md:py-16">
                <div class="mx-auto max-w-4xl">
                    <h2 class="text-center text-2xl font-semibold tracking-tight md:text-3xl">
                        Generar <span class="bg-gradient-to-r from-blue-400 to-sky-300 bg-clip-text text-transparent">Estadísticas</span>
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-400">
                        Analiza el comportamiento de accesos por día o por mes, filtrando opcionalmente por placa.
                    </p>

                    <form method="POST" action="ver_estadisticas.php" target="_blank" class="mt-8 flex flex-col gap-4 rounded-2xl border border-white/10 bg-white/5 p-6 text-sm">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="placa" class="text-xs font-medium text-gray-300">Placa (opcional)</label>
                                <input
                                    type="text"
                                    id="placa"
                                    name="placa"
                                    maxlength="7"
                                    placeholder="Ingrese la placa"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="intervalo" class="text-xs font-medium text-gray-300">Seleccione el intervalo</label>
                                <select
                                    id="intervalo"
                                    name="intervalo"
                                    required
                                    onchange="updateDateInput()"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-xs text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option value="fecha">Día específico</option>
                                    <option value="mes">Mes</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1" id="fecha-box">
                                <label for="fecha" class="text-xs font-medium text-gray-300">Seleccione la fecha</label>
                                <input
                                    type="date"
                                    id="fecha"
                                    name="fecha"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div class="hidden flex-col gap-1" id="mes-box">
                                <label for="mes" class="text-xs font-medium text-gray-300">Seleccione el mes</label>
                                <input
                                    type="month"
                                    id="mes"
                                    name="mes"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-2 inline-flex w-full items-center justify-center rounded-full bg-blue-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-400">
                            Generar estadísticas
                        </button>
                    </form>
                </div>
            </section>
        <?php } ?>

        <!-- Agregar placa (admin) -->
        <?php if ($rol === 'admin') { ?>
            <section id="agregar-placa" class="border-t border-white/10 bg-black/80 px-4 py-12 md:py-16">
                <div class="mx-auto max-w-4xl">
                    <h2 class="text-center text-2xl font-semibold tracking-tight md:text-3xl">
                        Agregar <span class="bg-gradient-to-r from-blue-400 to-sky-300 bg-clip-text text-transparent">Placa</span>
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-400">
                        Registra un nuevo vehículo autorizado en el sistema.
                    </p>

                    <form method="POST" action="logic/agregar_placa.php" class="mt-8 flex flex-col gap-4 rounded-2xl border border-white/10 bg-white/5 p-6 text-sm">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="propietario" class="text-xs font-medium text-gray-300">Propietario</label>
                                <input
                                    type="text"
                                    id="propietario"
                                    name="propietario"
                                    required
                                    placeholder="Ingrese el nombre"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="marca" class="text-xs font-medium text-gray-300">Marca</label>
                                <input
                                    type="text"
                                    id="marca"
                                    name="marca"
                                    required
                                    placeholder="Ingrese la marca del vehículo"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="modelo" class="text-xs font-medium text-gray-300">Modelo</label>
                                <input
                                    type="text"
                                    id="modelo"
                                    name="modelo"
                                    required
                                    placeholder="Ingrese el modelo del vehículo"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="placas" class="text-xs font-medium text-gray-300">Placa</label>
                                <input
                                    type="text"
                                    id="placas"
                                    name="placas"
                                    maxlength="7"
                                    minlength="6"
                                    required
                                    placeholder="Ingrese la placa"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-2 inline-flex w-full items-center justify-center rounded-full bg-blue-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-400">
                            Agregar placa
                        </button>

                        <?php if (!empty($mensaje_placa)) { ?>
                            <div class="mt-2 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs font-medium text-emerald-300">
                                <?php echo htmlspecialchars($mensaje_placa); ?>
                            </div>
                        <?php } ?>
                        <?php if (!empty($error_placa)) { ?>
                            <div class="mt-2 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-xs font-medium text-red-300">
                                <?php echo htmlspecialchars($error_placa); ?>
                            </div>
                        <?php } ?>
                    </form>
                </div>
            </section>
        <?php } ?>

        <!-- Admin form (Acceso manual) -->
        <?php if ($rol === 'admin') { ?>
            <section id="admin-form" class="border-t border-white/10 bg-black/80 px-4 py-12 md:py-16">
                <div class="mx-auto max-w-4xl">
                    <h2 class="text-center text-2xl font-semibold tracking-tight md:text-3xl">
                        Administrar <span class="bg-gradient-to-r from-blue-400 to-sky-300 bg-clip-text text-transparent">Acceso Vehicular</span>
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-400">
                        Registra accesos manuales ajustando fechas y horas de ingreso y salida.
                    </p>

                    <form method="POST" action="logic/agregar_acceso.php" class="mt-8 flex flex-col gap-4 rounded-2xl border border-white/10 bg-white/5 p-6 text-sm">
                        <div class="flex flex-col gap-1">
                            <label for="placa" class="text-xs font-medium text-gray-300">Seleccione la placa</label>
                            <select
                                id="placa"
                                name="placa"
                                class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <?php
                                include 'config/config.php';
                                $query  = "SELECT id, placa FROM placas";
                                $result = $conn->query($query);
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['placa']) . '</option>';
                                }
                                $conn->close();
                                ?>
                            </select>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="fecha_ingreso" class="text-xs font-medium text-gray-300">Fecha de ingreso</label>
                                <input
                                    type="date"
                                    id="fecha_ingreso"
                                    name="fecha_ingreso"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="hora_ingreso" class="text-xs font-medium text-gray-300">Hora de ingreso</label>
                                <input
                                    type="time"
                                    id="hora_ingreso"
                                    name="hora_ingreso"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="fecha_salida" class="text-xs font-medium text-gray-300">Fecha de salida (opcional)</label>
                                <input
                                    type="date"
                                    id="fecha_salida"
                                    name="fecha_salida"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="hora_salida" class="text-xs font-medium text-gray-300">Hora de salida (opcional)</label>
                                <input
                                    type="time"
                                    id="hora_salida"
                                    name="hora_salida"
                                    class="h-10 rounded-lg border border-white/10 bg-black/70 px-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-2 inline-flex w-full items-center justify-center rounded-full bg-blue-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-400">
                            Registrar acceso
                        </button>

                        <?php if (!empty($mensaje_acceso)) { ?>
                            <div class="mt-2 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs font-medium text-emerald-300">
                                <?php echo htmlspecialchars($mensaje_acceso); ?>
                            </div>
                        <?php } ?>
                        <?php if (!empty($error_acceso)) { ?>
                            <div class="mt-2 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-xs font-medium text-red-300">
                                <?php echo htmlspecialchars($error_acceso); ?>
                            </div>
                        <?php } ?>
                    </form>
                </div>
            </section>
        <?php } ?>

        <!-- Modal logout -->
        <div
            id="logoutModal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-black/90 p-6 text-center shadow-2xl">
                <h2 class="text-lg font-semibold text-white">¿Estás seguro de que deseas cerrar sesión?</h2>
                <div class="mt-6 flex justify-center gap-3">
                    <button
                        id="closeModal"
                        class="inline-flex flex-1 items-center justify-center rounded-full border border-white/20 bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">
                        Cancelar
                    </button>
                    <button
                        id="confirmLogout"
                        class="inline-flex flex-1 items-center justify-center rounded-full bg-blue-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-400">
                        Cerrar sesión
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-white/10 bg-black/80 px-4 py-6">
            <div class="mx-auto flex max-w-6xl items-center justify-between text-xs text-gray-500">
                <p>&copy; 2024 - 5to A TDS | Todos los derechos reservados.</p>
                <a href="#inicio" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-lg text-white hover:bg-white/10">
                    <i class='bx bx-up-arrow-alt'></i>
                </a>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const mobileNav = document.getElementById('mobileNav');
            const logoutLink = document.getElementById('logoutLink');
            const mobileLogoutButton = document.getElementById('mobileLogoutButton');
            const modal = document.getElementById('logoutModal');
            const closeModal = document.getElementById('closeModal');
            const confirmLogout = document.getElementById('confirmLogout');

            if (menuToggle && mobileNav) {
                menuToggle.addEventListener('click', () => {
                    mobileNav.classList.toggle('hidden');
                });
            }

            function openLogoutModal(e) {
                if (e) e.preventDefault();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeLogoutModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            if (logoutLink) {
                logoutLink.addEventListener('click', openLogoutModal);
            }

            if (mobileLogoutButton) {
                mobileLogoutButton.addEventListener('click', openLogoutModal);
            }

            if (closeModal) {
                closeModal.addEventListener('click', closeLogoutModal);
            }

            if (confirmLogout) {
                confirmLogout.addEventListener('click', () => {
                    window.location.href = 'logic/logout.php';
                });
            }

            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeLogoutModal();
                }
            });
        });

        function updateReportDateInput() {
            var intervalo = document.getElementById('reporte_intervalo').value;
            const fechaBox = document.getElementById('reporte_fecha-box');
            const mesBox = document.getElementById('reporte_mes-box');

            if (intervalo === 'fecha') {
                fechaBox.classList.remove('hidden');
                mesBox.classList.add('hidden');
            } else {
                fechaBox.classList.add('hidden');
                mesBox.classList.remove('hidden');
            }
        }

        function updateDateInput() {
            var intervalo = document.getElementById('intervalo').value;
            const fechaBox = document.getElementById('fecha-box');
            const mesBox = document.getElementById('mes-box');

            if (intervalo === 'fecha') {
                fechaBox.classList.remove('hidden');
                mesBox.classList.add('hidden');
            } else {
                fechaBox.classList.add('hidden');
                mesBox.classList.remove('hidden');
            }
        }
    </script>
</body>

</html>