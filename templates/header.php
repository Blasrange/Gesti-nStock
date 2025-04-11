<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            margin: 0;
        }
        .navbar {
            background-color: #1f3b5e;
            padding: 10px 20px;
            position: fixed !important;
        }
        .navbar-brand img {
            height: 40px;
        }
        .dropdown-menu {
            background-color: #ffffff;
            border-radius: 8px;
        }
        .dropdown-item:hover {
            background-color: #f1f1f1;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-menu img {
            border-radius: 50%;
            width: 35px;
            height: 35px;
        }
        .content {
            padding: 40px 20px;
            text-align: center;
        }
    </style>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><img src="assets/img/logo.png" alt="CCL"></a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
            <!-- Menú de administración -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cogs"></i> Administración
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="inventarioscontrolle.php"><i class="fas fa-box"></i> Inventarios</a></li>
                        <li><a class="dropdown-item" href="maestraMaterialesController.php"><i class="fas fa-cubes"></i> Maestra de Materiales</a></li>
                        <li><a class="dropdown-item" href="ReabastecimientosController.php"><i class="fas fa-arrow-up"></i> Reabastecimientos</a></li>
                        <li><a class="dropdown-item" href="ReportsController.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                        <li><a class="dropdown-item" href="HistorialController.php"><i class="fas fa-history"></i> Historial</a></li>
                        <li><a class="dropdown-item" href="graficosController.php"><i class="fas fa-chart-pie"></i> Graficos</a></li!>
                        <li><a class="dropdown-item" href="MovimientoController.php"><i class="fas fa-address-card"></i> Log</a></li!>
                        <!--li><a class="dropdown-item" href="modulo_carga.php"><i class="fas fa-upload"></i> Interfaces</a></li!-->                        
                    </ul>
                </li>
            </ul>

            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
            <!-- Menú de mantenimiento -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-tools"></i> Mantenimiento
                    </a>
            <ul class="dropdown-menu">                       
                        <li><a class="dropdown-item" href="UsuarioController.php"><i class="fas fa-user"></i> Usuarios</a></li>
                        <li><a class="dropdown-item" href="ClienteController.php"><i class="fas fa-address-card"></i> Clientes</a></li>
                        <li><a class="dropdown-item" href="modulo_carga.php"><i class="fas fa-upload"></i> Interfaces</a></li>                        
                    </ul>
                </li>
            </ul>


            <!-- Menú de usuario -->
            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle" style="font-size: 35px;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-dark" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a></li>
                </ul>

            </div>
       
</nav>
<nav aria-label="breadcrumb" style="margin-top:75px" id="nvbar">
    <ol class="breadcrumb breadcrumb-chevron p-3 bg-body-tertiary rounded-3">
      <li class="breadcrumb-item">
        <a class="link-body-emphasis" href="dashboard.php">
        <i class="fas fa-home"></i>
          <span class="visually-hidden">Home</span>
        </a>
      </li>
      <?php if(isset($titulo)): ?>
        <li class="breadcrumb-item">
            <a class="link-body-emphasis fw-semibold text-decoration-none" href="#"><?php echo $seccion ?? '';?></a>
        </li>
      <?php endif; ?>
      <li class="breadcrumb-item active" aria-current="page">
        <?php echo $titulo ?? 'Home';?>
      </li>
    </ol>
    </nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        
        // Verificar si el path NO contiene 'graficosController.php'
        if (!currentPath.includes('graficosController.php')) {
            const navBar = document.getElementById('nvbar');
            
            // Aplicar estilos solo si el elemento existe
            if (navBar) {
                navBar.style.marginLeft = '30px';
                navBar.style.marginRight = '30px';
            }
        }
    });
</script>