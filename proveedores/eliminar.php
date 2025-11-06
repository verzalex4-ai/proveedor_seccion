<?php
/**
 * Eliminar Proveedor
 */

require_once '../config.php';

$page_title = 'Eliminar Proveedor';
$page_heading = 'Eliminar Proveedor';

// Verificar si se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    mostrarMensaje('ID de proveedor no especificado', 'danger');
    header('Location: index.php');
    exit();
}

$id = intval($_GET['id']);
$conexion = conectarDB();

// Verificar si el proveedor existe
$sql = "SELECT nombre FROM proveedores WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    mostrarMensaje('Proveedor no encontrado', 'danger');
    $stmt->close();
    cerrarDB($conexion);
    header('Location: index.php');
    exit();
}

$proveedor = $resultado->fetch_assoc();
$stmt->close();

// Verificar si hay órdenes asociadas
$sql_check = "SELECT COUNT(*) as total FROM ordenes_compra WHERE id_proveedor = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();
$ordenes = $resultado_check->fetch_assoc();
$stmt_check->close();

// Procesar eliminación si se confirma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {
    if ($ordenes['total'] > 0) {
        mostrarMensaje('No se puede eliminar el proveedor porque tiene órdenes de compra asociadas', 'danger');
    } else {
        $sql = "DELETE FROM proveedores WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            mostrarMensaje('Proveedor eliminado exitosamente', 'success');
            $stmt->close();
            cerrarDB($conexion);
            header('Location: index.php');
            exit();
        } else {
            mostrarMensaje('Error al eliminar el proveedor: ' . $conexion->error, 'danger');
        }
        
        $stmt->close();
    }
}

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>🗑️ Eliminar Proveedor</h2>
    </div>

    <?php if ($ordenes['total'] > 0): ?>
        <div class="alert alert-danger">
            <p><strong>⚠️ No se puede eliminar este proveedor</strong></p>
            <p>El proveedor tiene <strong><?php echo $ordenes['total']; ?> orden(es) de compra</strong> asociada(s).</p>
            <p>Debe eliminar primero todas las órdenes relacionadas o considerar cambiar el estado a "Inactivo".</p>
        </div>
        
        <div class="info-box">
            <div class="info-row">
                <strong>Proveedor:</strong>
                <span><?php echo htmlspecialchars($proveedor['nombre']); ?></span>
            </div>
        </div>

        <div class="form-actions">
            <a href="editar.php?id=<?php echo $id; ?>" class="btn-info">✏️ Editar Proveedor</a>
            <a href="index.php" class="btn-primary">← Volver al Listado</a>
        </div>
        
    <?php else: ?>
        <div class="alert alert-warning">
            <h3 style="margin: 0 0 10px 0;">⚠️ Confirmación de Eliminación</h3>
            <p style="margin: 5px 0;">¿Está seguro de que desea eliminar este proveedor?</p>
            <p style="margin: 5px 0;"><strong>Esta acción no se puede deshacer.</strong></p>
        </div>

        <div class="info-box">
            <div class="info-row">
                <strong>Proveedor:</strong>
                <span><?php echo htmlspecialchars($proveedor['nombre']); ?></span>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-actions">
                <button type="submit" name="confirmar" class="btn-danger">🗑️ Sí, Eliminar Proveedor</button>
                <a href="index.php" class="btn-primary">❌ Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>