<?php
/**
 * Reportes Financieros
 */

require_once '../config.php';

$page_title = 'Reportes Financieros';
$page_heading = 'Reportes Financieros';

$conexion = conectarDB();

// Filtros
$filtro_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$filtro_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

// Reporte de pagos por proveedor
$sql_proveedor = "SELECT 
                    p.nombre as proveedor,
                    COUNT(DISTINCT o.id) as total_ordenes,
                    COALESCE(SUM(o.total), 0) as total_comprado,
                    COALESCE(SUM(pag.monto), 0) as total_pagado,
                    (COALESCE(SUM(o.total), 0) - COALESCE(SUM(pag.monto), 0)) as saldo_pendiente
                FROM proveedores p
                LEFT JOIN ordenes_compra o ON p.id = o.id_proveedor 
                    AND o.fecha_emision BETWEEN ? AND ?
                    AND o.estado = 'Recibida'
                LEFT JOIN pagos pag ON o.id = pag.id_orden
                GROUP BY p.id
                HAVING total_comprado > 0
                ORDER BY total_comprado DESC";

$stmt_prov = $conexion->prepare($sql_proveedor);
$stmt_prov->bind_param("ss", $filtro_desde, $filtro_hasta);
$stmt_prov->execute();
$resultado_proveedor = $stmt_prov->get_result();
$stmt_prov->close();

// Reporte de pagos por método
$sql_metodo = "SELECT 
                metodo_pago,
                COUNT(*) as cantidad,
                SUM(monto) as total
            FROM pagos
            WHERE fecha_pago BETWEEN ? AND ?
            GROUP BY metodo_pago
            ORDER BY total DESC";

$stmt_met = $conexion->prepare($sql_metodo);
$stmt_met->bind_param("ss", $filtro_desde, $filtro_hasta);
$stmt_met->execute();
$resultado_metodo = $stmt_met->get_result();
$stmt_met->close();

// Totales generales
$sql_totales = "SELECT 
                    COUNT(DISTINCT o.id) as total_ordenes,
                    COALESCE(SUM(o.total), 0) as total_compras,
                    COALESCE(SUM(pag.monto), 0) as total_pagos
                FROM ordenes_compra o
                LEFT JOIN pagos pag ON o.id = pag.id_orden
                WHERE o.fecha_emision BETWEEN ? AND ?";

$stmt_tot = $conexion->prepare($sql_totales);
$stmt_tot->bind_param("ss", $filtro_desde, $filtro_hasta);
$stmt_tot->execute();
$totales = $stmt_tot->get_result()->fetch_assoc();
$stmt_tot->close();

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<form method="GET" class="filters-box">
    <div class="filter-row">
        <div class="form-group">
            <label>Desde</label>
            <input type="date" name="desde" class="form-control" value="<?php echo $filtro_desde; ?>">
        </div>

        <div class="form-group">
            <label>Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?php echo $filtro_hasta; ?>">
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn-primary" style="display: block; width: 100%;">🔍 Generar Reporte</button>
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <a href="reportes.php" class="btn-info" style="display: block; text-align: center;">🔄 Limpiar</a>
        </div>
    </div>
</form>

<div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
    <div class="stat-card primary">
        <p class="stat-label">Total Órdenes</p>
        <p class="stat-value"><?php echo $totales['total_ordenes']; ?></p>
    </div>
    <div class="stat-card success">
        <p class="stat-label">Total Compras</p>
        <p class="stat-value"><?php echo formatearMoneda($totales['total_compras']); ?></p>
    </div>
    <div class="stat-card warning">
        <p class="stat-label">Total Pagos</p>
        <p class="stat-value"><?php echo formatearMoneda($totales['total_pagos']); ?></p>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2>📊 Reporte por Proveedor</h2>
    </div>

    <?php if ($resultado_proveedor && $resultado_proveedor->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Órdenes</th>
                    <th>Total Comprado</th>
                    <th>Total Pagado</th>
                    <th>Saldo Pendiente</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $resultado_proveedor->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['proveedor']); ?></strong></td>
                        <td><?php echo $row['total_ordenes']; ?></td>
                        <td><?php echo formatearMoneda($row['total_comprado']); ?></td>
                        <td><?php echo formatearMoneda($row['total_pagado']); ?></td>
                        <td><strong><?php echo formatearMoneda($row['saldo_pendiente']); ?></strong></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>No hay datos en el período seleccionado.</p>
        </div>
    <?php endif; ?>
</div>

<div class="table-container">
    <div class="table-header">
        <h2>💳 Reporte por Método de Pago</h2>
    </div>

    <?php if ($resultado_metodo && $resultado_metodo->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Método de Pago</th>
                    <th>Cantidad</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $resultado_metodo->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['metodo_pago']); ?></strong></td>
                        <td><?php echo $row['cantidad']; ?></td>
                        <td><?php echo formatearMoneda($row['total']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>No hay pagos registrados en el período seleccionado.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>