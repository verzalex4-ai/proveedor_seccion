<?php
/**
 * Listado y Seguimiento de Órdenes de Compra
 */

require_once '../config.php';

$page_title = 'Listado de Órdenes de Compra';
$page_heading = 'Órdenes de Compra';

$conexion = conectarDB();

// Filtros
$filtro_proveedor = isset($_GET['proveedor']) ? intval($_GET['proveedor']) : 0;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta SQL con filtros
$sql = "SELECT o.*, p.nombre as nombre_proveedor 
        FROM ordenes_compra o 
        INNER JOIN proveedores p ON o.id_proveedor = p.id 
        WHERE 1=1";

$params = [];
$types = "";

if ($filtro_proveedor > 0) {
    $sql .= " AND o.id_proveedor = ?";
    $params[] = $filtro_proveedor;
    $types .= "i";
}

if (!empty($filtro_estado)) {
    $sql .= " AND o.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

$sql .= " ORDER BY o.fecha_emision DESC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

// Obtener proveedores para el filtro
$sql_proveedores = "SELECT id, nombre FROM proveedores WHERE estado = 'Activo' ORDER BY nombre ASC";
$proveedores = $conexion->query($sql_proveedores);

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h2>📋 Listado de Órdenes de Compra</h2>
        <a href="crear.php" class="btn-success">➕ Crear Nueva Orden</a>
    </div>

    <form method="GET" class="filters-box">
        <div class="filter-row">
            <div class="form-group">
                <label>Filtrar por Proveedor</label>
                <select name="proveedor" class="form-control" onchange="this.form.submit()">
                    <option value="0">Todos los proveedores</option>
                    <?php while ($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?php echo $prov['id']; ?>" <?php echo ($filtro_proveedor == $prov['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prov['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Filtrar por Estado</label>
                <select name="estado" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="Pendiente" <?php echo ($filtro_estado == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="Enviada" <?php echo ($filtro_estado == 'Enviada') ? 'selected' : ''; ?>>Enviada</option>
                    <option value="Recibida" <?php echo ($filtro_estado == 'Recibida') ? 'selected' : ''; ?>>Recibida</option>
                    <option value="Cancelada" <?php echo ($filtro_estado == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <a href="index.php" class="btn-info" style="display: block; text-align: center;">🔄 Limpiar</a>
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
                    <th>Acciones</th>
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
                        <td style="white-space: nowrap;">
                            <a href="ver.php?id=<?php echo $orden['id']; ?>" class="btn-info btn-small">👁️ Ver</a>
                            <?php if ($orden['estado'] != 'Recibida' && $orden['estado'] != 'Cancelada'): ?>
                                <a href="recepcion.php?id=<?php echo $orden['id']; ?>" class="btn-success btn-small">✅ Recepcionar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>No hay órdenes de compra registradas con los filtros seleccionados.</p>
            <a href="crear.php" class="btn-primary">➕ Crear la primera orden</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>