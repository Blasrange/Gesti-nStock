<?php
session_start();

require_once '../app/db.php';
require_once '../vendor/autoload.php';

use App\Database;

$database = new Database();

$titulo = "Carga de Archivos";
$seccion = "Mantenimiento";
include '../templates/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carga de Archivos</title>
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
            margin: 20px 0;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .total {
            font-weight: bold;
            margin-top: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
        }

        .header h1 {
            margin: 0;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 98%;
            background-color: white;
            z-index: 1000;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .total {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 10px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
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

        .custom-file-upload {
            background-color: #0a002b;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            display: inline-block;
            cursor: pointer;
            font-weight: bold;
        }

        .upload-button {
            background-color: #0a002b;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .upload-button:hover {
            background-color: #05001a;
        }

        h2 {
            color: black !important;
        }
        
        .download-btn {
            margin-top: 10px;
            width: 100%;
        }
        
        .file-box {
            margin: 0 10px;
            font-style: italic;
        }
        
        .forms-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-top: 80px;
        }
        
        .form-upload {
            flex: 1;
            min-width: 300px;
            max-width: 500px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="report-container">
            <div class="forms-container">
                <!-- Formulario para cargar inventario -->
                <div class="form-upload">
                    <h2 style="text-align: center">Cargar Inventario</h2>
                    <form action="cargar_inventario.php" method="post" enctype="multipart/form-data">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label for="file-upload-inventario" class="btn btn-dark">
                                <i class="fas fa-file-excel"></i> Seleccionar archivo
                            </label>
                            <span id="file-selected-inventario" class="file-box">Ningún archivo seleccionado</span>
                            <input type="file" name="file" id="file-upload-inventario" accept=".xlsx, .xls" required style="display: none;">
                        </div>
                        <button type="submit" name="cargar_inventario" class="btn btn-dark w-100">
                            <i class="fas fa-upload"></i> Cargar Inventario
                        </button>
                    </form>
                    <a href="excel/plantilla_inventario.xlsx" class="btn btn-success download-btn" download="Plantilla_Inventario.xlsx">
                        <i class="fas fa-download"></i> Descargar Plantilla
                    </a>
                </div>

                <!-- Formulario para cargar maestra de materiales -->
                <div class="form-upload">
                    <h2 style="text-align: center">Cargar Maestra de Materiales</h2>
                    <form action="cargar_Materiales.php" method="post" enctype="multipart/form-data">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label for="file-upload-materiales" class="btn btn-dark">
                                <i class="fas fa-file-excel"></i> Seleccionar archivo
                            </label>
                            <span id="file-selected-materiales" class="file-box">Ningún archivo seleccionado</span>
                            <input type="file" name="file" id="file-upload-materiales" accept=".xlsx, .xls" required style="display: none;">
                        </div>
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fas fa-upload"></i> Cargar Materiales
                        </button>
                    </form>
                    <a href="excel/plantilla_materiales.xlsx" class="btn btn-success download-btn" download="Plantilla_Materiales.xlsx">
                        <i class="fas fa-download"></i> Descargar Plantilla
                    </a>
                </div>
            </div>

            <!-- Mostrar mensajes de error o éxito -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 1100;">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 1100;">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <script>
                // Mostrar el nombre del archivo seleccionado
                document.getElementById('file-upload-inventario').addEventListener('change', function() {
                    var fileName = this.files[0] ? this.files[0].name : "Ningún archivo seleccionado";
                    document.getElementById('file-selected-inventario').textContent = fileName;
                });

                document.getElementById('file-upload-materiales').addEventListener('change', function() {
                    var fileName = this.files[0] ? this.files[0].name : "Ningún archivo seleccionado";
                    document.getElementById('file-selected-materiales').textContent = fileName;
                });

                // Cerrar automáticamente las alertas después de 5 segundos
                setTimeout(() => {
                    const alerts = document.querySelectorAll('.alert');
                    alerts.forEach(alert => {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    });
                }, 5000);
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

</body>
</html>