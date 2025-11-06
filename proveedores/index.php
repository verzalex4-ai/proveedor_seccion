<?php
/**
 * Listado de Proveedores
 */

require_once '../config.php';

$page_title = 'Listado de Proveedores';
$page_heading = 'Gestión de Proveedores';

// Conectar a la base de datos
$conexion = conectarDB();

// Obtener todos los proveedores
$sql = "SELECT * FROM proveedores ORDER BY nombre ASC";
$resultado = $conexion->query($sql);

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h2>📋 Listado de Proveedores</h2>
        <a href="agregar.php" class="btn-success">➕ Agregar Nuevo Proveedor</a>
    </div>

    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Condiciones de Pago</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($proveedor = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $proveedor['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($proveedor['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($proveedor['contacto']); ?></td>
                        <td><?php echo htmlspecialchars($proveedor['email']); ?></td>
                        <td><?php echo htmlspecialchars($proveedor['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($proveedor['condiciones_pago']); ?></td>
                        <td>
                            <?php if ($proveedor['estado'] == 'Activo'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="editar.php?id=<?php echo $proveedor['id']; ?>" class="btn-primary btn-small">✏️ Editar</a>
                            <a href="eliminar.php?id=<?php echo $proveedor['id']; ?>" class="btn-danger btn-small" onclick="return confirm('¿Está seguro de eliminar este proveedor?')">🗑️ Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>No hay proveedores registrados en el sistema.</p>
            <a href="agregar.php" class="btn-primary">➕ Agregar el primer proveedor</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>