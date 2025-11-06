<?php
/**
 * Dashboard Principal - Sistema de Gestión de Compras
 */

require_once 'config.php';

$page_title = 'Sistema de Gestión de Compras';
$page_heading = 'Panel de Gestión';

// Obtener estadísticas del dashboard
$conexion = conectarDB();
$stats = ['total_proveedores' => 0, 'ordenes_pendientes' => 0, 'ordenes_mes' => 0, 'pagos_vencidos' => 0];

try {
    $sql_stats = "SELECT 
        (SELECT COUNT(*) FROM proveedores WHERE estado = 'Activo') as total_proveedores,
        (SELECT COUNT(*) FROM ordenes_compra WHERE estado IN ('Pendiente', 'Enviada')) as ordenes_pendientes,
        (SELECT COUNT(*) FROM ordenes_compra WHERE MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())) as ordenes_mes,
        (SELECT COUNT(DISTINCT o.id) FROM ordenes_compra o 
         LEFT JOIN (SELECT id_orden, SUM(monto) as pagado FROM pagos GROUP BY id_orden) p ON o.id = p.id_orden 
         INNER JOIN proveedores prov ON o.id_proveedor = prov.id 
         WHERE o.estado = 'Recibida' 
         AND (o.total - COALESCE(p.pagado, 0)) > 0 
         AND DATEDIFF(CURDATE(), CASE 
            WHEN prov.condiciones_pago = 'Contado' THEN o.fecha_emision
            WHEN prov.condiciones_pago = '7 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 7 DAY)
            WHEN prov.condiciones_pago = '15 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 15 DAY)
            WHEN prov.condiciones_pago = '30 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 30 DAY)
            WHEN prov.condiciones_pago = '60 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 60 DAY)
            WHEN prov.condiciones_pago = '90 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 90 DAY)
            ELSE DATE_ADD(o.fecha_emision, INTERVAL 30 DAY)
         END) > 0) as pagos_vencidos";
    
    $resultado = $conexion->query($sql_stats);
    if ($resultado) {
        $stats = $resultado->fetch_assoc();
    }
} catch (Exception $e) {
    $error_db = "Error al obtener estadísticas: " . $e->getMessage();
}

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($error_db)): ?>
    <div class="alert alert-danger">
        <strong>⚠️ Error:</strong> <?php echo $error_db; ?>
    </div>
<?php endif; ?>

<section class="stat-grid">
    <div class="stat-card primary">
        <p class="stat-title">Proveedores Activos</p>
        <p class="stat-value"><?php echo $stats['total_proveedores']; ?></p>
    </div>
    <div class="stat-card danger">
        <p class="stat-title">Órdenes Pendientes</p>
        <p class="stat-value"><?php echo $stats['ordenes_pendientes']; ?></p>
    </div>
    <div class="stat-card success">
        <p class="stat-title">Órdenes del Mes</p>
        <p class="stat-value"><?php echo $stats['ordenes_mes']; ?></p>
    </div>
    <div class="stat-card info">
        <p class="stat-title">Pagos Vencidos</p>
        <p class="stat-value"><?php echo $stats['pagos_vencidos']; ?></p>
    </div>
</section>

<section class="info-box">
    <h2>Seguimiento Rápido</h2>
    <p>Accede directamente a las funciones más utilizadas del sistema.</p>
    <button class="btn-primary" onclick="window.location.href='ordenes/index.php'">Ir a Listado de OC</button>
    <button class="btn-danger" onclick="window.location.href='pagos/pendientes.php'">Revisar Cuentas por Pagar</button>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>