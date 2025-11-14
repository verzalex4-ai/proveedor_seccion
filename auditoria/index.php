<?php
/**
 * Historial de Auditoría del Sistema
 * Visualizar todos los cambios registrados
 */

require_once '../config.php';

$page_title = 'Auditoría del Sistema';
$page_heading = 'Historial de Auditoría';

$conexion = conectarDB();

// Filtros
$filtro_tabla = isset($_GET['tabla']) ? $_GET['tabla'] : '';
$filtro_accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$filtro_desde = isset($_GET['desde']) && !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-d', strtotime('-30 days'));
$filtro_hasta = isset($_GET['hasta']) && !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

// Paginación
$por_pagina = 50;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir consulta
$sql = "SELECT * FROM auditoria WHERE fecha BETWEEN ? AND ?";
$params = [$filtro_desde . ' 00:00:00', $filtro_hasta . ' 23:59:59'];
$types = "ss";

if (!empty($filtro_tabla)) {
    $sql .= " AND tabla = ?";
    $params[] = $filtro_tabla;
    $types .= "s";
}

if (!empty($filtro_accion)) {
    $sql .= " AND accion = ?";
    $params[] = $filtro_accion;
    $types .= "s";
}

// Contar total de registros
$sql_count = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$stmt_count = $conexion->prepare($sql_count);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_paginas = ceil($total_registros / $por_pagina);

// Obtener registros de la página actual
$sql .= " ORDER BY fecha DESC LIMIT ? OFFSET ?";
$params[] = $por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

// Obtener tablas disponibles
$sql_tablas = "SELECT DISTINCT tabla FROM auditoria ORDER BY tabla";
$tablas = $conexion->query($sql_tablas);

cerrarDB($conexion);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;">
    <div class="stat-card primary">
        <p class="stat-label">Total Registros</p>
        <p class="stat-value"><?php echo number_format($total_registros); ?></p>
    </div>
    <div class="stat-card info">
        <p class="stat-label">Página Actual</p>
        <p class="stat-value"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></p>
    </div>
    <div class="stat-card success">
        <p class="stat-label">Período</p>
        <p class="stat-value" style="font-size: 0.9rem;"><?php echo formatearFecha($filtro_desde); ?></p>
    </div>
    <div class="stat-card warning">
        <p class="stat-label">Hasta</p>
        <p class="stat-value" style="font-size: 0.9rem;"><?php echo formatearFecha($filtro_hasta); ?></p>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2>🔍 Historial de Cambios del Sistema</h2>
        <button onclick="exportarCSV()" class="btn-success">📥 Exportar CSV</button>
    </div>

    <form method="GET" class="filters-box">
        <div class="filter-row">
            <div class="form-group">
                <label>Tabla</label>
                <select name="tabla" class="form-control">
                    <option value="">Todas las tablas</option>
                    <?php while ($tabla = $tablas->fetch_assoc()): ?>
                        <option value="<?php echo $tabla['tabla']; ?>" <?php echo ($filtro_tabla == $tabla['tabla']) ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $tabla['tabla'])); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Acción</label>
                <select name="accion" class="form-control">
                    <option value="">Todas las acciones</option>
                    <option value="INSERT" <?php echo ($filtro_accion == 'INSERT') ? 'selected' : ''; ?>>Inserción</option>
                    <option value="UPDATE" <?php echo ($filtro_accion == 'UPDATE') ? 'selected' : ''; ?>>Actualización</option>
                    <option value="DELETE" <?php echo ($filtro_accion == 'DELETE') ? 'selected' : ''; ?>>Eliminación</option>
                </select>
            </div>

            <div class="form-group">
                <label>Desde</label>
                <input type="date" name="desde" class="form-control" value="<?php echo $filtro_desde; ?>">
            </div>

            <div class="form-group">
                <label>Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?php echo $filtro_hasta; ?>">
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn-primary" style="display: block; width: 100%;">🔍 Filtrar</button>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <a href="index.php" class="btn-info" style="display: block; text-align: center;">🔄 Limpiar</a>
            </div>
        </div>
    </form>

    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table id="tablaAuditoria">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Tabla</th>
                        <th>Acción</th>
                        <th>ID Registro</th>
                        <th>Datos</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['fecha'])); ?></td>
                            <td><strong><?php echo ucfirst(str_replace('_', ' ', $log['tabla'])); ?></strong></td>
                            <td>
                                <?php
                                $clase_accion = '';
                                switch ($log['accion']) {
                                    case 'INSERT':
                                        $clase_accion = 'badge-success';
                                        $texto_accion = '➕ Inserción';
                                        break;
                                    case 'UPDATE':
                                        $clase_accion = 'badge-info';
                                        $texto_accion = '✏️ Actualización';
                                        break;
                                    case 'DELETE':
                                        $clase_accion = 'badge-danger';
                                        $texto_accion = '🗑️ Eliminación';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $clase_accion; ?>"><?php echo $texto_accion; ?></span>
                            </td>
                            <td><?php echo $log['id_registro']; ?></td>
                            <td>
                                <button class="btn-info btn-small" onclick="verDetalles(<?php echo $log['id']; ?>)">
                                    👁️ Ver Detalles
                                </button>
                            </td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                        
                        <!-- Fila oculta con detalles -->
                        <tr id="detalles-<?php echo $log['id']; ?>" style="display: none;">
                            <td colspan="6" style="background-color: #f8f9fc; padding: 20px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <?php if ($log['datos_anteriores']): ?>
                                        <div>
                                            <h4 style="color: #e74a3b; margin: 0 0 10px 0;">📋 Datos Anteriores</h4>
                                            <pre style="background: white; padding: 15px; border-radius: 0.35rem; overflow: auto; font-size: 0.8rem;"><?php echo json_encode(json_decode($log['datos_anteriores']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($log['datos_nuevos']): ?>
                                        <div>
                                            <h4 style="color: #1cc88a; margin: 0 0 10px 0;">📋 Datos Nuevos</h4>
                                            <pre style="background: white; padding: 15px; border-radius: 0.35rem; overflow: auto; font-size: 0.8rem;"><?php echo json_encode(json_decode($log['datos_nuevos']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($log['user_agent']): ?>
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e3e6f0;">
                                        <strong>User Agent:</strong> <?php echo htmlspecialchars($log['user_agent']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=1&tabla=<?php echo $filtro_tabla; ?>&accion=<?php echo $filtro_accion; ?>&desde=<?php echo $filtro_desde; ?>&hasta=<?php echo $filtro_hasta; ?>" class="btn-info btn-small">« Primera</a>
                <a href="?pagina=<?php echo $pagina_actual - 1; ?>&tabla=<?php echo $filtro_tabla; ?>&accion=<?php echo $filtro_accion; ?>&desde=<?php echo $filtro_desde; ?>&hasta=<?php echo $filtro_hasta; ?>" class="btn-info btn-small">‹ Anterior</a>
            <?php endif; ?>

            <span style="padding: 5px 15px; background: #f8f9fc; border-radius: 0.35rem;">
                Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
            </span>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_actual + 1; ?>&tabla=<?php echo $filtro_tabla; ?>&accion=<?php echo $filtro_accion; ?>&desde=<?php echo $filtro_desde; ?>&hasta=<?php echo $filtro_hasta; ?>" class="btn-info btn-small">Siguiente ›</a>
                <a href="?pagina=<?php echo $total_paginas; ?>&tabla=<?php echo $filtro_tabla; ?>&accion=<?php echo $filtro_accion; ?>&desde=<?php echo $filtro_desde; ?>&hasta=<?php echo $filtro_hasta; ?>" class="btn-info btn-small">Última »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-data">
            <p>📭 No hay registros de auditoría en el período seleccionado.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function verDetalles(id) {
    const fila = document.getElementById('detalles-' + id);
    if (fila.style.display === 'none') {
        fila.style.display = 'table-row';
    } else {
        fila.style.display = 'none';
    }
}

function exportarCSV() {
    const tabla = document.getElementById('tablaAuditoria');
    let csv = [];
    
    // Headers
    const headers = [];
    tabla.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Rows (solo filas visibles, no los detalles)
    tabla.querySelectorAll('tbody tr:not([id^="detalles"])').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach((td, index) => {
            if (index < 6) { // Solo las primeras 6 columnas
                let text = td.textContent.trim().replace(/,/g, ';');
                row.push('"' + text + '"');
            }
        });
        if (row.length > 0) {
            csv.push(row.join(','));
        }
    });
    
    // Descargar
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'auditoria_' + new Date().toISOString().slice(0,10) + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>