<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reabastecimiento</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            background: url('assets/images/Logistica.jpg') no-repeat center center;
            background-size: contain;
            background-attachment: fixed;

        }

        /* Capa de oscurecimiento sobre la imagen de fondo */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

       .login-container {
    position: relative;
    z-index: 2;
    width: 90%;
    max-width: 400px;
    padding: 30px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    margin-right: 10%;
}

/* Diseño responsivo para pantallas pequeñas */
@media screen and (max-width: 768px) {
    body {
        justify-content: center;
        padding: 20px;
    }

    .login-container {
        margin-right: 0;
    }
}
        .login-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .login-container label {
            display: block;
            margin: 10px 0 5px;
            color: #555;
        }

        .login-container input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #212529;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 15px;
        }

        .login-container button:hover {
            background-color:  #424649;
        }

        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #424649;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }

           

    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php
        if (isset($_GET['error'])) {
            if ($_GET['error'] === '0') {
                echo '<div class="error">El usuario está inactivo. Contacte al administrador.</div>';
            } else {
                echo '<div class="error">Usuario o contraseña incorrectos.</div>';
            }
        }
        ?>
        <form action="authenticate.php" method="POST">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Ingresar</button>
        </form>
        
        <footer style="text-align: center; font-size: 0.9em; color: #555; margin-top: 20px;">
            <p>© Copyright 2025|Corporación Colombiana de Logística</p>
            <p>Todos los derechos reservados.</p>
            <p>Versión 0.1.0</p>
        </footer>

    </div>
</body>
</html>
