<?php
// public/inventarioscontrolle.php

session_start();

// Habilitar la visualización de errores (solo para desarrollo; eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/inventarios.php';
require_once './sigware_api.php';

use App\Database;
use App\inventarios;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}


$database = new Database();
$cliente_id = $_SESSION['cliente_id']; // Obtener el cliente_id de la sesión
$inventariosObj = new inventarios($database, $cliente_id); // Pasar ambos argumentos

// Generar un token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener el término de búsqueda de la consulta GET
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Obtener y listar los inventarios de la base de datos, filtrando por el término de búsqueda
$inventarios = $inventariosObj->getAllItems($searchTerm); // Aquí necesitas modificar tu método para aceptar el término de búsqueda

$titulo = "Inventarios";
$seccion = "Administración";
// Contar el total de registros
$totalRegistros = count($inventarios); // Contar los elementos en el array de inventarios
include '../templates/header.php';  

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventarios</title>
    <link rel="icon" href="https://images.icon-icons.com/943/PNG/512/shoppaymentorderbuy-10_icon-icons.com_73874.png" type="image/png" sizes="32x32">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <style>
        .btn-back {
            display: inline-block;
            background-color: #1e3765            ;
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
            margin: 0px 0; /* Espaciado */
            border: 1px solid #ccc; /* Borde */
            padding: 1px; /* Espaciado interno */
            border-radius: 5px; /* Bordes redondeados */
            background-color: #f9f9f9; /* Fondo */
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
            background-color: white; /* Fondo blanco */
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
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Añadir sombra para destacar */
        }

        .total {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 3px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
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

<script>
    document.getElementById('file-upload').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
        document.getElementById('file-selected').textContent = fileName;
    });
</script>

<?php
// Mostrar un mensaje de error, si existe
if (isset($_SESSION['error_message'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']); // Limpiar el mensaje de error después de mostrarlo
}
?>

<div style="margin-left: 20px; margin-right: 20px">
    <div class="table-responsive">
    <div class="search-container">
        <button type="button" onclick="actualizar()" class="btn btn-dark btn-small">Actualizar de Sigware</button>
        </div>
        <table id="tablaInventarios" class="table table-striped table-hover dataTable display" style="font-size: 80%;">
            <thead>
                <tr>
                    <th style="text-align: center">Código</th>
                    <th style="text-align: center">LPN</th>
                    <th style="text-align: center">Localización</th>
                    <th style="text-align: center">Área Picking</th>
                    <th style="text-align: center">SKU</th>
                    <th style="text-align: center">SKU2</th>
                    <th style="text-align: center">Descripción</th>
                    <th style="text-align: center">Precio</th>
                    <th style="text-align: center">Tipo Material</th>
                    <th style="text-align: center">Categoría Material</th>
                    <th style="text-align: center">Unidades</th>
                    <th style="text-align: center">Cajas</th>
                    <th style="text-align: center">Reserva</th>
                    <th style="text-align: center">Disponible</th>
                    <th style="text-align: center">UDM</th>
                    <th style="text-align: center">Embalaje</th>
                    <th style="text-align: center">Fecha Entrada</th>
                    <th style="text-align: center">Estado</th>
                    <th style="text-align: center">Lote</th>
                    <th style="text-align: center">Fecha Fabricación</th>
                    <th style="text-align: center">Fecha Vencimiento</th>
                    <th style="text-align: center">FPC</th>
                    <th style="text-align: center">Peso</th>
                    <th style="text-align: center">Serial</th>
                    <!-- <th style="text-align: center">Cliente ID</th> -->
                    <!--th>Acciones</th-->
                </tr>
            </thead>
            
            <tbody>
                <?php foreach ($inventarios as $inventario): ?>
                    <tr>
                        <td style="text-align: center"><?php echo $inventario['codigo']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['lpn']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['localizacion']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['area_picking']?? ''; ?></td>
                        <td style="text-align: center"><?php echo $inventario['sku']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['sku2']?? ''; ?></td>
                        <td style="text-align: center"><?php echo $inventario['descripcion']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['precio'], 2; ?></td>
                        <td style="text-align: center"><?php echo $inventario['tipo_material']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['categoria_material']?? ''; ?></td>
                        <td style="text-align: center"><?php echo $inventario['unidades']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['cajas']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['reserva']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['disponible']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['udm']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['embalaje']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['fecha_entrada']?? ''; ?></td>
                        <td style="text-align: center"><?php echo $inventario['estado']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['lote']?? ''; ?></td>
                        <td style="text-align: center"><?php echo $inventario['fecha_fabricacion']?? ''; ?></td>
                        <td style="text-align: center"><?php echo $inventario['fecha_vencimiento']?? ''; ?></td>
                        <td style="text-align: center"><?php echo $inventario['fpc']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['peso']; ?></td>
                        <td style="text-align: center"><?php echo $inventario['serial']?? ''; ?></td>
                        <!-- <td style="text-align: center"><?php echo $inventario['cliente_id']; ?></td> -->
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterReports() {
        const searchInput = document.getElementById('search-input').value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(searchInput) ? '' : 'none';
        });
    }
    </script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

    function actualizar(){
        let timerInterval;
        Swal.fire({
            title: "Acualizando Inventario",
            html: "Esto puede tardar varios minutos, por favor espere...",
            timer: 1000 * 60 * 10,
            timerProgressBar: false,
            didOpen: () => {
                Swal.showLoading();
                const timer = Swal.getPopup().querySelector("b");
                    timerInterval = setInterval(() => {
                    timer.textContent = `${Swal.getTimerLeft()}`;
                }, 100);
            },
            willClose: () => {
                clearInterval(timerInterval);
            }
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.timer) {
                console.log("I was closed by the timer");
            }
        });

        $.ajax({
            url: '/Gesti-nStock-main/public/sigware_api.php',
            type: 'POST',
            data: {
                cliente_id: '<?php echo $_SESSION['cliente_id'] ?>',
                actualizar_inventario: true
            },
            success: function(response) {
                clearInterval(timerInterval);
                Swal.fire({
                    title: "Proceso Exitoso!",
                    icon: "success",
                    draggable: true
                });
                setTimeout(() => {
                    location.reload()
                }, 1000);
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: "La Actualización Falló",
                    text: error,
                    icon: "error"
                });
            }
        });
    }

    let table = $("#tablaInventarios").DataTable({
            "oLanguage": {
                "sUrl": "assets/js/datatables_es.json"
            },
            responsive: true,
            pagingType: "full_numbers"
        });
</script>
</body>
</html>