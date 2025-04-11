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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
   
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --light-gray: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .report-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin: 20px auto;
            max-width: 98%;
        }
        
        .filter-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
        }
        
        .filter-title {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .movement-type-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .movement-type-btn {
            border: 2px solid #dee2e6;
            background: white;
            color: var(--secondary-color);
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .movement-type-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .movement-type-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .report-section {
            margin-top: 25px;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .report-badge {
            background-color: var(--success-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        table.dataTable {
            border-collapse: collapse !important;
            margin-top: 10px !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        
        .badge-movement {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .badge-entry {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-exit {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-transfer {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        @media (max-width: 768px) {
            .filter-section .col-md-3 {
                margin-bottom: 15px;
            }
            
            .movement-type-selector {
                flex-direction: column;
            }
        }

           
        /* Ajustes generales para la tabla */
        #tablaInventarios {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #tablaInventarios th, 
        #tablaInventarios td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #tablaInventarios {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #tablaInventarios th, 
        #tablaInventarios td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #tablaInventarios th:nth-child(7),  /* Descripción */
        #tablaInventarios td:nth-child(7) {
            min-width: 250px !important;
            white-space: normal !important;
        }
        
        /* Scroll horizontal para pantallas pequeñas */
        .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        /* Scroll horizontal para pantallas pequeñas */
        .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 3px solid #FFF;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        .loader::after {
            content: '';  
            box-sizing: border-box;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 3px solid;
            border-color:rgb(10, 2, 77) transparent;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        } 

        .spinner-overlay {
            position: fixed; /* Fijo en la ventana */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Fondo semitransparente */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Asegura que esté por encima de todo */
        }
</style>
</head>
<body>
<div class="spinner-overlay">
    <span class="loader"></span>
</div>
<div class="container-fluid">
    <div class="report-container"> 

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
                <button type="button" onclick="actualizar()" class="btn btn-sm btn-dark btn-small">Actualizar Desde Sigware</button>
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
                            <th style="text-align: center;">Descripción</th>
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
    </div>
</div>  

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

    $(document).ready(function() {
        $(".spinner-overlay").hide();
    });

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
    language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        responsive: true,
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        pageLength: 10
    });

</script>
</body>
</html>
