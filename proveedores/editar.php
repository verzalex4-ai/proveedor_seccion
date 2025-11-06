<?php
/**
 * Editar Proveedor
 */

require_once '../config.php';

$page_title = 'Editar Proveedor';
$page_heading = 'Editar Proveedor';

// Verificar si se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    mostrarMensaje('ID de proveedor no especificado', 'danger');
    header('Location: index.php');
    exit();
}

$id = intval($_GET['id']);
$conexion = conectarDB();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiarDatos($_POST['nombre']);
    $razon_social = limpiarDatos($_POST['razon_social']);
    $cuit = limpiarDatos($_POST['cuit']);
    $contacto = limpiarDatos($_POST['contacto']);
    $email = limpiarDatos($_POST['email']);
    $telefono = limpiarDatos($_POST['telefono']);
    $direccion = limpiarDatos($_POST['direccion']);
    $condiciones_pago = limpiarDatos($_POST['condiciones_pago']);
    $estado = $_POST['estado'];
    
    if (empty($nombre)) {
        mostrarMensaje('El nombre del proveedor es obligatorio', 'danger');
    } else {
        $sql = "UPDATE proveedores SET nombre=?, razon_social=?, cuit=?, contacto=?, email=?, telefono=?, direccion=?, condiciones_pago=?, estado=? WHERE id=?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssssi", $nombre, $razon_social, $cuit, $contacto, $email, $telefono, $direccion, $condiciones_pago, $estado, $id);
        
        if ($stmt->execute()) {
            mostrarMensaje('Proveedor actualizado exitosamente', 'success');
            $stmt->close();
            cerrarDB($conexion);
            header('Location: index.php');
            exit();
        } else {
            mostrarMensaje('Error al actualizar el proveedor: ' . $conexion->error, 'danger');
        }
        
        $stmt->close();
    }
}

// Obtener datos del proveedor
$sql = "SELECT * FROM proveedores WHERE id = ?";
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
cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>✏️ Editar Proveedor</h2>
        <p style="color: #858796; font-size: 0.875rem; margin: 5px 0 0 0;">
            ID: <?php echo $proveedor['id']; ?> | Registrado: <?php echo formatearFecha($proveedor['fecha_registro']); ?>
        </p>
    </div>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre del Proveedor <span class="required">*</span></label>
                <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
            </div>

            <div class="form-group">
                <label>Razón Social</label>
                <input type="text" name="razon_social" class="form-control" value="<?php echo htmlspecialchars($proveedor['razon_social']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>CUIT</label>
                <input type="text" name="cuit" class="form-control" value="<?php echo htmlspecialchars($proveedor['cuit']); ?>">
            </div>

            <div class="form-group">
                <label>Persona de Contacto</label>
                <input type="text" name="contacto" class="form-control" value="<?php echo htmlspecialchars($proveedor['contacto']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($proveedor['email']); ?>">
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($proveedor['telefono']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Dirección</label>
            <textarea name="direccion" class="form-control"><?php echo htmlspecialchars($proveedor['direccion']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Condiciones de Pago</label>
                <select name="condiciones_pago" class="form-control">
                    <option value="Contado" <?php echo ($proveedor['condiciones_pago'] == 'Contado') ? 'selected' : ''; ?>>Contado</option>
                    <option value="7 días" <?php echo ($proveedor['condiciones_pago'] == '7 días') ? 'selected' : ''; ?>>7 días</option>
                    <option value="15 días" <?php echo ($proveedor['condiciones_pago'] == '15 días') ? 'selected' : ''; ?>>15 días</option>
                    <option value="30 días" <?php echo ($proveedor['condiciones_pago'] == '30 días') ? 'selected' : ''; ?>>30 días</option>
                    <option value="60 días" <?php echo ($proveedor['condiciones_pago'] == '60 días') ? 'selected' : ''; ?>>60 días</option>
                    <option value="90 días" <?php echo ($proveedor['condiciones_pago'] == '90 días') ? 'selected' : ''; ?>>90 días</option>
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="Activo" <?php echo ($proveedor['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Inactivo" <?php echo ($proveedor['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">💾 Actualizar Proveedor</button>
            <a href="index.php" class="btn-danger">❌ Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>