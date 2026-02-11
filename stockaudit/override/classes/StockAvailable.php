<?php
/**
 * Override StockAvailable para capturar TODOS los cambios de stock
 * Compatible con PrestaShop 1.6, 1.7, 1.8, 1.9
 */

class StockAvailable extends StockAvailableCore
{
    /**
     * Override del método update para capturar cambios de stock
     */
    public function update($null_values = false)
    {
        // Guardar el stock ANTES del cambio
        $quantity_before = null;

        // Si ya existe en BD, obtener el valor anterior
        if ($this->id) {
            $sql = 'SELECT quantity FROM ' . _DB_PREFIX_ . 'stock_available WHERE id_stock_available = ' . (int)$this->id;
            $quantity_before = Db::getInstance()->getValue($sql);
        }

        // Ejecutar el update original
        $result = parent::update($null_values);

        // Si el update fue exitoso, registrar el cambio en stock_audit
        if ($result && $quantity_before !== null && $quantity_before != $this->quantity) {
            $this->logStockChange($quantity_before, $this->quantity);
        }

        return $result;
    }

    /**
     * Registrar cambio de stock en el módulo de auditoría
     */
    protected function logStockChange($quantity_before, $quantity_after)
    {
        // Verificar que el módulo está instalado
        if (!Module::isInstalled('stockaudit') || !Module::isEnabled('stockaudit')) {
            return;
        }

        // IMPORTANTE: Si es el producto padre (id_product_attribute = 0) y tiene combinaciones,
        // NO registrar el cambio porque el stock real está en las combinaciones
        if ((int)$this->id_product_attribute == 0) {
            // Verificar si el producto tiene combinaciones
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_attribute
                    WHERE id_product = ' . (int)$this->id_product;
            $has_combinations = (int)Db::getInstance()->getValue($sql) > 0;

            if ($has_combinations) {
                // Es un producto padre con combinaciones - NO registrar
                return;
            }
        }

        // Obtener instancia del módulo
        $module = Module::getInstanceByName('stockaudit');
        if (!$module) {
            return;
        }

        // Determinar contexto y tipo de movimiento
        $context = Context::getContext();
        $movement_type = 'update';
        $reason = 'Actualización de stock';
        $id_order = null;

        // Detectar tipo de movimiento según el contexto
        if (isset($context->controller) && is_object($context->controller)) {
            $controller_name = get_class($context->controller);

            if (strpos($controller_name, 'AdminProducts') !== false) {
                $movement_type = 'manual_update';
                $reason = 'Edición manual desde ficha de producto';
            } elseif (strpos($controller_name, 'AdminStock') !== false) {
                $movement_type = 'manual_update';
                $reason = 'Actualización desde control de stocks';
            } elseif (strpos($controller_name, 'Order') !== false) {
                $movement_type = 'order';
                $reason = 'Venta (pedido)';
            }
        }

        // Obtener employee si existe
        $id_employee = isset($context->employee->id) ? (int)$context->employee->id : null;

        // Truncar user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $user_agent = Tools::substr($user_agent, 0, 255);

        // Preparar datos para insertar
        $data = array(
            'id_product' => (int)$this->id_product,
            'id_product_attribute' => (int)$this->id_product_attribute,
            'id_order' => $id_order,
            'id_employee' => $id_employee,
            'quantity_before' => (int)$quantity_before,
            'quantity_after' => (int)$quantity_after,
            'quantity_diff' => (int)$quantity_after - (int)$quantity_before,
            'movement_type' => pSQL($movement_type),
            'movement_source' => pSQL($this->getMovementSource()),
            'reason' => pSQL($reason),
            'date_add' => date('Y-m-d H:i:s'),
            'user_agent' => pSQL($user_agent),
            'ip_address' => pSQL(Tools::getRemoteAddr())
        );

        // Insertar en la tabla de auditoría
        Db::getInstance()->insert('stock_audit', $data);
    }

    /**
     * Determinar origen del movimiento
     */
    protected function getMovementSource()
    {
        $context = Context::getContext();

        if (defined('_PS_ADMIN_DIR_')) {
            if (isset($context->controller) && is_object($context->controller)) {
                return get_class($context->controller);
            }
            return 'BackOffice';
        }

        return 'FrontOffice';
    }
}
