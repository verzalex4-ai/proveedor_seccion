<?php
/**
 * Ver Detalle Completo de Orden de Compra
 */

require_once '../config.php';

$page_title = 'Ver Orden de Compra';
$page_heading = 'Detalle de Orden';

// Verificar si se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    mostrarMensaje('ID de orden no especificado', 'danger');
    header('Location: index.php');
    exit();
}

$id = intval($_GET['id']);
$conexion = conectarDB();

// Obtener datos de la orden
$sql = "SELECT o.*, p.nombre as nombre_proveedor, p.email, p.telefono, p.condiciones_pago
        FROM ordenes_compra o 
        INNER JOIN proveedores p ON o.id_proveedor = p.id 
        WHERE o.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    mostrarMensaje('Orden de compra no encontrada', 'danger');
    $stmt->close();
    cerrarDB($conexion);
    header('Location: index.php');
    exit();
}

$orden = $resultado->fetch_assoc();
$stmt->close();

// Obtener detalles de la orden
$sql_detalle = "SELECT * FROM detalle_orden WHERE id_orden = ?";
$stmt_detalle = $conexion->prepare($sql_detalle);
$stmt_detalle->bind_param("i", $id);
$stmt_detalle->execute();
$detalles = $stmt_detalle->get_result();
$stmt_detalle->close();

// Obtener pagos asociados
$sql_pagos = "SELECT * FROM pagos WHERE id_orden = ? ORDER BY fecha_pago DESC";
$stmt_pagos = $conexion->prepare($sql_pagos);
$stmt_pagos->bind_param("i", $id);
$stmt_pagos->execute();
$pagos = $stmt_pagos->get_result();
$stmt_pagos->close();

// Calcular total pagado
$total_pagado = 0;
$pagos->data_seek(0);
while ($pago = $pagos->fetch_assoc()) {
    $total_pagado += $pago['monto'];
}
$pagos->data_seek(0);

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e3e6f0;">
        <h2 style="color: #4e73df; font-size: 1.5rem; font-weight: 700; margin: 0;">
            Orden: <?php echo htmlspecialchars($orden['numero_orden']); ?>
        </h2>
        <?php
        $clase_badge = 'badge-' . strtolower($orden['estado']);
        ?>
        <span class="badge <?php echo $clase_badge; ?>" style="font-size: 0.9rem; padding: 6px 12px;">
            <?php echo $orden['estado']; ?>
        </span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <div class="info-box">
            <h3 style="color: #4e73df; font-size: 1rem; margin: 0 0 15px 0;">📋 Información de la Orden</h3>
            <div class="info-row">
                <strong>Fecha de Emisión:</strong>
                <span><?php echo formatearFecha($orden['fecha_emision']); ?></span>
            </div>
            <div class="info-row">
                <strong>Fecha de Entrega:</strong>
                <span><?php echo formatearFecha($orden['fecha_entrega_estimada']); ?></span>
            </div>
            <?php if ($orden['fecha_recepcion']): ?>
            <div class="info-row">
                <strong>Fecha de Recepción:</strong>
                <span><?php echo formatearFecha($orden['fecha_recepcion']); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <strong>Estado:</strong>
                <span><?php echo $orden['estado']; ?></span>
            </div>
        </div>

        <div class="info-box">
            <h3 style="color: #4e73df; font-size: 1rem; margin: 0 0 15px 0;">🏢 Información del Proveedor</h3>
            <div class="info-row">
                <strong>Proveedor:</strong>
                <span><?php echo htmlspecialchars($orden['nombre_proveedor']); ?></span>
            </div>
            <div class="info-row">
                <strong>Email:</strong>
                <span><?php echo htmlspecialchars($orden['email']); ?></span>
            </div>
            <div class="info-row">
                <strong>Teléfono:</strong>
                <span><?php echo htmlspecialchars($orden['telefono']); ?></span>
            </div>
            <div class="info-row">
                <strong>Condiciones de Pago:</strong>
                <span><?php echo htmlspecialchars($orden['condiciones_pago']); ?></span>
            </div>
        </div>
    </div>

    <h3 style="color: #4e73df; margin-bottom: 15px;">📦 Productos/Servicios</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($detalle = $detalles->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($detalle['producto']); ?></strong></td>
                    <td><?php echo $detalle['cantidad']; ?></td>
                    <td><?php echo htmlspecialchars($detalle['unidad_medida']); ?></td>
                    <td><?php echo formatearMoneda($detalle['precio_unitario']); ?></td>
                    <td><?php echo formatearMoneda($detalle['subtotal']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="totales-box">
        <div class="total-row">
            <span>Subtotal:</span>
            <span><?php echo formatearMoneda($orden['subtotal']); ?></span>
        </div>
        <div class="total-row">
            <span>IVA (21%):</span>
            <span><?php echo formatearMoneda($orden['impuestos']); ?></span>
        </div>
        <div class="total-row final">
            <span>TOTAL:</span>
            <span><?php echo formatearMoneda($orden['total']); ?></span>
        </div>
    </div>

    <?php if ($orden['observaciones']): ?>
    <div class="info-box">
        <h3 style="color: #4e73df; font-size: 1rem; margin: 0 0 10px 0;">📝 Observaciones</h3>
        <p style="margin: 0; color: #5a5c69;"><?php echo nl2br(htmlspecialchars($orden['observaciones'])); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($pagos->num_rows > 0): ?>
    <div style="margin-top: 30px;">
        <h3 style="color: #5a5c69; margin-bottom: 15px;">💵 Historial de Pagos</h3>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Método</th>
                    <th>Comprobante</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pago = $pagos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo formatearFecha($pago['fecha_pago']); ?></td>
                        <td><?php echo formatearMoneda($pago['monto']); ?></td>
                        <td><?php echo htmlspecialchars($pago['metodo_pago']); ?></td>
                        <td><?php echo htmlspecialchars($pago['numero_comprobante']); ?></td>
                        <td><?php echo htmlspecialchars($pago['observaciones']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #4e73df;">
            <div class="info-row">
                <strong style="font-size: 1.1rem;">Total Pagado:</strong>
                <strong style="font-size: 1.1rem; color: #1cc88a;"><?php echo formatearMoneda($total_pagado); ?></strong>
            </div>
            <div class="info-row">
                <strong style="font-size: 1.1rem;">Saldo Pendiente:</strong>
                <strong style="font-size: 1.1rem; color: #e74a3b;"><?php echo formatearMoneda($orden['total'] - $total_pagado); ?></strong>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="form-actions">
        <?php if ($orden['estado'] != 'Recibida' && $orden['estado'] != 'Cancelada'): ?>
            <a href="recepcion.php?id=<?php echo $orden['id']; ?>" class="btn-success">✅ Recepcionar</a>
        <?php endif; ?>
        <?php if (($orden['total'] - $total_pagado) > 0): ?>
            <a href="../pagos/registrar.php?orden=<?php echo $orden['id']; ?>" class="btn-primary">💵 Registrar Pago</a>
        <?php endif; ?>
        <a href="index.php" class="btn-info">← Volver al Listado</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>