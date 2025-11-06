<?php
/**
 * Sidebar Optimizado - Eliminando redundancias
 * Solo enlaces a páginas de LISTADO
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

// Calcular base path
$base = ($current_module == 'seccion_proveedor') ? '' : '../';
?>

<aside class="sidebar">
    <h3 class="sidebar-heading">NAVEGACIÓN</h3>
    
    <!-- Inicio -->
    <a href="<?php echo $base; ?>index.php" class="sidebar-link" <?php echo isActive('seccion_proveedor', 'index'); ?>>
        🏠 Panel Principal
    </a>
    
    <h3 class="sidebar-heading">MÓDULOS</h3>
    
    <!-- Proveedores -->
    <a href="<?php echo $base; ?>proveedores/index.php" class="sidebar-link" <?php echo isActive('proveedores', 'index'); ?>>
        📋 Proveedores
    </a>
    
    <!-- Órdenes de Compra -->
    <a href="<?php echo $base; ?>ordenes/index.php" class="sidebar-link" <?php echo isActive('ordenes', 'index'); ?>>
        📦 Órdenes de Compra
    </a>
    
    <!-- Recepción de Material -->
    <a href="<?php echo $base; ?>ordenes/recepcion.php" class="sidebar-link" <?php echo isActive('ordenes', 'recepcion'); ?>>
        ✅ Recepción de Material
    </a>
    
    <!-- Historial de Órdenes -->
    <a href="<?php echo $base; ?>ordenes/historial.php" class="sidebar-link" <?php echo isActive('ordenes', 'historial'); ?>>
        📚 Historial de Órdenes
    </a>
    
    <h3 class="sidebar-heading">PAGOS</h3>
    
    <!-- Saldos Pendientes -->
    <a href="<?php echo $base; ?>pagos/pendientes.php" class="sidebar-link" <?php echo isActive('pagos', 'pendientes'); ?>>
        ⏰ Cuentas por Pagar
    </a>
    
    <!-- Registrar Pago -->
    <a href="<?php echo $base; ?>pagos/registrar.php" class="sidebar-link" <?php echo isActive('pagos', 'registrar'); ?>>
        💵 Registrar Pago
    </a>
    
    <!-- Condiciones Comerciales -->
    <a href="<?php echo $base; ?>pagos/condiciones.php" class="sidebar-link" <?php echo isActive('pagos', 'condiciones'); ?>>
        📋 Condiciones de Pago
    </a>
    
    <h3 class="sidebar-heading">REPORTES</h3>
    
    <!-- Reportes Financieros -->
    <a href="<?php echo $base; ?>pagos/reportes.php" class="sidebar-link" <?php echo isActive('pagos', 'reportes'); ?>>
        📊 Reportes Financieros
    </a>
    
    <!-- Reportes Generales -->
    <a href="<?php echo $base; ?>reportes/index.php" class="sidebar-link" <?php echo isActive('reportes', 'index'); ?>>
        📈 Reportes Generales
    </a>
</aside>