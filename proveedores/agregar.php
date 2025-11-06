<?php
/**
 * Agregar Proveedor - CON VALIDACIONES INTEGRADAS
 */

require_once '../config.php';

$page_title = 'Agregar Proveedor';
$page_heading = 'Agregar Proveedor';

$errores = [];
$valores = [];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conexion = conectarDB();
    
    // Obtener y limpiar datos
    $valores['nombre'] = limpiarDatos($_POST['nombre']);
    $valores['razon_social'] = limpiarDatos($_POST['razon_social']);
    $valores['cuit'] = limpiarDatos($_POST['cuit']);
    $valores['contacto'] = limpiarDatos($_POST['contacto']);
    $valores['email'] = limpiarDatos($_POST['email']);
    $valores['telefono'] = limpiarDatos($_POST['telefono']);
    $valores['direccion'] = limpiarDatos($_POST['direccion']);
    $valores['condiciones_pago'] = limpiarDatos($_POST['condiciones_pago']);
    $valores['estado'] = $_POST['estado'];
    
    // ========== VALIDACIONES ==========
    
    // 1. Nombre obligatorio
    if (empty($valores['nombre'])) {
        $errores['nombre'] = 'El nombre del proveedor es obligatorio';
    }
    
    // 2. Verificar nombre duplicado
    if (!empty($valores['nombre'])) {
        $sql_check = "SELECT id FROM proveedores WHERE LOWER(nombre) = LOWER(?)";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("s", $valores['nombre']);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errores['nombre'] = 'Ya existe un proveedor con ese nombre';
        }
        $stmt_check->close();
    }
    
    // 3. Validar CUIT (solo formato: 11 dígitos)
    if (!empty($valores['cuit'])) {
        $cuit_limpio = preg_replace('/[^0-9]/', '', $valores['cuit']);
        
        if (strlen($cuit_limpio) != 11) {
            $errores['cuit'] = 'El CUIT debe tener exactamente 11 dígitos';
        } else {
            // Verificar CUIT duplicado
            $sql_check = "SELECT id FROM proveedores WHERE cuit = ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("s", $valores['cuit']);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errores['cuit'] = 'Ya existe un proveedor con ese CUIT';
            }
            $stmt_check->close();
        }
    }
    
    // 4. Validar Email
    if (!empty($valores['email'])) {
        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El email ingresado no es válido';
        }
    }
    
    // 5. Validar Teléfono (formato argentino básico)
    if (!empty($valores['telefono'])) {
        $patron_telefono = '/^(\+?54\s?)?(\(?\d{2,4}\)?[\s\-]?)?\d{6,8}$/';
        if (!preg_match($patron_telefono, $valores['telefono'])) {
            $errores['telefono'] = 'Formato inválido. Ejemplo: 0387-4123456';
        }
    }
    
    // ========== INSERTAR SI NO HAY ERRORES ==========
    
    if (count($errores) == 0) {
        $sql = "INSERT INTO proveedores (nombre, razon_social, cuit, contacto, email, telefono, direccion, condiciones_pago, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssss", 
            $valores['nombre'], 
            $valores['razon_social'], 
            $valores['cuit'], 
            $valores['contacto'], 
            $valores['email'], 
            $valores['telefono'], 
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
            mostrarMensaje('❌ Error al agregar el proveedor: ' . $conexion->error, 'danger');
        }
        
        $stmt->close();
    } else {
        // Mostrar resumen de errores
        $mensaje_errores = '<strong>⚠️ Por favor corrija los siguientes errores:</strong><ul style="margin: 10px 0 0 20px;">';
        foreach ($errores as $error) {
            $mensaje_errores .= '<li>' . $error . '</li>';
        }
        $mensaje_errores .= '</ul>';
        mostrarMensaje($mensaje_errores, 'danger');
    }
    
    cerrarDB($conexion);
}

// JavaScript para validación en tiempo real (usa funciones de validaciones.js)
$extra_js = "
// Inicializar validaciones usando las funciones del archivo externo
validarNombre('input[name=\"nombre\"]', 'error-nombre');
validarCUIT('input[name=\"cuit\"]', 'error-cuit');
validarEmail('input[name=\"email\"]', 'error-email');
validarTelefono('input[name=\"telefono\"]', 'error-telefono');

// Validar antes de enviar el formulario
document.querySelector('form')?.addEventListener('submit', function(e) {
    let errores = [];
    
    // Validar nombre obligatorio
    const nombre = document.querySelector('input[name=\"nombre\"]').value.trim();
    if (!nombre) {
        errores.push('El nombre del proveedor es obligatorio');
    }
    
    // Validar CUIT si está lleno
    const cuit = document.querySelector('input[name=\"cuit\"]').value;
    if (cuit) {
        const cuitLimpio = cuit.replace(/[^0-9]/g, '');
        if (cuitLimpio.length !== 11) {
            errores.push('El CUIT debe tener exactamente 11 dígitos');
        }
    }
    
    // Validar email si está lleno
    const email = document.querySelector('input[name=\"email\"]').value;
    if (email) {
        const patronEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!patronEmail.test(email)) {
            errores.push('El email ingresado no es válido');
        }
    }
    
    // Validar teléfono si está lleno
    const telefono = document.querySelector('input[name=\"telefono\"]').value;
    if (telefono) {
        const patronTel = /^(\+?54\s?)?(\(?\d{2,4}\)?[\s\-]?)?\d{6,8}$/;
        if (!patronTel.test(telefono)) {
            errores.push('El teléfono tiene un formato inválido');
        }
    }
    
    // Si hay errores, prevenir envío
    if (errores.length > 0) {
        e.preventDefault();
        alert('⚠️ Por favor corrija los siguientes errores antes de guardar:\\n\\n• ' + errores.join('\\n• '));
        return false;
    }
});
";

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2>➕ Agregar Nuevo Proveedor</h2>
    </div>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre del Proveedor <span class="required">*</span></label>
                <input type="text" 
                       name="nombre" 
                       class="form-control <?php echo isset($errores['nombre']) ? 'error' : ''; ?>" 
                       required 
                       maxlength="200"
                       placeholder="Ej: Proveedor ABC S.A."
                       value="<?php echo isset($valores['nombre']) ? htmlspecialchars($valores['nombre']) : ''; ?>">
                <span id="error-nombre" class="error-message" style="display: <?php echo isset($errores['nombre']) ? 'block' : 'none'; ?>;">
                    <?php echo isset($errores['nombre']) ? '✗ ' . $errores['nombre'] : ''; ?>
                </span>
            </div>

            <div class="form-group">
                <label>Razón Social</label>
                <input type="text" 
                       name="razon_social" 
                       class="form-control" 
                       maxlength="200"
                       placeholder="Razón social completa"
                       value="<?php echo isset($valores['razon_social']) ? htmlspecialchars($valores['razon_social']) : ''; ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>CUIT</label>
                <input type="text" 
                       name="cuit" 
                       class="form-control <?php echo isset($errores['cuit']) ? 'error' : ''; ?>" 
                       placeholder="20123456789 (11 dígitos)"
                       maxlength="13"
                       value="<?php echo isset($valores['cuit']) ? htmlspecialchars($valores['cuit']) : ''; ?>">
                <span id="error-cuit" class="error-message" style="display: <?php echo isset($errores['cuit']) ? 'block' : 'none'; ?>;">
                    <?php echo isset($errores['cuit']) ? '✗ ' . $errores['cuit'] : ''; ?>
                </span>
                <small style="color: #858796; font-size: 0.75rem; display: block; margin-top: 3px;">Puede incluir guiones: 20-12345678-9</small>
            </div>

            <div class="form-group">
                <label>Persona de Contacto</label>
                <input type="text" 
                       name="contacto" 
                       class="form-control" 
                       maxlength="100"
                       placeholder="Nombre del contacto"
                       value="<?php echo isset($valores['contacto']) ? htmlspecialchars($valores['contacto']) : ''; ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" 
                       name="email" 
                       class="form-control <?php echo isset($errores['email']) ? 'error' : ''; ?>" 
                       maxlength="100"
                       placeholder="email@proveedor.com"
                       value="<?php echo isset($valores['email']) ? htmlspecialchars($valores['email']) : ''; ?>">
                <span id="error-email" class="error-message" style="display: <?php echo isset($errores['email']) ? 'block' : 'none'; ?>;">
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
                <span id="error-telefono" class="error-message" style="display: <?php echo isset($errores['telefono']) ? 'block' : 'none'; ?>;">
                    <?php echo isset($errores['telefono']) ? '✗ ' . $errores['telefono'] : ''; ?>
                </span>
                <small style="color: #858796; font-size: 0.75rem; display: block; margin-top: 3px;">Ej: 0387-4123456 o 387-4123456</small>
            </div>
        </div>

        <div class="form-group">
            <label>Dirección</label>
            <textarea name="direccion" 
                      class="form-control" 
                      maxlength="500"
                      placeholder="Dirección completa del proveedor"><?php echo isset($valores['direccion']) ? htmlspecialchars($valores['direccion']) : ''; ?></textarea>
            <small style="color: #858796; font-size: 0.75rem;">Máximo 500 caracteres</small>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>