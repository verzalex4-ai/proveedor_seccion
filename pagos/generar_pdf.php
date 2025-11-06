<?php
/**
 * Generar Reporte PDF - pagos/generar_pdf.php
 * Usar la biblioteca TCPDF (debe instalarse via Composer o descarga manual)
 */

require_once '../config.php';

// Si usas TCPDF, descárgalo de: https://tcpdf.org/
// Y colócalo en una carpeta 'vendor/tcpdf/'
require_once(__DIR__ . '/../vendor/tcpdf/tcpdf.php');

// Obtener parámetros
$filtro_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$filtro_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

$conexion = conectarDB();

// Reporte de pagos por proveedor
$sql_proveedor = "SELECT 
                    p.nombre as proveedor,
                    COUNT(DISTINCT o.id) as total_ordenes,
                    COALESCE(SUM(o.total), 0) as total_comprado,
                    COALESCE(SUM(pag.monto), 0) as total_pagado,
                    (COALESCE(SUM(o.total), 0) - COALESCE(SUM(pag.monto), 0)) as saldo_pendiente
                FROM proveedores p
                LEFT JOIN ordenes_compra o ON p.id = o.id_proveedor 
                    AND o.fecha_emision BETWEEN ? AND ?
                    AND o.estado = 'Recibida'
                LEFT JOIN pagos pag ON o.id = pag.id_orden
                GROUP BY p.id
                HAVING total_comprado > 0
                ORDER BY total_comprado DESC";

$stmt_prov = $conexion->prepare($sql_proveedor);
$stmt_prov->bind_param("ss", $filtro_desde, $filtro_hasta);
$stmt_prov->execute();
$resultado_proveedor = $stmt_prov->get_result();
$stmt_prov->close();

// Totales generales
$sql_totales = "SELECT 
                    COUNT(DISTINCT o.id) as total_ordenes,
                    COALESCE(SUM(o.total), 0) as total_compras,
                    COALESCE(SUM(pag.monto), 0) as total_pagos
                FROM ordenes_compra o
                LEFT JOIN pagos pag ON o.id = pag.id_orden
                WHERE o.fecha_emision BETWEEN ? AND ?";

$stmt_tot = $conexion->prepare($sql_totales);
$stmt_tot->bind_param("ss", $filtro_desde, $filtro_hasta);
$stmt_tot->execute();
$totales = $stmt_tot->get_result()->fetch_assoc();
$stmt_tot->close();

cerrarDB($conexion);

// Crear PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configuración del documento
$pdf->SetCreator('Sistema de Compras');
$pdf->SetAuthor('Sistema de Gestión');
$pdf->SetTitle('Reporte Financiero');
$pdf->SetSubject('Reporte de Pagos');

// Configuración de página
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Primera página
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(78, 115, 223);
$pdf->Cell(0, 10, 'REPORTE FINANCIERO', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(90, 92, 105);
$pdf->Cell(0, 5, 'Período: ' . formatearFecha($filtro_desde) . ' al ' . formatearFecha($filtro_hasta), 0, 1, 'C');
$pdf->Cell(0, 5, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Ln(10);

// Resumen ejecutivo
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(78, 115, 223);
$pdf->Cell(0, 8, 'RESUMEN EJECUTIVO', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(90, 92, 105);

// Tabla de resumen
$pdf->SetFillColor(248, 249, 252);
$pdf->Cell(90, 8, 'Total de Órdenes:', 1, 0, 'L', true);
$pdf->Cell(90, 8, $totales['total_ordenes'], 1, 1, 'R');

$pdf->Cell(90, 8, 'Total de Compras:', 1, 0, 'L', true);
$pdf->Cell(90, 8, formatearMoneda($totales['total_compras']), 1, 1, 'R');

$pdf->Cell(90, 8, 'Total de Pagos Realizados:', 1, 0, 'L', true);
$pdf->Cell(90, 8, formatearMoneda($totales['total_pagos']), 1, 1, 'R');

$pdf->SetFillColor(231, 243, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(90, 8, 'Saldo Pendiente:', 1, 0, 'L', true);
$pdf->Cell(90, 8, formatearMoneda($totales['total_compras'] - $totales['total_pagos']), 1, 1, 'R', true);

$pdf->Ln(10);

// Detalle por proveedor
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(78, 115, 223);
$pdf->Cell(0, 8, 'DETALLE POR PROVEEDOR', 0, 1, 'L');
$pdf->Ln(2);

// Cabecera de tabla
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(78, 115, 223);
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell(50, 8, 'Proveedor', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Órdenes', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Total Comprado', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Total Pagado', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Saldo', 1, 1, 'C', true);

// Datos
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(90, 92, 105);

$resultado_proveedor->data_seek(0);
while ($row = $resultado_proveedor->fetch_assoc()) {
    $pdf->Cell(50, 7, substr($row['proveedor'], 0, 25), 1, 0, 'L');
    $pdf->Cell(25, 7, $row['total_ordenes'], 1, 0, 'C');
    $pdf->Cell(35, 7, formatearMoneda($row['total_comprado']), 1, 0, 'R');
    $pdf->Cell(35, 7, formatearMoneda($row['total_pagado']), 1, 0, 'R');
    $pdf->Cell(35, 7, formatearMoneda($row['saldo_pendiente']), 1, 1, 'R');
}

// Pie de página personalizado
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(133, 135, 150);
$pdf->Cell(0, 5, 'Sistema de Gestión de Compras y Proveedores', 0, 1, 'C');
$pdf->Cell(0, 5, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C');

// Salida del PDF
$pdf->Output('reporte_financiero_' . date('Y-m-d') . '.pdf', 'D');
exit();