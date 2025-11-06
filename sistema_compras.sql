-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2025 a las 12:07:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_compras`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_orden`
--

CREATE TABLE `detalle_orden` (
  `id` int(11) NOT NULL,
  `id_orden` int(11) NOT NULL,
  `producto` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `unidad_medida` varchar(50) DEFAULT 'Unidad',
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ;

--
-- Volcado de datos para la tabla `detalle_orden`
--

INSERT INTO `detalle_orden` (`id`, `id_orden`, `producto`, `descripcion`, `cantidad`, `unidad_medida`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 'gallets', NULL, 1.00, 'Unidad', 1000.00, 1000.00);

--
-- Disparadores `detalle_orden`
--
DELIMITER $$
CREATE TRIGGER `before_insert_detalle` BEFORE INSERT ON `detalle_orden` FOR EACH ROW BEGIN
    IF NEW.cantidad <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La cantidad debe ser mayor a cero';
    END IF;
    
    IF NEW.precio_unitario < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El precio no puede ser negativo';
    END IF;
    
    -- Recalcular subtotal automáticamente
    SET NEW.subtotal = NEW.cantidad * NEW.precio_unitario;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra`
--

CREATE TABLE `ordenes_compra` (
  `id` int(11) NOT NULL,
  `numero_orden` varchar(50) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_entrega_estimada` date DEFAULT NULL,
  `fecha_recepcion` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impuestos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `estado` enum('Pendiente','Enviada','Recibida','Cancelada') DEFAULT 'Pendiente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `ordenes_compra`
--

INSERT INTO `ordenes_compra` (`id`, `numero_orden`, `id_proveedor`, `fecha_emision`, `fecha_entrega_estimada`, `fecha_recepcion`, `subtotal`, `impuestos`, `total`, `observaciones`, `estado`, `fecha_registro`, `fecha_modificacion`) VALUES
(1, 'OC-2025-001', 5, '2025-11-06', '2025-11-08', '2025-11-06', 1000.00, 210.00, 1210.00, 'compra', 'Recibida', '2025-11-06 10:38:16', '2025-11-06 10:39:17');

--
-- Disparadores `ordenes_compra`
--
DELIMITER $$
CREATE TRIGGER `before_update_orden` BEFORE UPDATE ON `ordenes_compra` FOR EACH ROW BEGIN
    -- Si la orden ya está recepcionada, no permitir cambiar a otro estado
    IF OLD.estado = 'Recibida' AND NEW.estado != 'Recibida' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede cambiar el estado de una orden ya recepcionada';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `id_orden` int(11) NOT NULL,
  `fecha_pago` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `numero_comprobante` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `id_orden`, `fecha_pago`, `monto`, `metodo_pago`, `numero_comprobante`, `observaciones`, `fecha_registro`) VALUES
(1, 1, '2025-11-06', 1210.00, 'Transferencia', '68768768', '', '2025-11-06 10:40:23');

--
-- Disparadores `pagos`
--
DELIMITER $$
CREATE TRIGGER `before_insert_pago` BEFORE INSERT ON `pagos` FOR EACH ROW BEGIN
    DECLARE total_orden DECIMAL(10,2);
    DECLARE total_pagado DECIMAL(10,2);
    DECLARE saldo_pendiente DECIMAL(10,2);
    
    -- Obtener total de la orden
    SELECT total INTO total_orden
    FROM ordenes_compra
    WHERE id = NEW.id_orden;
    
    -- Obtener total ya pagado
    SELECT COALESCE(SUM(monto), 0) INTO total_pagado
    FROM pagos
    WHERE id_orden = NEW.id_orden;
    
    -- Calcular saldo
    SET saldo_pendiente = total_orden - total_pagado;
    
    -- Validar que el pago no exceda el saldo
    IF NEW.monto > saldo_pendiente THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del pago excede el saldo pendiente';
    END IF;
    
    -- Validar que el monto sea positivo
    IF NEW.monto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del pago debe ser mayor a cero';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `cuit` varchar(13) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `condiciones_pago` varchar(50) DEFAULT '30 días',
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `razon_social`, `cuit`, `contacto`, `email`, `telefono`, `direccion`, `condiciones_pago`, `estado`, `fecha_registro`, `fecha_modificacion`) VALUES
(5, '333388', '3', '', '3', 'martineznataliromina@gmail.com', '', '', '30 días', 'Activo', '2025-11-06 10:36:33', '2025-11-06 10:37:21');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_saldos_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_saldos_pendientes` (
`id` int(11)
,`numero_orden` varchar(50)
,`fecha_emision` date
,`total` decimal(10,2)
,`proveedor` varchar(200)
,`condiciones_pago` varchar(50)
,`pagado` decimal(32,2)
,`saldo_pendiente` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_saldos_pendientes`
--
DROP TABLE IF EXISTS `v_saldos_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_saldos_pendientes`  AS SELECT `o`.`id` AS `id`, `o`.`numero_orden` AS `numero_orden`, `o`.`fecha_emision` AS `fecha_emision`, `o`.`total` AS `total`, `p`.`nombre` AS `proveedor`, `p`.`condiciones_pago` AS `condiciones_pago`, coalesce(sum(`pag`.`monto`),0) AS `pagado`, `o`.`total`- coalesce(sum(`pag`.`monto`),0) AS `saldo_pendiente` FROM ((`ordenes_compra` `o` join `proveedores` `p` on(`o`.`id_proveedor` = `p`.`id`)) left join `pagos` `pag` on(`o`.`id` = `pag`.`id_orden`)) WHERE `o`.`estado` = 'Recibida' GROUP BY `o`.`id` HAVING `saldo_pendiente` > 0 ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle_orden`
--
ALTER TABLE `detalle_orden`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orden` (`id_orden`),
  ADD KEY `idx_detalle_producto` (`producto`);

--
-- Indices de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_orden` (`numero_orden`),
  ADD KEY `idx_proveedor` (`id_proveedor`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_emision` (`fecha_emision`),
  ADD KEY `idx_ordenes_numero` (`numero_orden`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orden` (`id_orden`),
  ADD KEY `idx_fecha_pago` (`fecha_pago`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cuit` (`cuit`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_proveedores_nombre` (`nombre`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_orden`
--
ALTER TABLE `detalle_orden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_orden`
--
ALTER TABLE `detalle_orden`
  ADD CONSTRAINT `fk_detalle_orden` FOREIGN KEY (`id_orden`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD CONSTRAINT `fk_ordenes_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_orden` FOREIGN KEY (`id_orden`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
