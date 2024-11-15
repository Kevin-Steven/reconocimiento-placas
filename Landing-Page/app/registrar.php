<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrate</title>
    <link rel="stylesheet" href="/Landing-Page/css/login.css">
    <link rel="icon" href="/Landing-Page/images/check.png" type="image/png">
</head>
<body>

    <section>
        <div class="login-box">

            <!-- Formulario para registrarse -->
            <form id="register-form" action="/Landing-Page/app/logic/procesar_registro.php" method="POST">
                <h2>Registrarse</h2>
                <?php
                    // Mostrar mensaje de error si existe
                    if (isset($_SESSION['error'])) {
                        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
                        unset($_SESSION['error']); // Limpiar mensaje de error después de mostrarlo
                    }
                ?>

                <div class="input-box">
                    <span class="icon"><ion-icon name="person"></ion-icon></span>
                    <input type="text" id="nombres" name="nombres" required placeholder=" ">
                    <label for="nombres">Nombres</label>
                </div>

                <div class="input-box">
                    <span class="icon"><ion-icon name="person"></ion-icon></span>
                    <input type="text" id="apellidos" name="apellidos" required placeholder=" ">
                    <label for="apellidos">Apellidos</label>
                </div>
                
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="email" id="email" name="email" required placeholder=" ">
                    <label for="email">Correo electrónico</label>
                </div>

                <div class="input-box">
                    <span class="icon"><ion-icon name="lock"></ion-icon></span>
                    <input type="password" id="password" name="password" required placeholder=" ">
                    <label for="password">Contraseña</label>
                </div>

                <button type="submit">Registrarse</button>

                <div class="register-link">
                    <p>¿Ya tienes una cuenta? <a href="/Landing-Page/app/login.php" >Iniciar Sesión</a></p>
                </div>
            </form>        
        </div>
    </section>

    <script src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons.js"></script>
    
    
</body>
</html>
