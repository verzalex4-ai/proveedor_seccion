<?php
/**
 * Archivo de Configuración MEJORADO
 * Sistema de Gestión de Compras
 * Incluye: Auditoría, Seguridad, Validaciones
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_compras');

// Configuración de zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Configuración de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Configuración de errores (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Función para conectar a la base de datos
 * @return mysqli Retorna la conexión
 */
function conectarDB() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conexion->connect_error) {
        // No mostrar detalles del error en producción
        error_log("Error de conexión BD: " . $conexion->connect_error);
        die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
    }
    
    $conexion->set_charset("utf8mb4");
    return $conexion;
}

/**
 * Función para cerrar la conexión a la base de datos
 * @param mysqli $conexion
 */
function cerrarDB($conexion) {
    if ($conexion) {
        $conexion->close();
    }
}

/**
 * Función para sanitizar datos de entrada - MEJORADA
 * @param string $data
 * @return string
 */
function limpiarDatos($data) {
    if (is_null($data)) return null;
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Función para validar y limpiar entrada numérica
 * @param mixed $value
 * @param bool $allowNegative
 * @return float|null
 */
function limpiarNumero($value, $allowNegative = false) {
    if (empty($value) && $value !== 0 && $value !== '0') return null;
    
    $numero = floatval($value);
    
    if (!$allowNegative && $numero < 0) return null;
    
    return $numero;
}

/**
 * Función para mostrar mensajes de error o éxito
 * @param string $mensaje
 * @param string $tipo (success, danger, warning, info)
 */
function mostrarMensaje($mensaje, $tipo = 'info') {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = $tipo;
}

/**
 * Función para obtener y limpiar mensaje de sesión
 * @return array|null
 */
function obtenerMensaje() {
    if (isset($_SESSION['mensaje'])) {
        $mensaje = [
            'texto' => $_SESSION['mensaje'],
            'tipo' => $_SESSION['tipo_mensaje'] ?? 'info'
        ];
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
        return $mensaje;
    }
    return null;
}

/**
 * Función para formatear fecha
 * @param string $fecha
 * @return string
 */
function formatearFecha($fecha) {
    if (empty($fecha) || $fecha == '0000-00-00') return '';
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

/**
 * Función para formatear moneda argentina
 * @param float $monto
 * @return string
 */
function formatearMoneda($monto) {
    return '$' . number_format($monto, 2, ',', '.');
}

/**
 * NUEVA: Función para validar CUIT argentino
 * @param string $cuit
 * @return bool
 */
function validarCUITArgentino($cuit) {
    // Remover guiones y espacios
    $cuit = preg_replace('/[^0-9]/', '', $cuit);
    
    // Verificar longitud
    if (strlen($cuit) != 11) return false;
    
    // Algoritmo de validación de CUIT
    $multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    $suma = 0;
    
    for ($i = 0; $i < 10; $i++) {
        $suma += intval($cuit[$i]) * $multiplicadores[$i];
    }
    
    $resto = $suma % 11;
    $digito_verificador = $resto == 0 ? 0 : (11 - $resto);
    
    // Casos especiales
    if ($digito_verificador == 11) $digito_verificador = 0;
    if ($digito_verificador == 10) $digito_verificador = 9;
    
    return $digito_verificador == intval($cuit[10]);
}

/**
 * NUEVA: Función para protección contra CSRF
 * @return string
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * NUEVA: Función para validar token CSRF
 * @param string $token
 * @return bool
 */
function validarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * NUEVA: Función para prevenir SQL Injection adicional
 * @param mysqli $conexion
 * @param string $valor
 * @return string
 */
function escaparSQL($conexion, $valor) {
    return $conexion->real_escape_string($valor);
}

/**
 * NUEVA: Función para validar email
 * @param string $email
 * @return bool
 */
function validarEmail($email) {
    if (empty($email)) return true; // Campo opcional
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    
    // Validar dominio existe
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, "MX")) return false;
    
    return true;
}

/**
 * NUEVA: Función para validar teléfono argentino
 * @param string $telefono
 * @return bool
 */
function validarTelefonoArgentino($telefono) {
    if (empty($telefono)) return true; // Campo opcional
    
    // Patrón para números argentinos
    $patron = '/^(\+?54\s?)?(\(?\d{2,4}\)?[\s\-]?)?\d{6,8}$/';
    return preg_match($patron, $telefono);
}

/**
 * NUEVA: Función para logging de errores
 * @param string $mensaje
 * @param string $nivel (INFO, WARNING, ERROR, CRITICAL)
 */
function logError($mensaje, $nivel = 'ERROR') {
    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $log = "[{$fecha}] [{$nivel}] [IP: {$ip}] {$mensaje}\n";
    
    error_log($log, 3, __DIR__ . '/logs/errores.log');
}

/**
 * NUEVA: Función para generar número de orden único
 * @param mysqli $conexion
 * @return string
 */
function generarNumeroOrden($conexion) {
    $year = date('Y');
    $sql = "SELECT COUNT(*) as total FROM ordenes_compra WHERE YEAR(fecha_emision) = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'] + 1;
    $stmt->close();
    
    return "OC-" . $year . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * NUEVA: Función para verificar permisos de eliminación
 * @param mysqli $conexion
 * @param string $tabla
 * @param int $id
 * @return array [bool $puede_eliminar, string $mensaje]
 */
function verificarPermisoEliminacion($conexion, $tabla, $id) {
    switch ($tabla) {
        case 'proveedores':
            $sql = "SELECT COUNT(*) as total FROM ordenes_compra WHERE id_proveedor = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['total'];
            $stmt->close();
            
            if ($count > 0) {
                return [false, "No se puede eliminar: el proveedor tiene {$count} orden(es) asociada(s)"];
            }
            return [true, ''];
            
        case 'ordenes_compra':
            $sql = "SELECT estado FROM ordenes_compra WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $orden = $result->fetch_assoc();
            $stmt->close();
            
            if ($orden['estado'] == 'Recibida') {
                return [false, "No se puede eliminar: la orden ya fue recepcionada"];
            }
            return [true, ''];
            
        default:
            return [true, ''];
    }
}

/**
 * NUEVA: Función para calcular fecha de vencimiento de pago
 * @param string $fecha_base
 * @param string $condiciones_pago
 * @return string
 */
function calcularFechaVencimientoPago($fecha_base, $condiciones_pago) {
    $dias = 30; // Por defecto 30 días
    
    switch ($condiciones_pago) {
        case 'Contado':
            $dias = 0;
            break;
        case '7 días':
            $dias = 7;
            break;
        case '15 días':
            $dias = 15;
            break;
        case '30 días':
            $dias = 30;
            break;
        case '60 días':
            $dias = 60;
            break;
        case '90 días':
            $dias = 90;
            break;
    }
    
    return date('Y-m-d', strtotime($fecha_base . " + {$dias} days"));
}

// Crear directorio de logs si no existe
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}