<?php
/**
 * Historial de Órdenes con Filtros CORREGIDOS
 * ordenes/historial.php
 */

require_once '../config.php';

$page_title = 'Historial de Órdenes';
$page_heading = 'Historial de Órdenes';

$conexion = conectarDB();

// Filtros con validación
$filtro_desde = isset($_GET['desde']) && !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$filtro_hasta = isset($_GET['hasta']) && !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
$filtro_proveedor = isset($_GET['proveedor']) && is_numeric($_GET['proveedor']) ? intval($_GET['proveedor']) : 0;

// Validar que fecha_hasta no sea menor que fecha_desde
if (strtotime($filtro_hasta) < strtotime($filtro_desde)) {
    mostrarMensaje('La fecha final no puede ser anterior a la fecha inicial', 'warning');
    $filtro_hasta = $filtro_desde;
}

// Construir consulta SQL con filtros
$sql = "SELECT o.*, p.nombre as nombre_proveedor 
        FROM ordenes_compra o 
        INNER JOIN proveedores p ON o.id_proveedor = p.id 
        WHERE o.fecha_emision BETWEEN ? AND ?";

$params = [$filtro_desde, $filtro_hasta];
$types = "ss";

if ($filtro_proveedor > 0) {
    $sql .= " AND o.id_proveedor = ?";
    $params[] = $filtro_proveedor;
    $types .= "i";
}

$sql .= " ORDER BY o.fecha_emision DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

// Obtener proveedores para el filtro
$sql_proveedores = "SELECT id, nombre FROM proveedores ORDER BY nombre ASC";
$proveedores = $conexion->query($sql_proveedores);

// Calcular totales
$total_ordenes = 0;
$suma_total = 0;

if ($resultado->num_rows > 0) {
    $resultado->data_seek(0);
    while ($row = $resultado->fetch_assoc()) {
        $total_ordenes++;
        $suma_total += $row['total'];
    }
    $resultado->data_seek(0);
}

cerrarDB($conexion);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 20px;">
    <div class="stat-card primary">
        <p class="stat-label">Total de Órdenes</p>
        <p class="stat-value"><?php echo $total_ordenes; ?></p>
    </div>
    <div class="stat-card success">
        <p class="stat-label">Monto Total</p>
        <p class="stat-value"><?php echo formatearMoneda($suma_total); ?></p>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2>🔍 Historial de Órdenes de Compra</h2>
        <a href="crear.php" class="btn-success">➕ Nueva Orden</a>
    </div>

    <form method="GET" class="filters-box" id="formFiltros">
        <div class="filter-row">
            <div class="form-group">
                <label>Desde <span class="required">*</span></label>
                <input type="date" name="desde" class="form-control" 
                       value="<?php echo $filtro_desde; ?>" 
                       max="<?php echo date('Y-m-d'); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Hasta <span class="required">*</span></label>
                <input type="date" name="hasta" class="form-control" 
                       value="<?php echo $filtro_hasta; ?>" 
                       max="<?php echo date('Y-m-d'); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Proveedor</label>
                <select name="proveedor" class="form-control">
                    <option value="0">Todos los proveedores</option>
                    <?php while ($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?php echo $prov['id']; ?>" <?php echo ($filtro_proveedor == $prov['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prov['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn-primary" style="display: block; width: 100%;">🔍 Filtrar</button>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <a href="historial.php" class="btn-info" style="display: block; text-align: center;">🔄 Limpiar</a>
            </div>
        </div>
    </form>

    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Número Orden</th>
                    <th>Proveedor</th>
                    <th>Fecha Emisión</th>
                    <th>Fecha Entrega</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($orden = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($orden['numero_orden']); ?></strong></td>
                        <td><?php echo htmlspecialchars($orden['nombre_proveedor']); ?></td>
                        <td><?php echo formatearFecha($orden['fecha_emision']); ?></td>
                        <td><?php echo formatearFecha($orden['fecha_entrega_estimada']); ?></td>
                        <td><?php echo formatearMoneda($orden['total']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($orden['estado']); ?>">
                                <?php echo $orden['estado']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>📭 No hay órdenes en el rango de fechas seleccionado.</p>
            <p style="font-size: 0.875rem; color: #858796;">
                Período: <?php echo formatearFecha($filtro_desde); ?> al <?php echo formatearFecha($filtro_hasta); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
// Validar fechas antes de enviar
document.getElementById('formFiltros').addEventListener('submit', function(e) {
    const desde = new Date(document.querySelector('input[name="desde"]').value);
    const hasta = new Date(document.querySelector('input[name="hasta"]').value);
    
    if (hasta < desde) {
        e.preventDefault();
        alert('⚠️ La fecha final no puede ser anterior a la fecha inicial');
        return false;
    }
});

// Actualizar límite de fecha "hasta" cuando cambia "desde"
document.querySelector('input[name="desde"]').addEventListener('change', function() {
    document.querySelector('input[name="hasta"]').min = this.value;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>