<?php
/**
 * Condiciones Comerciales por Proveedor
 */

require_once '../config.php';

$page_title = 'Condiciones de Pago';
$page_heading = 'Condiciones de Pago';

$conexion = conectarDB();

// Obtener proveedores con sus condiciones
$sql = "SELECT 
            p.*,
            COUNT(o.id) as total_ordenes,
            COALESCE(SUM(o.total), 0) as monto_total_ordenes
        FROM proveedores p
        LEFT JOIN ordenes_compra o ON p.id = o.id_proveedor
        WHERE p.estado = 'Activo'
        GROUP BY p.id
        ORDER BY p.nombre ASC";

$resultado = $conexion->query($sql);

cerrarDB($conexion);

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h2>📋 Condiciones Comerciales por Proveedor</h2>
    </div>

    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <?php while ($proveedor = $resultado->fetch_assoc()): ?>
            <div style="background-color: #f8f9fc; padding: 20px; border-radius: 0.35rem; margin-bottom: 15px; border-left: 4px solid #4e73df;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <div style="font-size: 1.1rem; font-weight: bold; color: #4e73df; margin-bottom: 5px;">
                            <?php echo htmlspecialchars($proveedor['nombre']); ?>
                        </div>
                        <div style="font-size: 0.875rem; color: #858796;">
                            📧 <?php echo htmlspecialchars($proveedor['email']); ?> | 
                            📞 <?php echo htmlspecialchars($proveedor['telefono']); ?>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; padding: 15px 0; border-top: 1px solid #e3e6f0;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: #858796; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">
                            Condiciones de Pago
                        </div>
                        <div style="font-size: 1.3rem; font-weight: bold; color: #4e73df;">
                            <?php echo htmlspecialchars($proveedor['condiciones_pago']); ?>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: #858796; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">
                            Persona de Contacto
                        </div>
                        <div style="font-size: 1.1rem; font-weight: bold; color: #5a5c69;">
                            <?php echo htmlspecialchars($proveedor['contacto']); ?>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: #858796; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">
                            Total de Órdenes
                        </div>
                        <div style="font-size: 1.1rem; font-weight: bold; color: #5a5c69;">
                            <?php echo $proveedor['total_ordenes']; ?>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: #858796; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">
                            Monto Total
                        </div>
                        <div style="font-size: 1.1rem; font-weight: bold; color: #5a5c69;">
                            <?php echo formatearMoneda($proveedor['monto_total_ordenes']); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-data">
            <p>No hay proveedores activos registrados.</p>
            <a href="../proveedores/agregar.php" class="btn-primary">➕ Agregar Proveedor</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>