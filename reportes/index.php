<?php
/**
 * Reportes Generales del Sistema
 */

require_once '../config.php';

$page_title = 'Reportes Generales';
$page_heading = 'Reportes Generales';

$conexion = conectarDB();

// Estadísticas generales
$sql_stats = "SELECT 
                (SELECT COUNT(*) FROM proveedores WHERE estado = 'Activo') as total_proveedores,
                (SELECT COUNT(*) FROM ordenes_compra) as total_ordenes,
                (SELECT COUNT(*) FROM ordenes_compra WHERE estado = 'Pendiente') as ordenes_pendientes,
                (SELECT COUNT(*) FROM ordenes_compra WHERE estado = 'Recibida') as ordenes_recibidas,
                (SELECT COALESCE(SUM(total), 0) FROM ordenes_compra) as monto_total_ordenes,
                (SELECT COALESCE(SUM(monto), 0) FROM pagos) as monto_total_pagos";

$stats = $conexion->query($sql_stats)->fetch_assoc();

// Top 5 proveedores por monto
$sql_top_prov = "SELECT 
                    p.nombre,
                    COUNT(o.id) as total_ordenes,
                    COALESCE(SUM(o.total), 0) as monto_total
                FROM proveedores p
                LEFT JOIN ordenes_compra o ON p.id = o.id_proveedor
                GROUP BY p.id
                HAVING monto_total > 0
                ORDER BY monto_total DESC
                LIMIT 5";

$top_proveedores = $conexion->query($sql_top_prov);

// Órdenes recientes
$sql_recientes = "SELECT 
                    o.numero_orden,
                    o.fecha_emision,
                    o.total,
                    o.estado,
                    p.nombre as nombre_proveedor
                FROM ordenes_compra o
                INNER JOIN proveedores p ON o.id_proveedor = p.id
                ORDER BY o.fecha_emision DESC
                LIMIT 10";

$ordenes_recientes = $conexion->query($sql_recientes);

// Pagos recientes
$sql_pagos_rec = "SELECT 
                    pag.fecha_pago,
                    pag.monto,
                    pag.metodo_pago,
                    o.numero_orden,
                    p.nombre as nombre_proveedor
                FROM pagos pag
                INNER JOIN ordenes_compra o ON pag.id_orden = o.id
                INNER JOIN proveedores p ON o.id_proveedor = p.id
                ORDER BY pag.fecha_pago DESC
                LIMIT 10";

$pagos_recientes = $conexion->query($sql_pagos_rec);

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<h2 style="color: #5a5c69; margin-bottom: 20px;">📊 Resumen General del Sistema</h2>

<div class="stat-grid" style="margin-bottom: 30px;">
    <div class="stat-card primary">
        <p class="stat-label">Proveedores Activos</p>
        <p class="stat-value"><?php echo $stats['total_proveedores']; ?></p>
    </div>
    <div class="stat-card info">
        <p class="stat-label">Total Órdenes</p>
        <p class="stat-value"><?php echo $stats['total_ordenes']; ?></p>
    </div>
    <div class="stat-card warning">
        <p class="stat-label">Órdenes Pendientes</p>
        <p class="stat-value"><?php echo $stats['ordenes_pendientes']; ?></p>
    </div>
    <div class="stat-card success">
        <p class="stat-label">Órdenes Recibidas</p>
        <p class="stat-value"><?php echo $stats['ordenes_recibidas']; ?></p>
    </div>
    <div class="stat-card primary">
        <p class="stat-label">Monto Total Compras</p>
        <p class="stat-value"><?php echo formatearMoneda($stats['monto_total_ordenes']); ?></p>
    </div>
    <div class="stat-card success">
        <p class="stat-label">Monto Total Pagos</p>
        <p class="stat-value"><?php echo formatearMoneda($stats['monto_total_pagos']); ?></p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <div class="table-container" style="margin: 0;">
        <h3 style="color: #5a5c69; font-size: 1.1rem; font-weight: 700; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e3e6f0;">
            🏆 Top 5 Proveedores
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Órdenes</th>
                    <th>Monto Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($prov = $top_proveedores->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($prov['nombre']); ?></strong></td>
                        <td><?php echo $prov['total_ordenes']; ?></td>
                        <td><?php echo formatearMoneda($prov['monto_total']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="table-container" style="margin: 0;">
        <h3 style="color: #5a5c69; font-size: 1.1rem; font-weight: 700; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e3e6f0;">
            💵 Últimos Pagos Registrados
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Orden</th>
                    <th>Monto</th>
                    <th>Método</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pago = $pagos_recientes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo formatearFecha($pago['fecha_pago']); ?></td>
                        <td><?php echo htmlspecialchars($pago['numero_orden']); ?></td>
                        <td><?php echo formatearMoneda($pago['monto']); ?></td>
                        <td><?php echo htmlspecialchars($pago['metodo_pago']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="table-container">
    <h3 style="color: #5a5c69; font-size: 1.1rem; font-weight: 700; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e3e6f0;">
        📋 Órdenes Recientes
    </h3>
    <table>
        <thead>
            <tr>
                <th>Número Orden</th>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($orden = $ordenes_recientes->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($orden['numero_orden']); ?></strong></td>
                    <td><?php echo htmlspecialchars($orden['nombre_proveedor']); ?></td>
                    <td><?php echo formatearFecha($orden['fecha_emision']); ?></td>
                    <td><?php echo formatearMoneda($orden['total']); ?></td>
                    <td>
                        <?php
                        $clase_badge = 'badge-' . strtolower($orden['estado']);
                        ?>
                        <span class="badge <?php echo $clase_badge; ?>"><?php echo $orden['estado']; ?></span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>