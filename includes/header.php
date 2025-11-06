<?php
/**
 * Header Global
 * Incluir en todas las páginas después del PHP lógico
 */

// Detectar directorio actual para calcular rutas
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$base_path = ($current_dir == 'seccion_proveedor') ? '' : '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistema de Compras'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>styles.css">
</head>
<body>

<header class="navbar">
    <div class="logo">📦 Sección Proveedor</div>
    <div class="title"><?php echo $page_heading ?? 'Panel de Gestión'; ?></div>
</header>

<div class="main-container">

<?php include __DIR__ . '/sidebar.php'; ?>

<main class="main-content">

<?php
// Mostrar mensajes de sesión automáticamente
$mensaje = obtenerMensaje();
if ($mensaje):
?>
    <div class="alert alert-<?php echo $mensaje['tipo']; ?>">
        <?php echo $mensaje['texto']; ?>
    </div>
<?php endif; ?>