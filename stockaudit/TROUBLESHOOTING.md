# 🔧 GUÍA DE SOLUCIÓN DE PROBLEMAS
## Stock Audit Module - Troubleshooting

---

## ⚠️ Problema 1: El módulo no aparece en la lista

### Posibles causas:
- [ ] La carpeta no está en `/modules/stockaudit/`
- [ ] El nombre de la carpeta es incorrecto
- [ ] Permisos de archivo incorrectos
- [ ] Error en el código PHP

### Soluciones:

#### ✅ Verificar ubicación y nombre
```bash
# La ruta debe ser exactamente:
/tu-prestashop/modules/stockaudit/stockaudit.php

# NO debe ser:
/tu-prestashop/modules/StockAudit/  ❌
/tu-prestashop/modules/stock-audit/ ❌
/tu-prestashop/stockaudit/          ❌
```

#### ✅ Verificar permisos
```bash
# Establecer permisos correctos
chmod 755 modules/stockaudit
chmod 644 modules/stockaudit/*.php
chmod 755 modules/stockaudit/controllers
chmod 644 modules/stockaudit/controllers/admin/*.php
```

#### ✅ Revisar errores PHP
```bash
# Activar modo debug en PrestaShop
# Edita: /config/defines.inc.php

define('_PS_MODE_DEV_', true);

# Luego revisa errores en:
# /var/logs/
# o el error_log de PHP
```

#### ✅ Limpiar caché
```bash
# Borrar caché completa
rm -rf var/cache/*
rm -rf cache/class_index.php

# En el backoffice:
# Parámetros Avanzados → Rendimiento → Limpiar caché
```

---

## ⚠️ Problema 2: El módulo se instala pero no registra movimientos

### Verificaciones:

#### ✅ Comprobar hooks
```sql
-- Ver hooks registrados del módulo
SELECT h.name, m.name 
FROM ps_hook h
INNER JOIN ps_hook_module hm ON h.id_hook = hm.id_hook
INNER JOIN ps_module m ON hm.id_module = m.id_module
WHERE m.name = 'stockaudit';

-- Deberías ver al menos:
-- actionUpdateQuantity
-- actionProductUpdate
-- actionValidateOrder
```

#### ✅ Verificar tabla creada
```sql
-- Ver si existe la tabla
SHOW TABLES LIKE '%stock_audit%';

-- Ver estructura
DESCRIBE ps_stock_audit;

-- Ver si hay registros
SELECT COUNT(*) FROM ps_stock_audit;
```

#### ✅ Hacer prueba manual
1. Ve a un producto en el backoffice
2. Cambia la cantidad de stock manualmente
3. Guarda
4. Ejecuta esta query:
```sql
SELECT * FROM ps_stock_audit 
ORDER BY date_add DESC 
LIMIT 1;
```

Si no aparece nada, el problema está en los hooks.

#### ✅ Reinstalar hooks
```php
// En el backoffice:
// Módulos → Stock Audit → Desinstalar
// (MARCA la opción de mantener datos si existe)
// Luego → Instalar de nuevo
```

---

## ⚠️ Problema 3: Error "Tabla no existe"

### Error:
```
Table 'prestashop.ps_stock_audit' doesn't exist
```

### Solución:

#### Opción A: Crear tabla manualmente
```sql
-- Ejecuta el contenido de install.sql
-- Asegúrate de cambiar el prefijo si es necesario

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
    KEY `movement_type` (`movement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### Opción B: Reinstalar módulo
1. Desinstalar completamente
2. Borrar caché
3. Volver a instalar

---

## ⚠️ Problema 4: No aparece el menú "Auditoría de Stock"

### Verificar pestaña instalada:

```sql
-- Ver si existe la pestaña
SELECT * FROM ps_tab 
WHERE class_name = 'AdminStockAudit';

-- Si no existe, crearla manualmente:
INSERT INTO ps_tab (id_parent, class_name, module, active, position)
VALUES (
    (SELECT id_tab FROM ps_tab WHERE class_name = 'AdminCatalog'),
    'AdminStockAudit',
    'stockaudit',
    1,
    999
);

-- Añadir traducciones
INSERT INTO ps_tab_lang (id_tab, id_lang, name)
VALUES (
    (SELECT id_tab FROM ps_tab WHERE class_name = 'AdminStockAudit'),
    1, -- ID del idioma (1 = español generalmente)
    'Auditoría de Stock'
);
```

### Limpiar permisos de empleados:

```sql
-- Dar permisos al perfil de Superadmin
INSERT INTO ps_access (id_profile, id_authorization_role, id_tab)
SELECT 1, id_authorization_role, 
    (SELECT id_tab FROM ps_tab WHERE class_name = 'AdminStockAudit')
FROM ps_authorization_role;
```

---

## ⚠️ Problema 5: Exportación CSV no funciona

### Errores comunes:

#### "Headers already sent"
```php
// Verifica que no haya espacios o saltos de línea 
// ANTES de <?php en stockaudit.php

// Mal:
 <?php  ❌

// Bien:
<?php  ✅
```

#### "Permission denied" al exportar
```bash
# Verificar permisos de carpeta temporal
chmod 777 /tmp/
# o
chmod 777 var/cache/
```

#### Archivo vacío o corrupto
```php
// En AdminStockAuditController.php
// Asegúrate de que no hay output antes de:
header('Content-type: text/csv');

// Añade debug temporal:
error_log('Exportando ' . count($this->_list) . ' registros');
```

---

## ⚠️ Problema 6: Rendimiento lento con muchos registros

### Si tienes +100,000 registros:

#### ✅ Añadir índices
```sql
ALTER TABLE ps_stock_audit 
ADD INDEX idx_product_date (id_product, date_add);

ALTER TABLE ps_stock_audit 
ADD INDEX idx_type_date (movement_type, date_add);
```

#### ✅ Limpiar registros antiguos
```sql
-- Eliminar registros de más de 2 años
DELETE FROM ps_stock_audit 
WHERE date_add < DATE_SUB(NOW(), INTERVAL 2 YEAR);

-- O exportar primero y luego borrar
```

#### ✅ Paginación
El módulo ya incluye paginación automática, 
pero puedes ajustarla en:
```
Configuración → Preferencias → Productos por página
```

---

## ⚠️ Problema 7: Movimientos duplicados

### Causa:
Hooks ejecutándose múltiples veces

### Solución:
```php
// En stockaudit.php, modifica logStockMovement():

private $logged_movements = array();

private function logStockMovement($id_product, $id_product_attribute = 0, 
    $quantity_before = 0, $quantity_after = 0, $movement_type = 'unknown')
{
    // Crear identificador único
    $key = $id_product . '_' . $id_product_attribute . '_' 
         . $quantity_before . '_' . $quantity_after . '_' . time();
    
    // Evitar duplicados en la misma ejecución
    if (isset($this->logged_movements[$key])) {
        return true;
    }
    
    $this->logged_movements[$key] = true;
    
    // ... resto del código ...
}
```

---

## ⚠️ Problema 8: Conflicto con otros módulos

### Módulos que pueden causar conflictos:
- Módulos de gestión de stock avanzada
- Módulos de importación masiva
- Módulos de sincronización con marketplaces

### Solución:
```php
// Añadir prioridad alta a los hooks
// En install():

$this->registerHook('actionUpdateQuantity', null, 1); // Prioridad 1
```

O desactivar temporalmente el otro módulo para probar.

---

## ⚠️ Problema 9: No se registran cambios de combinaciones

### Verificar:
```sql
-- Ver movimientos de combinaciones
SELECT * FROM ps_stock_audit 
WHERE id_product_attribute > 0 
ORDER BY date_add DESC;
```

### Si está vacío:
```php
// Asegúrate de tener este hook en install():
$this->registerHook('actionProductAttributeUpdate');
```

---

## 📞 Checklist General de Diagnóstico

Antes de pedir ayuda, verifica:

- [ ] PHP 5.6+ (recomendado PHP 7.4+)
- [ ] MySQL 5.6+ (recomendado MySQL 8.0+)
- [ ] PrestaShop 1.6/1.7/8/9
- [ ] Módulo en `/modules/stockaudit/`
- [ ] Permisos correctos (755/644)
- [ ] Tabla `ps_stock_audit` existe
- [ ] Hooks registrados (query SQL arriba)
- [ ] Pestaña AdminStockAudit en menú
- [ ] Modo debug activado para ver errores
- [ ] Caché limpiada
- [ ] Probado en producto simple (no combinación)
- [ ] Logs de errores revisados

---

## 🔍 Debug Avanzado

### Activar logging en el módulo:

```php
// En stockaudit.php, al inicio de logStockMovement():

PrestaShopLogger::addLog(
    'Stock Audit: ' . $movement_type . ' - Producto ' . $id_product,
    1, // Nivel de información
    null,
    'StockAudit',
    $id_product
);

// Ver logs en:
// Parámetros Avanzados → Logs
```

---

## ✅ Test Final de Funcionamiento

Ejecuta este test completo:

1. **Crear producto de prueba**
   - Nombre: "Test Stock Audit"
   - Stock inicial: 100

2. **Cambiar stock manualmente**
   - Poner stock a 90
   - Guardar

3. **Verificar en base de datos**
```sql
SELECT * FROM ps_stock_audit 
WHERE id_product = (
    SELECT id_product FROM ps_product_lang 
    WHERE name = 'Test Stock Audit'
)
ORDER BY date_add DESC;
```

4. **Hacer una venta de prueba**
   - Crear pedido de 2 unidades
   - Validar pedido

5. **Ver en panel de administración**
   - Ir a Auditoría de Stock
   - Filtrar por producto "Test Stock Audit"
   - Deberías ver 2 movimientos

6. **Exportar a CSV**
   - Clic en "Exportar"
   - Verificar que descarga el archivo

---

Si después de seguir todos estos pasos el problema persiste:

1. **Activa modo debug**
2. **Captura el error completo**
3. **Revisa los logs** de PrestaShop
4. **Verifica versión** de PHP y MySQL
5. **Contacta** con el desarrollador con toda esta información

---

**¡Mucha suerte! 🚀**
