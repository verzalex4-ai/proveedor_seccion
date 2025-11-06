<?php
/**
 * Sidebar Dinámico
 * Detecta automáticamente la página activa
 */

// Detectar módulo y página actual
$current_file = basename($_SERVER['PHP_SELF'], '.php');
$current_module = basename(dirname($_SERVER['PHP_SELF']));

// Función para marcar página activa
function isActive($module, $page) {
    global $current_module, $current_file;
    if ($current_module == $module && $current_file == $page) {
        return 'style="background-color: #354e99;"';
    }
    return '';
}

// Función para mostrar submenu abierto
function isModuleActive($module) {
    global $current_module;
    return ($current_module == $module) ? 'show' : '';
}

// Calcular base path
$base = ($current_module == 'seccion_proveedor') ? '' : '../';
?>

<aside class="sidebar">
    <h3 class="sidebar-heading">NAVEGACIÓN</h3>
    
    <!-- Inicio -->
    <a href="<?php echo $base; ?>index.php" class="sidebar-link" <?php echo isActive('seccion_proveedor', 'index'); ?>>
        🏠 Inicio
    </a>
    
    <h3 class="sidebar-heading">MÓDULOS</h3>
    
    <!-- Gestión de Proveedores -->
    <div class="sidebar-module">
        <a href="#" class="sidebar-link collapsed" onclick="toggleSubmenu('proveedores', this)">
            1. Gestión de Proveedores
        </a>
        <ul class="submenu <?php echo isModuleActive('proveedores'); ?>" id="submenu-proveedores">
            <li><a href="<?php echo $base; ?>proveedores/index.php" <?php echo isActive('proveedores', 'index'); ?>>Listado de Proveedores</a></li>
            <li><a href="<?php echo $base; ?>proveedores/agregar.php" <?php echo isActive('proveedores', 'agregar'); ?>>Agregar Proveedor</a></li>
        </ul>
    </div>
    
    <!-- Órdenes de Compra -->
    <div class="sidebar-module">
        <a href="#" class="sidebar-link collapsed" onclick="toggleSubmenu('ordenes', this)">
            2. Órdenes de Compra
        </a>
        <ul class="submenu <?php echo isModuleActive('ordenes'); ?>" id="submenu-ordenes">
            <li><a href="<?php echo $base; ?>ordenes/index.php" <?php echo isActive('ordenes', 'index'); ?>>Listado y Seguimiento</a></li>
            <li><a href="<?php echo $base; ?>ordenes/crear.php" <?php echo isActive('ordenes', 'crear'); ?>>Crear Nueva OC</a></li>
            <li><a href="<?php echo $base; ?>ordenes/historial.php" <?php echo isActive('ordenes', 'historial'); ?>>Historial de Órdenes</a></li>
        </ul>
    </div>

    <!-- Control de Pagos -->
    <div class="sidebar-module">
        <a href="#" class="sidebar-link collapsed" onclick="toggleSubmenu('pagos', this)">
            3. Control de Pagos
        </a>
        <ul class="submenu <?php echo isModuleActive('pagos'); ?>" id="submenu-pagos">
            <li><a href="<?php echo $base; ?>pagos/pendientes.php" <?php echo isActive('pagos', 'pendientes'); ?>>Saldos Pendientes</a></li>
            <li><a href="<?php echo $base; ?>pagos/registrar.php" <?php echo isActive('pagos', 'registrar'); ?>>Registrar Pago</a></li>
            <li><a href="<?php echo $base; ?>pagos/condiciones.php" <?php echo isActive('pagos', 'condiciones'); ?>>Condiciones de Pago</a></li>
            <li><a href="<?php echo $base; ?>pagos/reportes.php" <?php echo isActive('pagos', 'reportes'); ?>>Reportes Financieros</a></li>
        </ul>
    </div>

    <h3 class="sidebar-heading">REPORTES</h3>
    <a href="<?php echo $base; ?>reportes/index.php" class="sidebar-link" <?php echo isActive('reportes', 'index'); ?>>
        📊 Reportes Generales
    </a>
</aside>