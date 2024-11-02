<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Reabastecimiento</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
            margin: 0;
            position: relative; /* Para que el pseudo-elemento ::before se posicione relativo al body */
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/Logistica.jpg');
            background-size: cover;
            background-position: center;
            /*background-color: rgba(0, 0, 0, 0.5); /* Capa negra con opacidad */
            /*opacity: 0.7; /* Controla la opacidad de la imagen */
            z-index: -1; /* Coloca el pseudo-elemento detrás del contenido del body */
        }

        .login-container {
            position: relative;
            z-index: 1; /* Asegura que el contenido esté por encima de la capa */
            background-color: rgba(255, 255, 255, 0.9); /* Fondo blanco semitransparente */
            padding: 20px 30px;  
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }

        .login-container h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .login-container label {
            display: block;
            margin-top: 10px;
            color: #555;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 93%;
            padding: 8px 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .login-container button {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background-color: #5cb85c;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .login-container button:hover {
            background-color: #4cae4c;
        }

        .error {
            color: red;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="error">Usuario o contraseña incorrectos.</div>';
        }
        ?>
        <form action="authenticate.php" method="POST">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
