<?php
/**
 * EJEMPLOS DE PERSONALIZACIÓN AVANZADA
 * Stock Audit Module
 * 
 * Este archivo contiene ejemplos de código para personalizar
 * el módulo según tus necesidades específicas.
 * 
 * NO INCLUIR ESTE ARCHIVO EN PRODUCCIÓN - ES SOLO REFERENCIA
 */

// =====================================================
// EJEMPLO 1: Añadir un hook personalizado
// =====================================================

/**
 * Si quieres capturar un evento específico que no está incluido,
 * puedes añadir más hooks en el método install() del módulo:
 */

/*
public function install()
{
    return parent::install()
        && $this->installDB()
        && $this->registerHook('actionUpdateQuantity')
        && $this->registerHook('actionProductUpdate')
        
        // NUEVOS HOOKS PERSONALIZADOS:
        && $this->registerHook('actionProductDelete')  // Cuando se elimina un producto
        && $this->registerHook('actionOrderReturn')    // Devoluciones
        && $this->registerHook('actionProductAttributeUpdate') // Combinaciones
        
        && $this->installTab();
}
*/

// =====================================================
// EJEMPLO 2: Hook para capturar eliminación de productos
// =====================================================

/**
 * Añade este método a la clase StockAudit en stockaudit.php
 */

/*
public function hookActionProductDelete($params)
{
    if (isset($params['product'])) {
        $product = $params['product'];
        
        $this->logStockMovement(
            (int)$product->id,
            0,
            (int)$product->quantity,
            0,
            'product_deleted',
            'Producto eliminado del catálogo'
        );
    }
}
*/

// =====================================================
// EJEMPLO 3: Hook para devoluciones de pedidos
// =====================================================

/*
public function hookActionOrderReturn($params)
{
    if (isset($params['order']) && isset($params['orderReturn'])) {
        $order = $params['order'];
        $orderReturn = $params['orderReturn'];
        
        // Obtener productos devueltos
        $returnProducts = $orderReturn->getProducts();
        
        foreach ($returnProducts as $product) {
            $this->logStockMovement(
                (int)$product['product_id'],
                (int)$product['product_attribute_id'],
                0, // Se actualizará automáticamente
                (int)$product['product_quantity'],
                'return',
                'Devolución del pedido #' . (int)$order->id,
                (int)$order->id
            );
        }
    }
}
*/

// =====================================================
// EJEMPLO 4: Enviar email cuando el stock baja de umbral
// =====================================================

/**
 * Añade esto al método logStockMovement() para enviar alertas
 */

/*
private function logStockMovement($id_product, $id_product_attribute = 0, 
    $quantity_before = 0, $quantity_after = 0, $movement_type = 'unknown', 
    $reason = null, $id_order = null)
{
    // ... código existente ...
    
    // ALERTA DE STOCK BAJO
    $threshold = 5; // Umbral mínimo de stock
    
    if ($quantity_after <= $threshold && $quantity_before > $threshold) {
        // El stock acaba de bajar del umbral
        $this->sendLowStockAlert($id_product, $quantity_after);
    }
    
    return Db::getInstance()->insert('stock_audit', $data);
}

private function sendLowStockAlert($id_product, $current_stock)
{
    $product = new Product($id_product, false, $this->context->language->id);
    $email_admin = Configuration::get('PS_SHOP_EMAIL');
    
    Mail::Send(
        $this->context->language->id,
        'low_stock_alert',
        'Alerta: Stock bajo',
        array(
            '{product_name}' => $product->name,
            '{current_stock}' => $current_stock,
            '{product_link}' => $this->context->link->getAdminLink('AdminProducts') 
                . '&id_product=' . $id_product . '&updateproduct'
        ),
        $email_admin,
        null,
        null,
        null,
        null,
        null,
        dirname(__FILE__) . '/mails/'
    );
}
*/

// =====================================================
// EJEMPLO 5: Exportar datos específicos con filtros avanzados
// =====================================================

/**
 * Añade este método al controlador AdminStockAuditController.php
 * para exportar solo movimientos negativos
 */

/*
public function processExportNegativeMovements()
{
    // Obtener solo decrementos de stock
    $sql = 'SELECT sa.*, p.reference, pl.name as product_name 
            FROM `' . _DB_PREFIX_ . 'stock_audit` sa
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = sa.id_product
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.id_product = sa.id_product
            WHERE sa.quantity_diff < 0
            ORDER BY sa.date_add DESC';
    
    $data = Db::getInstance()->executeS($sql);
    
    if (!count($data)) {
        return;
    }
    
    header('Content-type: text/csv');
    header('Content-disposition: attachment; filename="movimientos_negativos_' 
        . date('Y-m-d_His') . '.csv"');
    
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    
    $fd = fopen('php://output', 'w');
    
    // Headers
    fputcsv($fd, array('Fecha', 'Producto', 'Stock Anterior', 'Stock Nuevo', 
        'Diferencia', 'Tipo', 'Motivo'), ';', '"');
    
    foreach ($data as $row) {
        fputcsv($fd, array(
            $row['date_add'],
            $row['product_name'],
            $row['quantity_before'],
            $row['quantity_after'],
            $row['quantity_diff'],
            $row['movement_type'],
            $row['reason']
        ), ';', '"');
    }
    
    fclose($fd);
    exit;
}
*/

// =====================================================
// EJEMPLO 6: Añadir columna personalizada a la tabla
// =====================================================

/**
 * Si necesitas añadir campos personalizados a la tabla:
 */

/*
// 1. Ejecuta esta query SQL en tu base de datos:

ALTER TABLE `ps_stock_audit` 
ADD COLUMN `id_warehouse` INT(11) UNSIGNED DEFAULT NULL AFTER `id_product_attribute`,
ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `reason`;

// 2. Modifica el método logStockMovement() para incluir los nuevos campos:

$data = array(
    // ... campos existentes ...
    'id_warehouse' => (int)$id_warehouse,
    'notes' => pSQL($notes),
    // ... resto de campos ...
);
*/

// =====================================================
// EJEMPLO 7: Dashboard widget con últimos movimientos
// =====================================================

/**
 * Añade un widget al dashboard de PrestaShop
 */

/*
// En stockaudit.php, añade:

public function install()
{
    return parent::install()
        && $this->installDB()
        // ... otros hooks ...
        && $this->registerHook('displayBackOfficeHeader')
        && $this->installTab();
}

public function hookDisplayBackOfficeHeader()
{
    // Solo en el dashboard
    if ($this->context->controller->controller_name == 'AdminDashboard') {
        $this->context->controller->addCSS($this->_path . 'views/css/dashboard.css');
        $this->context->controller->addJS($this->_path . 'views/js/dashboard.js');
    }
}

public function renderWidget($hookName, array $params)
{
    // Obtener últimos 5 movimientos
    $sql = 'SELECT sa.*, pl.name 
            FROM `' . _DB_PREFIX_ . 'stock_audit` sa
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl 
                ON pl.id_product = sa.id_product
            ORDER BY sa.date_add DESC
            LIMIT 5';
    
    $movements = Db::getInstance()->executeS($sql);
    
    $this->context->smarty->assign('last_movements', $movements);
    
    return $this->display(__FILE__, 'dashboard_widget.tpl');
}
*/

// =====================================================
// EJEMPLO 8: Integración con módulo de inventario
// =====================================================

/**
 * Si usas Advanced Stock Management de PrestaShop
 */

/*
public function install()
{
    return parent::install()
        // ... otros hooks ...
        && $this->registerHook('actionUpdateAdvancedStockManagement');
}

public function hookActionUpdateAdvancedStockManagement($params)
{
    if (isset($params['warehouse']) && isset($params['product'])) {
        $warehouse = $params['warehouse'];
        $product = $params['product'];
        
        $this->logStockMovement(
            (int)$product->id,
            0,
            0,
            (int)$product->quantity,
            'warehouse_transfer',
            'Transferencia a almacén: ' . $warehouse->name
        );
    }
}
*/

// =====================================================
// FIN DE EJEMPLOS
// =====================================================

/**
 * NOTAS IMPORTANTES:
 * 
 * 1. Estos son solo ejemplos de referencia
 * 2. Adapta el código a tus necesidades específicas
 * 3. Siempre haz backup antes de modificar código
 * 4. Prueba en entorno de desarrollo primero
 * 5. Consulta la documentación de hooks de PrestaShop:
 *    https://devdocs.prestashop.com/1.7/modules/concepts/hooks/
 */
