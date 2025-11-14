<?php
/**
 * Editar Proveedor - COMPLETAMENTE CORREGIDO
 * Validaciones completas y control de duplicados
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

$errores = [];
$valores = [];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener y limpiar datos
    $valores['nombre'] = trim(limpiarDatos($_POST['nombre']));
    $valores['razon_social'] = trim(limpiarDatos($_POST['razon_social']));
    $valores['cuit'] = trim($_POST['cuit']);
    $valores['contacto'] = trim(limpiarDatos($_POST['contacto']));
    $valores['email'] = trim($_POST['email']);
    $valores['telefono'] = trim(limpiarDatos($_POST['telefono']));
    $valores['direccion'] = trim(limpiarDatos($_POST['direccion']));
    $valores['condiciones_pago'] = $_POST['condiciones_pago'];
    $valores['estado'] = $_POST['estado'];
    
    // ========== VALIDACIONES ==========
    
    // 1. Nombre (OBLIGATORIO, solo letras)
    if (empty($valores['nombre'])) {
        $errores['nombre'] = 'El nombre del proveedor es obligatorio';
    } elseif (strlen($valores['nombre']) < 3) {
        $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-\.]+$/u", $valores['nombre'])) { 
        $errores['nombre'] = 'El nombre solo puede contener letras, espacios, puntos y guiones';
    }
    
    // 2. Razón Social (Opcional, permite números)
    if (!empty($valores['razon_social']) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]+$/u", $valores['razon_social'])) {
        $errores['razon_social'] = 'La Razón Social contiene caracteres inválidos';
    }
    
    // 3. Contacto (Opcional, solo letras)
    if (!empty($valores['contacto']) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $valores['contacto'])) {
        $errores['contacto'] = 'El nombre de contacto solo puede contener letras y espacios';
    }
    
    // 4. CUIT (Opcional, 11 dígitos)
    if (!empty($valores['cuit'])) {
        $cuit_limpio = preg_replace('/[^0-9]/', '', $valores['cuit']);
        if (strlen($cuit_limpio) != 11) {
            $errores['cuit'] = 'El CUIT debe tener exactamente 11 dígitos';
        } else {
            $valores['cuit'] = $cuit_limpio;
        }
    } else {
        $valores['cuit'] = null;
    }
    
    // 5. Email (Opcional, formato válido)
    if (!empty($valores['email'])) {
        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El formato del email no es válido';
        } elseif (strlen($valores['email']) > 100) {
            $errores['email'] = 'El email es demasiado largo (máximo 100 caracteres)';
        }
    } else {
        $valores['email'] = null;
    }
    
    // 6. Teléfono (Opcional, formato argentino)
    if (!empty($valores['telefono'])) {
        $patron_telefono = '/^(\+?54\s?)?(\(?\d{2,4}\)?[\s\-]?)?\d{6,8}$/';
        if (!preg_match($patron_telefono, $valores['telefono'])) {
            $errores['telefono'] = 'Formato inválido. Ejemplos: 0387-4123456 o +54 387 4123456';
        }
    } else {
        $valores['telefono'] = null;
    }
    
    // ========== VERIFICAR DUPLICADOS (EXCLUYENDO EL ACTUAL) ==========
    
    // Nombre duplicado
    if (!isset($errores['nombre'])) {
        $sql_check = "SELECT id FROM proveedores WHERE LOWER(TRIM(nombre)) = LOWER(?) AND id != ?";
        $stmt = $conexion->prepare($sql_check);
        $stmt->bind_param("si", $valores['nombre'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errores['nombre'] = 'Ya existe otro proveedor con ese nombre';
        }
        $stmt->close();
    }
    
    // Razón Social duplicada
    if (!isset($errores['razon_social']) && !empty($valores['razon_social'])) {
        $sql_check = "SELECT id FROM proveedores WHERE LOWER(TRIM(razon_social)) = LOWER(?) AND id != ?";
        $stmt = $conexion->prepare($sql_check);
        $stmt->bind_param("si", $valores['razon_social'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errores['razon_social'] = 'Ya existe otro proveedor con esa Razón Social';
        }
        $stmt->close();
    }
    
    // CUIT duplicado
    if (!isset($errores['cuit']) && !empty($valores['cuit'])) {
        $sql_check = "SELECT id FROM proveedores WHERE cuit = ? AND id != ?";
        $stmt = $conexion->prepare($sql_check);
        $stmt->bind_param("si", $valores['cuit'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errores['cuit'] = 'Ya existe otro proveedor con ese CUIT';
        }
        $stmt->close();
    }
    
    // Email duplicado
    if (!isset($errores['email']) && !empty($valores['email'])) {
        $sql_check = "SELECT id FROM proveedores WHERE LOWER(TRIM(email)) = LOWER(?) AND id != ?";
        $stmt = $conexion->prepare($sql_check);
        $stmt->bind_param("si", $valores['email'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errores['email'] = 'Ya existe otro proveedor con este Email';
        }
        $stmt->close();
    }
    
    // Teléfono duplicado
    if (!isset($errores['telefono']) && !empty($valores['telefono'])) {
        $sql_check = "SELECT id FROM proveedores WHERE telefono = ? AND id != ?";
        $stmt = $conexion->prepare($sql_check);
        $stmt->bind_param("si", $valores['telefono'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errores['telefono'] = 'Ya existe otro proveedor con este Teléfono';
        }
        $stmt->close();
    }
    
    // ========== ACTUALIZAR SI NO HAY ERRORES ==========
    
    if (count($errores) == 0) {
        $sql = "UPDATE proveedores SET nombre=?, razon_social=?, cuit=?, contacto=?, email=?, telefono=?, direccion=?, condiciones_pago=?, estado=? WHERE id=?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssssi", 
            $valores['nombre'], 
            $valores['razon_social'], 
            $valores['cuit'], 
            $valores['contacto'], 
            $valores['email'], 
            $valores['telefono'], 
            $valores['direccion'], 
            $valores['condiciones_pago'], 
            $valores['estado'], 
            $id
        );
        
        if ($stmt->execute()) {
            mostrarMensaje('✅ Proveedor actualizado exitosamente: ' . $valores['nombre'], 'success');
            $stmt->close();
            cerrarDB($conexion);
            header('Location: index.php');
            exit();
        } else {
            if (strpos($stmt->error, 'email') !== false) {
                $errores['email'] = 'El email ya está registrado';
            } elseif (strpos($stmt->error, 'telefono') !== false) {
                $errores['telefono'] = 'El teléfono ya está registrado';
            } elseif (strpos($stmt->error, 'cuit') !== false) {
                $errores['cuit'] = 'El CUIT ya está registrado';
            } else {
                mostrarMensaje('❌ Error al actualizar: ' . $stmt->error, 'danger');
            }
        }
        
        $stmt->close();
    }
    
    // Mostrar errores
    if (count($errores) > 0) {
        $mensaje_errores = '<strong>⚠️ Corrija los siguientes errores:</strong><ul style="margin: 10px 0 0 20px;">';
        foreach ($errores as $campo => $error) {
            $mensaje_errores .= '<li><strong>' . ucfirst($campo) . ':</strong> ' . $error . '</li>';
        }
        $mensaje_errores .= '</ul>';
        mostrarMensaje($mensaje_errores, 'danger');
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

// Si hay valores POST, usarlos (para mantener datos en caso de error)
if (!empty($valores)) {
    $proveedor = array_merge($proveedor, $valores);
}

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
                <input type="text" 
                       name="nombre" 
                       class="form-control <?php echo isset($errores['nombre']) ? 'error' : ''; ?>" 
                       required
                       minlength="3"
                       maxlength="200"
                       value="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
                <span class="error-message" id="error-nombre" style="display: <?php echo isset($errores['nombre']) ? 'block' : 'none'; ?>; color: #e74a3b; font-size: 0.75rem; margin-top: 3px;">
                    <?php echo isset($errores['nombre']) ? '✗ ' . $errores['nombre'] : ''; ?>
                </span>
            </div>

            <div class="form-group">
                <label>Razón Social</label>
                <input type="text" 
                       name="razon_social" 
                       class="form-control <?php echo isset($errores['razon_social']) ? 'error' : ''; ?>"
                       maxlength="200"
                       value="<?php echo htmlspecialchars($proveedor['razon_social']); ?>">
                <span class="error-message" id="error-razon-social" style="display: <?php echo isset($errores['razon_social']) ? 'block' : 'none'; ?>; color: #e74a3b; font-size: 0.75rem; margin-top: 3px;">
                    <?php echo isset($errores['razon_social']) ? '✗ ' . $errores['razon_social'] : ''; ?>
                </span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>CUIT</label>
                <input type="text" 
                       name="cuit" 
                       class="form-control <?php echo isset($errores['cuit']) ? 'error' : ''; ?>"
                       maxlength="13"
                       value="<?php echo htmlspecialchars($proveedor['cuit']); ?>">
                <span class="error-message" id="error-cuit" style="display: <?php echo isset($errores['cuit']) ? 'block' : 'none'; ?>; color: #e74a3b; font-size: 0.75rem; margin-top: 3px;">
                    <?php echo isset($errores['cuit']) ? '✗ ' . $errores['cuit'] : ''; ?>
                </span>
            </div>

            <div class="form-group">
                <label>Persona de Contacto</label>
                <input type="text" 
                       name="contacto" 
                       class="form-control <?php echo isset($errores['contacto']) ? 'error' : ''; ?>"
                       maxlength="100"
                       value="<?php echo htmlspecialchars($proveedor['contacto']); ?>">
                <span class="error-message" id="error-contacto" style="display: <?php echo isset($errores['contacto']) ? 'block' : 'none'; ?>; color: #e74a3b; font-size: 0.75rem; margin-top: 3px;">
                    <?php echo isset($errores['contacto']) ? '✗ ' . $errores['contacto'] : ''; ?>
                </span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" 
                       name="email" 
                       class="form-control <?php echo isset($errores['email']) ? 'error' : ''; ?>"
                       maxlength="100"
                       value="<?php echo htmlspecialchars($proveedor['email']); ?>">
                <span class="error-message" id="error-email" style="display: <?php echo isset($errores['email']) ? 'block' : 'none'; ?>; color: #e74a3b; font-size: 0.75rem; margin-top: 3px;">
                    <?php echo isset($errores['email']) ? '✗ ' . $errores['email'] : ''; ?>
                </span>
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" 
                       name="telefono" 
                       class="form-control <?php echo isset($errores['telefono']) ? 'error' : ''; ?>"
                       maxlength="20"
                       value="<?php echo htmlspecialchars($proveedor['telefono']); ?>">
                <span class="error-message" id="error-telefono" style="display: <?php echo isset($errores['telefono']) ? 'block' : 'none'; ?>; color: #e74a3b; font-size: 0.75rem; margin-top: 3px;">
                    <?php echo isset($errores['telefono']) ? '✗ ' . $errores['telefono'] : ''; ?>
                </span>
            </div>
        </div>

        <div class="form-group">
            <label>Dirección</label>
            <textarea name="direccion" 
                      class="form-control"
                      maxlength="500"><?php echo htmlspecialchars($proveedor['direccion']); ?></textarea>
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

<script src="../assets/js/validaciones.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validaciones en tiempo real
    validarNombre('input[name="nombre"]', 'error-nombre');
    validarRazonSocial('input[name="razon_social"]', 'error-razon-social');
    validarCUIT('input[name="cuit"]', 'error-cuit');
    validarContacto('input[name="contacto"]', 'error-contacto');
    validarEmail('input[name="email"]', 'error-email');
    validarTelefono('input[name="telefono"]', 'error-telefono');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>