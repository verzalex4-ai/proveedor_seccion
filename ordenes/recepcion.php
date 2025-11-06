<?php
/**
 * Recepción de Material
 */

require_once '../config.php';

$page_title = 'Recepción de Material';
$page_heading = 'Recepción de Material';

$conexion = conectarDB();

// Verificar si se recibió un ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Obtener datos de la orden
    $sql = "SELECT o.*, p.nombre as nombre_proveedor 
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
} else {
    // Obtener órdenes pendientes para recepcionar
    $sql = "SELECT o.*, p.nombre as nombre_proveedor 
            FROM ordenes_compra o 
            INNER JOIN proveedores p ON o.id_proveedor = p.id 
            WHERE o.estado IN ('Pendiente', 'Enviada') 
            ORDER BY o.fecha_emision DESC";
    $ordenes_pendientes = $conexion->query($sql);
}

// Procesar recepción
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recepcionar'])) {
    $id_orden = intval($_POST['id_orden']);
    $fecha_recepcion = $_POST['fecha_recepcion'];
    
    $sql = "UPDATE ordenes_compra SET estado = 'Recibida', fecha_recepcion = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $fecha_recepcion, $id_orden);
    
    if ($stmt->execute()) {
        mostrarMensaje('Material recepcionado exitosamente', 'success');
        $stmt->close();
        cerrarDB($conexion);
        header('Location: index.php');
        exit();
    } else {
        mostrarMensaje('Error al recepcionar: ' . $conexion->error, 'danger');
    }
    
    $stmt->close();
}

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($orden)): ?>
    <!-- Formulario de recepción específica -->
    <div class="form-container">
        <div class="form-header">
            <h2>✅ Recepcionar Orden de Compra</h2>
        </div>

        <div class="info-box">
            <div class="info-row">
                <span><strong>Número de Orden:</strong></span>
                <span><?php echo htmlspecialchars($orden['numero_orden']); ?></span>
            </div>
            <div class="info-row">
                <span><strong>Proveedor:</strong></span>
                <span><?php echo htmlspecialchars($orden['nombre_proveedor']); ?></span>
            </div>
            <div class="info-row">
                <span><strong>Fecha de Emisión:</strong></span>
                <span><?php echo formatearFecha($orden['fecha_emision']); ?></span>
            </div>
            <div class="info-row">
                <span><strong>Total:</strong></span>
                <span><?php echo formatearMoneda($orden['total']); ?></span>
            </div>
            <div class="info-row">
                <span><strong>Estado Actual:</strong></span>
                <span><?php echo $orden['estado']; ?></span>
            </div>
        </div>

        <h3 style="color: #4e73df; margin: 20px 0 15px 0;">📦 Productos/Servicios</h3>
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
                        <td><?php echo htmlspecialchars($detalle['producto']); ?></td>
                        <td><?php echo $detalle['cantidad']; ?></td>
                        <td><?php echo htmlspecialchars($detalle['unidad_medida']); ?></td>
                        <td><?php echo formatearMoneda($detalle['precio_unitario']); ?></td>
                        <td><?php echo formatearMoneda($detalle['subtotal']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <form method="POST" action="">
            <input type="hidden" name="id_orden" value="<?php echo $orden['id']; ?>">
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Fecha de Recepción <span class="required">*</span></label>
                <input type="date" name="fecha_recepcion" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" name="recepcionar" class="btn-success">✅ Confirmar Recepción</button>
                <a href="index.php" class="btn-danger">❌ Cancelar</a>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- Listado de órdenes pendientes -->
    <div class="table-container">
        <div class="form-header">
            <h2>📦 Órdenes Pendientes de Recepción</h2>
        </div>

        <?php if ($ordenes_pendientes && $ordenes_pendientes->num_rows > 0): ?>
            <?php while ($orden_pend = $ordenes_pendientes->fetch_assoc()): ?>
                <div style="background-color: #f8f9fc; padding: 15px; border-radius: 0.35rem; margin-bottom: 15px; border-left: 4px solid #4e73df;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 1.1rem; font-weight: bold; color: #4e73df; margin-bottom: 5px;">
                                <?php echo htmlspecialchars($orden_pend['numero_orden']); ?>
                            </div>
                            <div style="font-size: 0.875rem; color: #5a5c69;">
                                <strong>Proveedor:</strong> <?php echo htmlspecialchars($orden_pend['nombre_proveedor']); ?> | 
                                <strong>Fecha:</strong> <?php echo formatearFecha($orden_pend['fecha_emision']); ?> | 
                                <strong>Total:</strong> <?php echo formatearMoneda($orden_pend['total']); ?>
                            </div>
                        </div>
                        <div>
                            <a href="recepcion.php?id=<?php echo $orden_pend['id']; ?>" class="btn-success">✅ Recepcionar</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <p>No hay órdenes pendientes de recepción.</p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>