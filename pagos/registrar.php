<?php
/**
 * Registrar Pago
 */

require_once '../config.php';

$page_title = 'Registrar Pago';
$page_heading = 'Registrar Pago';

$conexion = conectarDB();

// Obtener órdenes con saldo pendiente
$sql_ordenes = "SELECT 
                    o.id,
                    o.numero_orden,
                    o.total,
                    p.nombre as nombre_proveedor,
                    COALESCE(SUM(pag.monto), 0) as pagado,
                    (o.total - COALESCE(SUM(pag.monto), 0)) as saldo_pendiente
                FROM ordenes_compra o
                INNER JOIN proveedores p ON o.id_proveedor = p.id
                LEFT JOIN pagos pag ON o.id = pag.id_orden
                WHERE o.estado = 'Recibida'
                GROUP BY o.id
                HAVING saldo_pendiente > 0
                ORDER BY o.fecha_emision DESC";

$ordenes = $conexion->query($sql_ordenes);

// Orden preseleccionada si viene por parámetro
$orden_seleccionada = null;
if (isset($_GET['orden'])) {
    $id_orden = intval($_GET['orden']);
    $sql_orden = "SELECT 
                    o.id,
                    o.numero_orden,
                    o.total,
                    p.nombre as nombre_proveedor,
                    COALESCE(SUM(pag.monto), 0) as pagado,
                    (o.total - COALESCE(SUM(pag.monto), 0)) as saldo_pendiente
                FROM ordenes_compra o
                INNER JOIN proveedores p ON o.id_proveedor = p.id
                LEFT JOIN pagos pag ON o.id = pag.id_orden
                WHERE o.id = ?
                GROUP BY o.id";
    
    $stmt = $conexion->prepare($sql_orden);
    $stmt->bind_param("i", $id_orden);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $orden_seleccionada = $result->fetch_assoc();
    }
    $stmt->close();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['id_orden']) || empty($_POST['id_orden'])) {
        mostrarMensaje('Debe seleccionar una orden de compra', 'danger');
    } else {
        $id_orden = intval($_POST['id_orden']);
        $fecha_pago = $_POST['fecha_pago'];
        $monto = floatval($_POST['monto']);
        $metodo_pago = $_POST['metodo_pago'];
        $numero_comprobante = limpiarDatos($_POST['numero_comprobante']);
        $observaciones = limpiarDatos($_POST['observaciones']);
        
        if ($monto <= 0) {
            mostrarMensaje('El monto debe ser mayor a cero', 'danger');
        } else {
            $sql = "INSERT INTO pagos (id_orden, fecha_pago, monto, metodo_pago, numero_comprobante, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("isdsss", $id_orden, $fecha_pago, $monto, $metodo_pago, $numero_comprobante, $observaciones);
            
            if ($stmt->execute()) {
                mostrarMensaje('Pago registrado exitosamente', 'success');
                $stmt->close();
                cerrarDB($conexion);
                header('Location: pendientes.php');
                exit();
            } else {
                mostrarMensaje('Error al registrar el pago: ' . $conexion->error, 'danger');
            }
            
            $stmt->close();
        }
    }
}

cerrarDB($conexion);

// JavaScript para actualizar info de la orden
$extra_js = "
function actualizarInfo(select) {
    const option = select.options[select.selectedIndex];
    const infoBox = document.getElementById('infoOrden');
    
    if (option.value) {
        const total = parseFloat(option.dataset.total);
        const pagado = parseFloat(option.dataset.pagado);
        const saldo = parseFloat(option.dataset.saldo);
        const proveedor = option.dataset.proveedor;
        
        document.getElementById('infoProveedor').textContent = proveedor;
        document.getElementById('infoTotal').textContent = '$' + total.toFixed(2);
        document.getElementById('infoPagado').textContent = '$' + pagado.toFixed(2);
        document.getElementById('infoSaldo').textContent = '$' + saldo.toFixed(2);
        
        infoBox.style.display = 'block';
    } else {
        infoBox.style.display = 'none';
    }
}

window.addEventListener('DOMContentLoaded', function() {
    const select = document.querySelector('select[name=\"id_orden\"]');
    if (select && select.value) {
        actualizarInfo(select);
    }
});
";

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>💵 Registrar Pago</h2>
    </div>

    <?php if ($ordenes && $ordenes->num_rows > 0): ?>
    <form method="POST" action="">
        <div class="form-group">
            <label>Seleccionar Orden de Compra <span class="required">*</span></label>
            <select name="id_orden" class="form-control" required onchange="actualizarInfo(this)">
                <option value="">-- Seleccione una orden de compra --</option>
                <?php 
                $ordenes->data_seek(0);
                while ($orden = $ordenes->fetch_assoc()): 
                ?>
                    <option value="<?php echo $orden['id']; ?>" 
                            data-total="<?php echo $orden['total']; ?>"
                            data-pagado="<?php echo $orden['pagado']; ?>"
                            data-saldo="<?php echo $orden['saldo_pendiente']; ?>"
                            data-proveedor="<?php echo htmlspecialchars($orden['nombre_proveedor']); ?>"
                            <?php echo ($orden_seleccionada && $orden_seleccionada['id'] == $orden['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($orden['numero_orden']); ?> - <?php echo htmlspecialchars($orden['nombre_proveedor']); ?> (Saldo: <?php echo formatearMoneda($orden['saldo_pendiente']); ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <small style="color: #858796; font-size: 0.75rem; margin-top: 5px; display: block;">
                Seleccione la orden a la cual desea registrar el pago
            </small>
        </div>

        <div class="info-box" id="infoOrden" style="display: <?php echo $orden_seleccionada ? 'block' : 'none'; ?>;">
            <div class="info-row">
                <span><strong>Proveedor:</strong></span>
                <span id="infoProveedor"><?php echo $orden_seleccionada ? htmlspecialchars($orden_seleccionada['nombre_proveedor']) : ''; ?></span>
            </div>
            <div class="info-row">
                <span><strong>Total de la Orden:</strong></span>
                <span id="infoTotal"><?php echo $orden_seleccionada ? formatearMoneda($orden_seleccionada['total']) : ''; ?></span>
            </div>
            <div class="info-row">
                <span><strong>Ya Pagado:</strong></span>
                <span id="infoPagado"><?php echo $orden_seleccionada ? formatearMoneda($orden_seleccionada['pagado']) : ''; ?></span>
            </div>
            <div class="info-row">
                <span><strong>Saldo Pendiente:</strong></span>
                <span id="infoSaldo" style="font-size: 1.15rem; font-weight: bold; color: #e74a3b;">
                    <?php echo $orden_seleccionada ? formatearMoneda($orden_seleccionada['saldo_pendiente']) : ''; ?>
                </span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Fecha de Pago <span class="required">*</span></label>
                <input type="date" name="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Monto del Pago <span class="required">*</span></label>
                <input type="number" name="monto" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Método de Pago <span class="required">*</span></label>
                <select name="metodo_pago" class="form-control" required>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label>Número de Comprobante</label>
                <input type="text" name="numero_comprobante" class="form-control" placeholder="Ej: 001-00123456">
            </div>
        </div>

        <div class="form-group">
            <label>Observaciones</label>
            <textarea name="observaciones" class="form-control" placeholder="Notas adicionales sobre el pago"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-success">💾 Registrar Pago</button>
            <a href="pendientes.php" class="btn-danger">❌ Cancelar</a>
        </div>
    </form>

    <?php else: ?>
    <div class="alert alert-warning">
        <h3 style="color: #856404; margin: 0 0 15px 0;">⚠️ No hay órdenes disponibles para pagar</h3>
        <p style="margin: 0 0 10px 0; font-size: 0.875rem; color: #856404;">Para poder registrar un pago necesitas:</p>
        <ol style="margin: 10px 0 15px 20px; font-size: 0.875rem; color: #856404;">
            <li>Crear una orden de compra</li>
            <li>Recepcionar la orden (marcarla como "Recibida")</li>
            <li>Que la orden tenga saldo pendiente de pago</li>
        </ol>
        <a href="../ordenes/crear.php" class="btn-primary">➕ Crear Orden de Compra</a>
        <a href="../ordenes/index.php" class="btn-info" style="margin-left: 10px;">📋 Ver Órdenes</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>