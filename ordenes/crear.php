<?php
/**
 * Crear Nueva Orden de Compra
 */

require_once '../config.php';

$page_title = 'Crear Orden de Compra';
$page_heading = 'Crear Orden de Compra';

$conexion = conectarDB();

$sql_proveedores = "SELECT id, nombre FROM proveedores WHERE estado = 'Activo' ORDER BY nombre ASC";
$proveedores = $conexion->query($sql_proveedores);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_proveedor = intval($_POST['id_proveedor']);
    $fecha_emision = $_POST['fecha_emision'];
    $fecha_entrega_estimada = $_POST['fecha_entrega_estimada'];
    $observaciones = limpiarDatos($_POST['observaciones']);
    
    // Generar número de orden
    $year = date('Y');
    $sql_count = "SELECT COUNT(*) as total FROM ordenes_compra WHERE YEAR(fecha_emision) = ?";
    $stmt_count = $conexion->prepare($sql_count);
    $stmt_count->bind_param("i", $year);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $count = $result_count->fetch_assoc()['total'] + 1;
    $stmt_count->close();
    
    $numero_orden = "OC-" . $year . "-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    
    // Calcular totales
    $subtotal = 0;
    $productos = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $precios = $_POST['precios'];
    
    for ($i = 0; $i < count($productos); $i++) {
        if (!empty($productos[$i]) && !empty($cantidades[$i]) && !empty($precios[$i])) {
            $subtotal += floatval($cantidades[$i]) * floatval($precios[$i]);
        }
    }
    
    $impuestos = $subtotal * 0.21;
    $total = $subtotal + $impuestos;
    
    // Insertar orden
    $sql = "INSERT INTO ordenes_compra (numero_orden, id_proveedor, fecha_emision, fecha_entrega_estimada, subtotal, impuestos, total, observaciones, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sissddds", $numero_orden, $id_proveedor, $fecha_emision, $fecha_entrega_estimada, $subtotal, $impuestos, $total, $observaciones);
    
    if ($stmt->execute()) {
        $id_orden = $conexion->insert_id;
        
        // Insertar detalles
        $sql_detalle = "INSERT INTO detalle_orden (id_orden, producto, cantidad, unidad_medida, precio_unitario) VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = $conexion->prepare($sql_detalle);
        
        for ($i = 0; $i < count($productos); $i++) {
            if (!empty($productos[$i]) && !empty($cantidades[$i]) && !empty($precios[$i])) {
                $producto = limpiarDatos($productos[$i]);
                $cantidad = intval($cantidades[$i]);
                $unidad = limpiarDatos($_POST['unidades'][$i]);
                $precio = floatval($precios[$i]);
                
                $stmt_detalle->bind_param("isisd", $id_orden, $producto, $cantidad, $unidad, $precio);
                $stmt_detalle->execute();
            }
        }
        
        $stmt_detalle->close();
        mostrarMensaje('Orden de compra creada exitosamente: ' . $numero_orden, 'success');
        $stmt->close();
        cerrarDB($conexion);
        header('Location: index.php');
        exit();
    } else {
        mostrarMensaje('Error al crear la orden: ' . $conexion->error, 'danger');
    }
    
    $stmt->close();
}

cerrarDB($conexion);

// JavaScript para calcular totales
$extra_js = "
function agregarProducto() {
    const container = document.getElementById('productosContainer');
    const primerItem = container.querySelector('.producto-item');
    const nuevoItem = primerItem.cloneNode(true);
    nuevoItem.querySelectorAll('input').forEach(input => {
        if (input.name === 'unidades[]') {
            input.value = 'Unidad';
        } else if (input.name === 'cantidades[]') {
            input.value = '1';
        } else if (input.name === 'precios[]') {
            input.value = '0';
        } else {
            input.value = '';
        }
    });
    container.appendChild(nuevoItem);
    calcularTotales();
}

function removerProducto(boton) {
    const container = document.getElementById('productosContainer');
    const items = container.querySelectorAll('.producto-item');
    if (items.length > 1) {
        boton.closest('.producto-item').remove();
        calcularTotales();
    } else {
        alert('Debe haber al menos un producto en la orden');
    }
}

function calcularTotales() {
    let subtotal = 0;
    const items = document.querySelectorAll('.producto-item');
    items.forEach(item => {
        const cantidad = parseFloat(item.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(item.querySelector('.precio').value) || 0;
        subtotal += cantidad * precio;
    });
    const iva = subtotal * 0.21;
    const total = subtotal + iva;
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('iva').textContent = '$' + iva.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);
}

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('cantidad') || e.target.classList.contains('precio')) {
        calcularTotales();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    calcularTotales();
});
";

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>🆕 Crear Nueva Orden de Compra</h2>
    </div>

    <form method="POST" action="" id="formOrden">
        <div class="form-row">
            <div class="form-group">
                <label>Proveedor <span class="required">*</span></label>
                <select name="id_proveedor" class="form-control" required>
                    <option value="">Seleccione un proveedor</option>
                    <?php 
                    $conexion2 = conectarDB();
                    $proveedores2 = $conexion2->query("SELECT id, nombre FROM proveedores WHERE estado = 'Activo' ORDER BY nombre ASC");
                    while ($prov = $proveedores2->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['nombre']); ?></option>
                    <?php endwhile; 
                    cerrarDB($conexion2);
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Emisión <span class="required">*</span></label>
                <input type="date" name="fecha_emision" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Fecha de Entrega Estimada</label>
            <input type="date" name="fecha_entrega_estimada" class="form-control">
        </div>

        <div class="productos-section">
            <h3>📦 Productos / Servicios</h3>
            <div id="productosContainer">
                <div class="producto-item">
                    <div class="form-group">
                        <label>Producto/Servicio <span class="required">*</span></label>
                        <input type="text" name="productos[]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Cantidad <span class="required">*</span></label>
                        <input type="number" name="cantidades[]" class="form-control cantidad" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Unidad</label>
                        <input type="text" name="unidades[]" class="form-control" value="Unidad">
                    </div>
                    <div class="form-group">
                        <label>Precio Unit. <span class="required">*</span></label>
                        <input type="number" name="precios[]" class="form-control precio" step="0.01" min="0" value="0" required>
                    </div>
                    <button type="button" class="btn-remove" onclick="removerProducto(this)">🗑️</button>
                </div>
            </div>
            <button type="button" class="btn-add" onclick="agregarProducto()">➕ Agregar Producto</button>
        </div>

        <div class="totales-box">
            <div class="total-row">
                <span>Subtotal:</span>
                <span id="subtotal">$0.00</span>
            </div>
            <div class="total-row">
                <span>IVA (21%):</span>
                <span id="iva">$0.00</span>
            </div>
            <div class="total-row final">
                <span>TOTAL:</span>
                <span id="total">$0.00</span>
            </div>
        </div>

        <div class="form-group">
            <label>Observaciones</label>
            <textarea name="observaciones" class="form-control"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-success">💾 Crear Orden de Compra</button>
            <a href="index.php" class="btn-danger">❌ Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>