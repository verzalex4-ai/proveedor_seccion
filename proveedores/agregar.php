<?php
/**
 * Agregar Proveedor - VERSIÓN FINAL CORREGIDA
 * proveedores/agregar.php
 */

require_once '../config.php';

$page_title = 'Agregar Proveedor';
$page_heading = 'Agregar Proveedor';

$errores = [];
$valores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conexion = conectarDB();
    
    // Obtener y limpiar datos
    $valores['nombre'] = limpiarDatos($_POST['nombre']);
    $valores['razon_social'] = limpiarDatos($_POST['razon_social']);
    $valores['cuit'] = trim($_POST['cuit']);
    $valores['contacto'] = limpiarDatos($_POST['contacto']);
    $valores['email'] = trim($_POST['email']);
    $valores['telefono'] = limpiarDatos($_POST['telefono']);
    $valores['direccion'] = limpiarDatos($_POST['direccion']);
    $valores['condiciones_pago'] = $_POST['condiciones_pago'];
    $valores['estado'] = $_POST['estado'];
    
    // ========== VALIDACIONES DE FORMATO Y OBLIGATORIEDAD (Lado del Servidor) ==========
    
    // 1. Nombre obligatorio y formato (CORREGIDO: Excluye números '0-9')
    if (empty($valores['nombre'])) {
        $errores['nombre'] = 'El nombre del proveedor es obligatorio';
    } elseif (strlen($valores['nombre']) < 3) {
        $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    // Se elimina el '0-9' de la RegEx para forzar solo letras/símbolos
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-\.]+$/u", $valores['nombre'])) { 
        $errores['nombre'] = 'El nombre solo puede contener letras, espacios, puntos y guiones';
    } else {
        // Verificar nombre duplicado (Solo si el formato es correcto)
        $sql_check = "SELECT id FROM proveedores WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))";
        $stmt_check = $conexion->prepare($sql_check);
        $nombre_check = trim($valores['nombre']);
        $stmt_check->bind_param("s", $nombre_check);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errores['nombre'] = 'Ya existe un proveedor con ese nombre';
        }
        $stmt_check->close();
    }
    
    // 2. Razón Social (Mantenido: Permite letras y números)
    if (!empty($valores['razon_social']) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]+$/u", $valores['razon_social'])) {
        $errores['razon_social'] = 'La Razón Social solo puede contener letras, números, espacios, puntos y guiones.';
    }
    
    // 3. Contacto (Mantenido: Solo letras y espacios)
    if (!empty($valores['contacto']) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $valores['contacto'])) {
        $errores['contacto'] = 'El nombre de contacto solo puede contener letras y espacios.';
    }
    
    // 4. Validar CUIT (Mantenido)
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
    
    // 5. Validar Email (Mantenido)
    if (!empty($valores['email'])) {
        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El formato del email no es válido';
        } elseif (strlen($valores['email']) > 100) {
            $errores['email'] = 'El email es demasiado largo (máximo 100 caracteres)';
        }
    } else {
        $valores['email'] = null;
    }
    
    // 6. Validar Teléfono (Mantenido)
    if (!empty($valores['telefono'])) {
        $patron_telefono = '/^(\+?54\s?)?(\(?\d{2,4}\)?[\s\-]?)?\d{6,8}$/';
        if (!preg_match($patron_telefono, $valores['telefono'])) {
            $errores['telefono'] = 'Formato inválido. Ejemplos: 0387-4123456 o +54 387 4123456';
        }
    } else {
        $valores['telefono'] = null;
    }
    
    // ========== VERIFICACIONES DE DUPLICADOS (Optimizadas) - Mantenido ==========
    
    // Verificar Razón Social Duplicada
    if (!isset($errores['razon_social']) && !empty($valores['razon_social'])) {
        $sql_check = "SELECT id FROM proveedores WHERE LOWER(TRIM(razon_social)) = LOWER(TRIM(?))";
        $stmt_check = $conexion->prepare($sql_check);
        $razon_social_check = trim($valores['razon_social']);
        $stmt_check->bind_param("s", $razon_social_check);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errores['razon_social'] = 'Ya existe un proveedor con esa Razón Social.';
        }
        $stmt_check->close();
    }
    
    // Verificar CUIT duplicado
    if (!isset($errores['cuit']) && !empty($valores['cuit'])) {
        $sql_check = "SELECT id FROM proveedores WHERE cuit = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("s", $valores['cuit']);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errores['cuit'] = 'Ya existe un proveedor con ese CUIT';
        }
        $stmt_check->close();
    }
    
    // Verificar Email duplicado
    if (!isset($errores['email']) && !empty($valores['email'])) {
        $sql_check = "SELECT id FROM proveedores WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))";
        $stmt_check = $conexion->prepare($sql_check);
        $email_check = trim($valores['email']);
        $stmt_check->bind_param("s", $email_check);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errores['email'] = 'Ya existe un proveedor registrado con este Email.';
        }
        $stmt_check->close();
    }
    
    // Verificar Teléfono duplicado
    if (!isset($errores['telefono']) && !empty($valores['telefono'])) {
        $sql_check = "SELECT id FROM proveedores WHERE telefono = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("s", $valores['telefono']);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errores['telefono'] = 'Ya existe un proveedor registrado con este número de Teléfono.';
        }
        $stmt_check->close();
    }
    
    // ========== INSERTAR SI NO HAY ERRORES ==========
    
    if (count($errores) == 0) {
        // Asegurar que los campos vacíos se envíen como NULL (si la BD lo permite)
        $cuit_db = !empty($valores['cuit']) ? $valores['cuit'] : null;
        $email_db = !empty($valores['email']) ? $valores['email'] : null;
        $telefono_db = !empty($valores['telefono']) ? $valores['telefono'] : null;
        
        $sql = "INSERT INTO proveedores (nombre, razon_social, cuit, contacto, email, telefono, direccion, condiciones_pago, estado) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        
        $stmt->bind_param("sssssssss", 
            $valores['nombre'], 
            $valores['razon_social'], 
            $cuit_db,
            $valores['contacto'], 
            $email_db,
            $telefono_db,
            $valores['direccion'], 
            $valores['condiciones_pago'], 
            $valores['estado']
        );
        
        if ($stmt->execute()) {
            mostrarMensaje('✅ Proveedor agregado exitosamente', 'success');
            $stmt->close();
            cerrarDB($conexion);
            header('Location: index.php');
            exit();
        } else {
            mostrarMensaje('❌ Error al agregar el proveedor. Inténtelo de nuevo. Detalles: ' . $conexion->error, 'danger');
        }
    } else {
        // Mostrar resumen de errores
        $mensaje_errores = '<strong>⚠️ Corrija los siguientes errores:</strong><ul style="margin: 10px 0 0 20px;">';
        foreach ($errores as $error) {
            $mensaje_errores .= '<li>' . $error . '</li>';
        }
        $mensaje_errores .= '</ul>';
        mostrarMensaje($mensaje_errores, 'danger');
    }
    
    cerrarDB($conexion); 
}

// **CAMBIO 2: ELIMINACIÓN DEL BLOQUE $extra_js COMPLETO**
// Todo el JavaScript en el archivo original (la variable $extra_js) ha sido ELIMINADO
// y debe ser reemplazado por la inclusión de validaciones.js y sus llamadas.

require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>➕ Agregar Nuevo Proveedor</h2>
    </div>

    <form method="POST" action="" id="form-proveedor"> <div class="form-row">
            <div class="form-group">
                <label>Nombre del Proveedor <span class="required">*</span></label>
                <input type="text" 
                        name="nombre" 
                        class="form-control <?php echo isset($errores['nombre']) ? 'error' : ''; ?>" 
                        required 
                        minlength="3"
                        maxlength="200"
                        placeholder="Ej: Distribuidora ABC"
                        value="<?php echo isset($valores['nombre']) ? htmlspecialchars($valores['nombre']) : ''; ?>">
                <span class="error-message" id="error-nombre" style="display: <?php echo isset($errores['nombre']) ? 'block' : 'none'; ?>; color: #e74a3b;">
                    <?php echo isset($errores['nombre']) ? '✗ ' . $errores['nombre'] : ''; ?>
                </span>
            </div>

            <div class="form-group">
                <label>Razón Social</label>
                <input type="text" 
                        name="razon_social" 
                        class="form-control <?php echo isset($errores['razon_social']) ? 'error' : ''; ?>" 
                        maxlength="200"
                        placeholder="Razón social completa"
                        value="<?php echo isset($valores['razon_social']) ? htmlspecialchars($valores['razon_social']) : ''; ?>">
                <span class="error-message" id="error-razon-social" style="display: <?php echo isset($errores['razon_social']) ? 'block' : 'none'; ?>; color: #e74a3b;">
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
                        placeholder="20-12345678-9 (11 dígitos)"
                        pattern="[0-9\-]{11,13}"
                        maxlength="13"
                        value="<?php echo isset($valores['cuit']) ? htmlspecialchars($valores['cuit']) : ''; ?>">
                <span class="error-message" id="error-cuit" style="display: <?php echo isset($errores['cuit']) ? 'block' : 'none'; ?>; color: #e74a3b;">
                    <?php echo isset($errores['cuit']) ? '✗ ' . $errores['cuit'] : ''; ?>
                </span>
                <small style="color: #858796; font-size: 0.75rem; display: block; margin-top: 3px;">Solo números y guiones. Opcional.</small>
            </div>

            <div class="form-group">
                <label>Persona de Contacto</label>
                <input type="text" 
                        name="contacto" 
                        class="form-control <?php echo isset($errores['contacto']) ? 'error' : ''; ?>" 
                        maxlength="100"
                        placeholder="Juan Pérez"
                        value="<?php echo isset($valores['contacto']) ? htmlspecialchars($valores['contacto']) : ''; ?>">
                <span class="error-message" id="error-contacto" style="display: <?php echo isset($errores['contacto']) ? 'block' : 'none'; ?>; color: #e74a3b;">
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
                        placeholder="contacto@proveedor.com"
                        value="<?php echo isset($valores['email']) ? htmlspecialchars($valores['email']) : ''; ?>">
                <span class="error-message" id="error-email" style="display: <?php echo isset($errores['email']) ? 'block' : 'none'; ?>; color: #e74a3b;">
                    <?php echo isset($errores['email']) ? '✗ ' . $errores['email'] : ''; ?>
                </span>
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" 
                        name="telefono" 
                        class="form-control <?php echo isset($errores['telefono']) ? 'error' : ''; ?>" 
                        maxlength="20"
                        placeholder="0387-4123456"
                        value="<?php echo isset($valores['telefono']) ? htmlspecialchars($valores['telefono']) : ''; ?>">
                <span class="error-message" id="error-telefono" style="display: <?php echo isset($errores['telefono']) ? 'block' : 'none'; ?>; color: #e74a3b;">
                    <?php echo isset($errores['telefono']) ? '✗ ' . $errores['telefono'] : ''; ?>
                </span>
            </div>
        </div>

        <div class="form-group">
            <label>Dirección</label>
            <textarea name="direccion" 
                        class="form-control" 
                        maxlength="500"
                        placeholder="Dirección completa del proveedor"><?php echo isset($valores['direccion']) ? htmlspecialchars($valores['direccion']) : ''; ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Condiciones de Pago</label>
                <select name="condiciones_pago" class="form-control">
                    <option value="Contado" <?php echo (isset($valores['condiciones_pago']) && $valores['condiciones_pago'] == 'Contado') ? 'selected' : ''; ?>>Contado</option>
                    <option value="7 días" <?php echo (isset($valores['condiciones_pago']) && $valores['condiciones_pago'] == '7 días') ? 'selected' : ''; ?>>7 días</option>
                    <option value="15 días" <?php echo (isset($valores['condiciones_pago']) && $valores['condiciones_pago'] == '15 días') ? 'selected' : ''; ?>>15 días</option>
                    <option value="30 días" <?php echo (!isset($valores['condiciones_pago']) || $valores['condiciones_pago'] == '30 días') ? 'selected' : ''; ?>>30 días</option>
                    <option value="60 días" <?php echo (isset($valores['condiciones_pago']) && $valores['condiciones_pago'] == '60 días') ? 'selected' : ''; ?>>60 días</option>
                    <option value="90 días" <?php echo (isset($valores['condiciones_pago']) && $valores['condiciones_pago'] == '90 días') ? 'selected' : ''; ?>>90 días</option>
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="Activo" <?php echo (!isset($valores['estado']) || $valores['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Inactivo" <?php echo (isset($valores['estado']) && $valores['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-success">💾 Guardar Proveedor</button>
            <a href="index.php" class="btn-danger">❌ Cancelar</a>
        </div>
    </form>
</div>

<script src="../js/validaciones.js"></script> <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializa las validaciones de front-end
        validarNombre('input[name="nombre"]', 'error-nombre'); 
        validarRazonSocial('input[name="razon_social"]', 'error-razon-social'); 
        validarCUIT('input[name="cuit"]', 'error-cuit'); 
        validarContacto('input[name="contacto"]', 'error-contacto'); 
        validarEmail('input[name="email"]', 'error-email'); 
        validarTelefono('input[name="telefono"]', 'error-telefono'); 
        
        // Puedes añadir aquí la lógica de validación general del formulario si es necesario, 
        // similar a la que eliminamos, usando las funciones de validaciones.js.
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>