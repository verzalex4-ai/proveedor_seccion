<?php
/**
 * Saldos Pendientes - Cuentas por Pagar
 */

require_once '../config.php';

$page_title = 'Saldos Pendientes';
$page_heading = 'Saldos Pendientes';

$conexion = conectarDB();

// Obtener órdenes con saldos pendientes
$sql = "SELECT 
            o.id,
            o.numero_orden,
            o.fecha_emision,
            o.total,
            p.nombre as nombre_proveedor,
            p.condiciones_pago,
            COALESCE(SUM(pag.monto), 0) as pagado,
            (o.total - COALESCE(SUM(pag.monto), 0)) as saldo_pendiente,
            CASE 
                WHEN p.condiciones_pago = 'Contado' THEN o.fecha_emision
                WHEN p.condiciones_pago = '7 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 7 DAY)
                WHEN p.condiciones_pago = '15 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 15 DAY)
                WHEN p.condiciones_pago = '30 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 30 DAY)
                WHEN p.condiciones_pago = '60 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 60 DAY)
                WHEN p.condiciones_pago = '90 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 90 DAY)
                ELSE DATE_ADD(o.fecha_emision, INTERVAL 30 DAY)
            END as fecha_vencimiento,
            DATEDIFF(CURDATE(), 
                CASE 
                    WHEN p.condiciones_pago = 'Contado' THEN o.fecha_emision
                    WHEN p.condiciones_pago = '7 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 7 DAY)
                    WHEN p.condiciones_pago = '15 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 15 DAY)
                    WHEN p.condiciones_pago = '30 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 30 DAY)
                    WHEN p.condiciones_pago = '60 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 60 DAY)
                    WHEN p.condiciones_pago = '90 días' THEN DATE_ADD(o.fecha_emision, INTERVAL 90 DAY)
                    ELSE DATE_ADD(o.fecha_emision, INTERVAL 30 DAY)
                END
            ) as dias_vencimiento
        FROM ordenes_compra o
        INNER JOIN proveedores p ON o.id_proveedor = p.id
        LEFT JOIN pagos pag ON o.id = pag.id_orden
        WHERE o.estado = 'Recibida'
        GROUP BY o.id
        HAVING saldo_pendiente > 0
        ORDER BY dias_vencimiento DESC, o.fecha_emision ASC";

$resultado = $conexion->query($sql);

// Calcular estadísticas
$total_vencidos = 0;
$total_proximos = 0;
$suma_total = 0;

if ($resultado && $resultado->num_rows > 0) {
    $resultado->data_seek(0);
    while ($row = $resultado->fetch_assoc()) {
        $suma_total += $row['saldo_pendiente'];
        if ($row['dias_vencimiento'] > 0) {
            $total_vencidos++;
        } elseif ($row['dias_vencimiento'] >= -7) {
            $total_proximos++;
        }
    }
    $resultado->data_seek(0);
}

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
    <div class="stat-card danger">
        <p class="stat-label">Pagos Vencidos</p>
        <p class="stat-value"><?php echo $total_vencidos; ?></p>
    </div>
    <div class="stat-card warning">
        <p class="stat-label">Vencen en 7 días</p>
        <p class="stat-value"><?php echo $total_proximos; ?></p>
    </div>
    <div class="stat-card info">
        <p class="stat-label">Total Pendiente</p>
        <p class="stat-value"><?php echo formatearMoneda($suma_total); ?></p>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2>⏰ Cuentas por Pagar</h2>
    </div>

    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Proveedor</th>
                    <th>Fecha Emisión</th>
                    <th>Vencimiento</th>
                    <th>Total</th>
                    <th>Pagado</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pago = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($pago['numero_orden']); ?></strong></td>
                        <td><?php echo htmlspecialchars($pago['nombre_proveedor']); ?></td>
                        <td><?php echo formatearFecha($pago['fecha_emision']); ?></td>
                        <td><?php echo formatearFecha($pago['fecha_vencimiento']); ?></td>
                        <td><?php echo formatearMoneda($pago['total']); ?></td>
                        <td><?php echo formatearMoneda($pago['pagado']); ?></td>
                        <td><strong><?php echo formatearMoneda($pago['saldo_pendiente']); ?></strong></td>
                        <td>
                            <?php
                            if ($pago['dias_vencimiento'] > 0) {
                                echo '<span class="badge badge-vencido">Vencido (' . $pago['dias_vencimiento'] . ' días)</span>';
                            } elseif ($pago['dias_vencimiento'] >= -7) {
                                echo '<span class="badge badge-proximo">Vence pronto</span>';
                            } else {
                                echo '<span class="badge badge-normal">Normal</span>';
                            }
                            ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="registrar.php?orden=<?php echo $pago['id']; ?>" class="btn-success btn-small">💵 Pagar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>✅ No hay saldos pendientes. Todas las cuentas están al día.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>