-- =====================================================
-- SCRIPT SQL PARA INSTALACIÓN MANUAL
-- Stock Audit Module - Auditoría de Stock
-- =====================================================
--
-- IMPORTANTE: Reemplaza "ps_" por el prefijo de tu base de datos
-- si es diferente (ej: "prestashop_", "tienda_", etc.)
--
-- =====================================================

-- Crear tabla principal de auditoría
CREATE TABLE IF NOT EXISTS `ps_stock_audit` (
    `id_stock_audit` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) UNSIGNED NOT NULL,
    `id_product_attribute` INT(11) UNSIGNED DEFAULT 0,
    `id_order` INT(11) UNSIGNED DEFAULT NULL,
    `id_employee` INT(11) UNSIGNED DEFAULT NULL,
    `quantity_before` INT(11) NOT NULL DEFAULT 0,
    `quantity_after` INT(11) NOT NULL DEFAULT 0,
    `quantity_diff` INT(11) NOT NULL DEFAULT 0,
    `movement_type` VARCHAR(50) NOT NULL,
    `movement_source` VARCHAR(100) DEFAULT NULL,
    `reason` TEXT DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (`id_stock_audit`),
    KEY `id_product` (`id_product`),
    KEY `id_product_attribute` (`id_product_attribute`),
    KEY `date_add` (`date_add`),
    KEY `movement_type` (`movement_type`),
    KEY `id_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- CONSULTAS ÚTILES PARA DIAGNÓSTICO
-- =====================================================

-- Ver total de movimientos registrados
-- SELECT COUNT(*) as total_movimientos FROM `ps_stock_audit`;

-- Ver últimos 10 movimientos
-- SELECT 
--     sa.*,
--     pl.name as producto,
--     p.reference as referencia
-- FROM `ps_stock_audit` sa
-- LEFT JOIN `ps_product` p ON p.id_product = sa.id_product
-- LEFT JOIN `ps_product_lang` pl ON pl.id_product = sa.id_product AND pl.id_lang = 1
-- ORDER BY sa.date_add DESC
-- LIMIT 10;

-- Ver movimientos de un producto específico (cambiar ID_PRODUCTO)
-- SELECT * FROM `ps_stock_audit` 
-- WHERE id_product = ID_PRODUCTO 
-- ORDER BY date_add DESC;

-- Ver estadísticas de movimientos por tipo
-- SELECT 
--     movement_type,
--     COUNT(*) as cantidad,
--     SUM(CASE WHEN quantity_diff > 0 THEN quantity_diff ELSE 0 END) as stock_añadido,
--     SUM(CASE WHEN quantity_diff < 0 THEN ABS(quantity_diff) ELSE 0 END) as stock_restado
-- FROM `ps_stock_audit`
-- GROUP BY movement_type
-- ORDER BY cantidad DESC;

-- Ver movimientos de los últimos 7 días
-- SELECT 
--     DATE(date_add) as fecha,
--     COUNT(*) as movimientos,
--     SUM(CASE WHEN quantity_diff > 0 THEN 1 ELSE 0 END) as incrementos,
--     SUM(CASE WHEN quantity_diff < 0 THEN 1 ELSE 0 END) as decrementos
-- FROM `ps_stock_audit`
-- WHERE date_add >= DATE_SUB(NOW(), INTERVAL 7 DAY)
-- GROUP BY DATE(date_add)
-- ORDER BY fecha DESC;

-- =====================================================
-- LIMPIEZA / MANTENIMIENTO
-- =====================================================

-- Eliminar movimientos anteriores a 1 año (usar con cuidado)
-- DELETE FROM `ps_stock_audit` 
-- WHERE date_add < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Eliminar TODOS los registros (CUIDADO - NO REVERSIBLE)
-- TRUNCATE TABLE `ps_stock_audit`;

-- Eliminar la tabla completamente
-- DROP TABLE IF EXISTS `ps_stock_audit`;

-- =====================================================
-- ÍNDICES ADICIONALES PARA MEJOR RENDIMIENTO (OPCIONAL)
-- =====================================================

-- Solo ejecutar si tienes muchos registros (>100,000) y notas lentitud

-- ALTER TABLE `ps_stock_audit` ADD INDEX `idx_product_date` (`id_product`, `date_add`);
-- ALTER TABLE `ps_stock_audit` ADD INDEX `idx_order` (`id_order`);
-- ALTER TABLE `ps_stock_audit` ADD INDEX `idx_employee` (`id_employee`);
-- ALTER TABLE `ps_stock_audit` ADD INDEX `idx_type_date` (`movement_type`, `date_add`);

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
