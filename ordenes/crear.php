<?php
/**
 * Crear Nueva Orden de Compra - CORREGIDO
 * Validaciones mejoradas y control de productos duplicados
 */

require_once '../config.php';

$page_title = 'Crear Orden de Compra';
$page_heading = 'Crear Orden de Compra';

$conexion = conectarDB();

$sql_proveedores = "SELECT id, nombre FROM proveedores WHERE estado = 'Activo' ORDER BY nombre ASC";
$proveedores = $conexion->query($sql_proveedores);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errores = [];
    
    $id_proveedor = intval($_POST['id_proveedor']);
    $fecha_emision = $_POST['fecha_emision'];
    $fecha_entrega_estimada = !empty($_POST['fecha_entrega_estimada']) ? $_POST['fecha_entrega_estimada'] : null;
    $observaciones = limpiarDatos($_POST['observaciones']);
    
    // Validaciones básicas
    if ($id_proveedor <= 0) {
        $errores[] = 'Debe seleccionar un proveedor válido';
    }
    
    if (empty($fecha_emision)) {
        $errores[] = 'La fecha de emisión es obligatoria';
    }
    
    // Validar fechas
    if ($fecha_entrega_estimada && strtotime($fecha_entrega_estimada) < strtotime($fecha_emision)) {
        $errores[] = 'La fecha de entrega no puede ser anterior a la fecha de emisión';
    }
    
    // Validar productos
    $productos = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $precios = $_POST['precios'];
    $unidades = $_POST['unidades'];
    
    $productos_validos = [];
    $productos_nombres = [];
    
    for ($i = 0; $i < count($productos); $i++) {
        if (!empty($productos[$i]) && !empty($cantidades[$i]) && !empty($precios[$i])) {
            $producto_nombre = trim($productos[$i]);
            $cantidad = floatval($cantidades[$i]);
            $precio = floatval($precios[$i]);
            
            // Validar duplicados
            if (in_array(strtolower($producto_nombre), array_map('strtolower', $productos_nombres))) {
                $errores[] = 'El producto "' . htmlspecialchars($producto_nombre) . '" está duplicado';
                continue;
            }
            
            if ($cantidad <= 0) {
                $errores[] = 'La cantidad del producto "' . htmlspecialchars($producto_nombre) . '" debe ser mayor a cero';
                continue;
            }
            
            if ($precio < 0) {
                $errores[] = 'El precio del producto "' . htmlspecialchars($producto_nombre) . '" no puede ser negativo';
                continue;
            }
            
            $productos_validos[] = [
                'producto' => $producto_nombre,
                'cantidad' => $cantidad,
                'unidad' => limpiarDatos($unidades[$i]),
                'precio' => $precio
            ];
            
            $productos_nombres[] = $producto_nombre;
        }
    }
    
    if (count($productos_validos) == 0) {
        $errores[] = 'Debe agregar al menos un producto a la orden';
    }
    
    if (count($errores) == 0) {
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
        foreach ($productos_validos as $item) {
            $subtotal += $item['cantidad'] * $item['precio'];
        }
        
        $impuestos = $subtotal * 0.21;
        $total = $subtotal + $impuestos;
        
        // Iniciar transacción
        $conexion->begin_transaction();
        
        try {
            // Insertar orden
            $sql = "INSERT INTO ordenes_compra (numero_orden, id_proveedor, fecha_emision, fecha_entrega_estimada, subtotal, impuestos, total, observaciones, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sissddds", $numero_orden, $id_proveedor, $fecha_emision, $fecha_entrega_estimada, $subtotal, $impuestos, $total, $observaciones);
            $stmt->execute();
            $id_orden = $conexion->insert_id;
            $stmt->close();
            
            // Insertar detalles
            $sql_detalle = "INSERT INTO detalle_orden (id_orden, producto, cantidad, unidad_medida, precio_unitario) VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $conexion->prepare($sql_detalle);
            
            foreach ($productos_validos as $item) {
                $stmt_detalle->bind_param("isdsd", 
                    $id_orden, 
                    $item['producto'], 
                    $item['cantidad'], 
                    $item['unidad'], 
                    $item['precio']
                );
                $stmt_detalle->execute();
            }
            
            $stmt_detalle->close();
            
            // Confirmar transacción
            $conexion->commit();
            
            mostrarMensaje('✅ Orden de compra creada exitosamente: ' . $numero_orden, 'success');
            cerrarDB($conexion);
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            $conexion->rollback();
            $errores[] = 'Error al crear la orden: ' . $e->getMessage();
        }
    }
    
    if (count($errores) > 0) {
        $mensaje = '<strong>⚠️ No se pudo crear la orden:</strong><ul style="margin: 10px 0 0 20px;">';
        foreach ($errores as $error) {
            $mensaje .= '<li>' . $error . '</li>';
        }
        $mensaje .= '</ul>';
        mostrarMensaje($mensaje, 'danger');
    }
}

cerrarDB($conexion);

// JavaScript para calcular totales
$extra_js = "
function agregarProducto() {
    const container = document.getElementById('productosContainer');
    const primerItem = container.querySelector('.producto-item');
    const nuevoItem = primerItem.cloneNode(true);
    
    // Limpiar valores
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
        alert('⚠️ Debe haber al menos un producto en la orden');
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
    
    document.getElementById('subtotal').textContent = formatMoney(subtotal);
    document.getElementById('iva').textContent = formatMoney(iva);
    document.getElementById('total').textContent = formatMoney(total);
}

function formatMoney(value) {
    return '$' + value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Validar antes de enviar
document.getElementById('formOrden').addEventListener('submit', function(e) {
    const productos = document.querySelectorAll('input[name=\"productos[]\"]');
    let hayProducto = false;
    
    productos.forEach(input => {
        if (input.value.trim() !== '') {
            hayProducto = true;
        }
    });
    
    if (!hayProducto) {
        e.preventDefault();
        alert('⚠️ Debe agregar al menos un producto a la orden');
        return false;
    }
    
    // Validar fechas
    const fechaEmision = new Date(document.querySelector('input[name=\"fecha_emision\"]').value);
    const fechaEntrega = document.querySelector('input[name=\"fecha_entrega_estimada\"]').value;
    
    if (fechaEntrega) {
        const fechaEntregaDate = new Date(fechaEntrega);
        if (fechaEntregaDate < fechaEmision) {
            e.preventDefault();
            alert('⚠️ La fecha de entrega no puede ser anterior a la fecha de emisión');
            return false;
        }
    }
});

// Calcular en tiempo real
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
        <p style="color: #858796; font-size: 0.875rem; margin: 5px 0 0 0;">
            Complete los datos de la orden. Los campos marcados con <span class="required">*</span> son obligatorios
        </p>
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
                <input type="date" name="fecha_emision" class="form-control" 
                       value="<?php echo date('Y-m-d'); ?>" 
                       max="<?php echo date('Y-m-d'); ?>"
                       required>
            </div>
        </div>

        <div class="form-group">
            <label>Fecha de Entrega Estimada</label>
            <input type="date" name="fecha_entrega_estimada" class="form-control"
                   min="<?php echo date('Y-m-d'); ?>">
            <small style="color: #858796; font-size: 0.75rem; margin-top: 3px; display: block;">
                Opcional. Debe ser igual o posterior a la fecha de emisión
            </small>
        </div>

        <div class="productos-section">
            <h3>📦 Productos / Servicios</h3>
            <p style="color: #858796; font-size: 0.875rem; margin: 0 0 15px 0;">
                ⚠️ No se permiten productos duplicados
            </p>
            <div id="productosContainer">
                <div class="producto-item">
                    <div class="form-group">
                        <label>Producto/Servicio <span class="required">*</span></label>
                        <input type="text" name="productos[]" class="form-control" 
                               maxlength="200"
                               placeholder="Nombre del producto"
                               required>
                    </div>
                    <div class="form-group">
                        <label>Cantidad <span class="required">*</span></label>
                        <input type="number" name="cantidades[]" class="form-control cantidad" 
                               min="0.01" 
                               step="0.01"
                               value="1" 
                               required>
                    </div>
                    <div class="form-group">
                        <label>Unidad</label>
                        <input type="text" name="unidades[]" class="form-control" 
                               value="Unidad"
                               maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Precio Unit. <span class="required">*</span></label>
                        <input type="number" name="precios[]" class="form-control precio" 
                               step="0.01" 
                               min="0" 
                               value="0" 
                               required>
                    </div>
                    <button type="button" class="btn-remove" onclick="removerProducto(this)" title="Eliminar producto">🗑️</button>
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
            <textarea name="observaciones" class="form-control" 
                      maxlength="1000"
                      placeholder="Notas adicionales sobre la orden de compra"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-success">💾 Crear Orden de Compra</button>
            <a href="index.php" class="btn-danger">❌ Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>