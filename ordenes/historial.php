<?php
/**
 * Historial de Órdenes con Filtros
 */

require_once '../config.php';

$page_title = 'Historial de Órdenes';
$page_heading = 'Historial de Órdenes';

$conexion = conectarDB();

// Filtros
$filtro_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$filtro_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
$filtro_proveedor = isset($_GET['proveedor']) ? intval($_GET['proveedor']) : 0;

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

// Incluir header
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
    </div>

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
                <label>Proveedor</label>
                <select name="proveedor" class="form-control">
                    <option value="0">Todos</option>
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
                            <?php
                            $clase_badge = 'badge-' . strtolower($orden['estado']);
                            ?>
                            <span class="badge <?php echo $clase_badge; ?>"><?php echo $orden['estado']; ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>No hay órdenes en el rango de fechas seleccionado.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>