<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo??'Reabastecimiento';?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <style>
        .btn-back {
            display: inline-block;
            background-color: #1e3765;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-back:hover {
            background-color: #1e3765;
            transform: scale(1.05);
        }

        .form-upload {
            margin: 0px 0;
            /* Espaciado */
            border: 1px solid #ccc;
            /* Borde */
            padding: 1px;
            /* Espaciado interno */
            border-radius: 5px;
            /* Bordes redondeados */
            background-color: #f9f9f9;
            /* Fondo */
        }

        .total {
            font-weight: bold;
            margin-top: 20px;
        }

        /* Estilo para alinear el título y el formulario */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            /* Fondo blanco */
        }

        .header h1 {
            margin: 0;
        }

        /*.error-message {
            color: red;
            font-weight: bold;
            margin: 10px 0;
            padding: 10px;
            border: 2px solid red;
            background-color: #ffe6e6; /* Fondo claro para destacar el error */

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 98%;
            background-color: white;
            z-index: 1000;
            /* Asegúrate de que esté sobre otros elementos */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            /* Añadir sombra para destacar */
        }

        .total {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 3px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            /* Asegúrate de que esté sobre otros elementos */
        }

        .search-container {
            text-align: right;
        }

        .search-input {
            padding: 8px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 200px;
            margin-left: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            position: relative;
            top: 0;
            left: 0;
            width: 98%;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
    </style>
</head>

<body>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>

<?
    include '/header.php';
    include '/'.$content??'/notfound.php';
    include '/footer.php';
?>
</body>
</html>