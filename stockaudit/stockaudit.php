<?php
/**
 * Stock Audit Module
 * Módulo de Auditoría de Movimientos de Stock
 * Compatible con PrestaShop 1.6, 1.7, 8 y 9
 *
 * @author Tu Nombre
 * @version 1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class StockAudit extends Module
{
    public function __construct()
    {
        $this->name = 'stockaudit';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Tu Nombre';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Stock Audit - Auditoría de Stock');
        $this->description = $this->l('Registra y audita todos los movimientos de stock de tus productos');

        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar este módulo? Se perderá todo el historial de movimientos.');
    }

    /**
     * Instalación del módulo
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $install = parent::install()
            && $this->installDB()
            && $this->registerHook('actionUpdateQuantity')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionObjectProductUpdateAfter')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->installTab();
        
        // Registrar stock inicial de todos los productos
        if ($install) {
            $this->registerInitialStock();
        }
        
        return $install;
    }

    /**
     * Desinstalación del módulo
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDB()
            && $this->uninstallTab();
    }

    /**
     * Crear tabla en base de datos
     */
    private function installDB()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'stock_audit` (
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
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Eliminar tabla de base de datos
     */
    private function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'stock_audit`';
        return Db::getInstance()->execute($sql);
    }

    /**
     * Instalar pestaña en el backoffice
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminStockAudit';
        $tab->name = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Auditoría de Stock';
        }
        
        // Determinar el parent según la versión de PrestaShop
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
        } else {
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminStock');
        }
        
        $tab->module = $this->name;
        
        return $tab->add();
    }

    /**
     * Desinstalar pestaña del backoffice
     */
    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminStockAudit');
        
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        
        return true;
    }

    /**
     * Hook: Actualización de cantidad (principal)
     */
    public function hookActionUpdateQuantity($params)
    {
        $id_product = (int)$params['id_product'];
        $id_product_attribute = isset($params['id_product_attribute']) ? (int)$params['id_product_attribute'] : 0;

        // Obtener stock ANTES del cambio desde el último registro de auditoría
        $quantity_before = $this->getLastRecordedStock($id_product, $id_product_attribute);

        // Si no hay registro previo, usar el stock actual de la BD
        if ($quantity_before === null) {
            $quantity_before = $this->getProductStock($id_product, $id_product_attribute);
        }

        // El stock DESPUÉS del cambio siempre viene de la BD (ya está actualizado)
        $quantity_after = $this->getProductStock($id_product, $id_product_attribute);

        // Determinar el tipo de movimiento
        $movement_type = 'update';
        $reason = 'Actualización de cantidad';
        $id_order = null;

        // Contexto más específico
        if (isset($params['id_order']) && $params['id_order'] > 0) {
            $movement_type = 'order';
            $id_order = (int)$params['id_order'];
            $reason = 'Pedido #' . $id_order;
        } elseif (isset($params['id_stock_mvt_reason'])) {
            $id_reason = (int)$params['id_stock_mvt_reason'];
            $stock_reason = new StockMvtReason($id_reason);
            if (Validate::isLoadedObject($stock_reason)) {
                $movement_type = 'stock_movement';
                $reason = $stock_reason->name;
            }
        }

        $this->logStockMovement(
            $id_product,
            $id_product_attribute,
            $quantity_before,
            $quantity_after,
            $movement_type,
            $reason,
            $id_order
        );
    }

    /**
     * Obtener stock actual de un producto
     */
    private function getProductStock($id_product, $id_product_attribute = 0)
    {
        $sql = 'SELECT quantity 
                FROM `' . _DB_PREFIX_ . 'stock_available`
                WHERE id_product = ' . (int)$id_product . '
                AND id_product_attribute = ' . (int)$id_product_attribute;
        
        $quantity = Db::getInstance()->getValue($sql);
        
        return $quantity !== false ? (int)$quantity : 0;
    }

    /**
     * Hook: Actualización de producto (después)
     */
    public function hookActionObjectProductUpdateAfter($params)
    {
        if (isset($params['object']) && $params['object'] instanceof Product) {
            $product = $params['object'];
            $id_product = (int)$product->id;

            // Solo para productos sin combinaciones
            if (!$product->hasCombinations()) {
                // Stock ANTES = último registrado en auditoría
                $quantity_before = $this->getLastRecordedStock($id_product, 0);

                // Stock DESPUÉS = actual en la BD
                $quantity_after = $this->getProductStock($id_product, 0);

                // Si hay registro previo y el stock cambió, registrar
                if ($quantity_before !== null && $quantity_after != $quantity_before) {
                    $this->logStockMovement(
                        $id_product,
                        0,
                        $quantity_before,
                        $quantity_after,
                        'manual_update',
                        'Actualización manual desde backoffice'
                    );
                } elseif ($quantity_before === null) {
                    // Si no hay registro previo, es la primera vez que se registra
                    $this->logStockMovement(
                        $id_product,
                        0,
                        0,
                        $quantity_after,
                        'initial_stock',
                        'Stock inicial del producto'
                    );
                }
            }
        }
    }

    /**
     * Obtener último stock registrado
     */
    private function getLastRecordedStock($id_product, $id_product_attribute = 0)
    {
        $sql = 'SELECT quantity_after 
                FROM `' . _DB_PREFIX_ . 'stock_audit`
                WHERE id_product = ' . (int)$id_product . '
                AND id_product_attribute = ' . (int)$id_product_attribute . '
                ORDER BY date_add DESC
                LIMIT 1';
        
        $quantity = Db::getInstance()->getValue($sql);
        
        return $quantity !== false ? (int)$quantity : null;
    }

    /**
     * Hook: Validación de pedido
     */
    public function hookActionValidateOrder($params)
    {
        if (isset($params['order'])) {
            $order = $params['order'];
            
            foreach ($order->getProducts() as $product) {
                $this->logStockMovement(
                    (int)$product['product_id'],
                    (int)$product['product_attribute_id'],
                    0, // Se actualizará con el hook actionUpdateQuantity
                    0,
                    'order_validation',
                    'Validación de pedido #' . (int)$order->id,
                    (int)$order->id
                );
            }
        }
    }

    /**
     * Registrar movimiento de stock
     */
    private function logStockMovement(
        $id_product,
        $id_product_attribute = 0,
        $quantity_before = 0,
        $quantity_after = 0,
        $movement_type = 'unknown',
        $reason = null,
        $id_order = null
    ) {
        $context = Context::getContext();
        $id_employee = isset($context->employee->id) ? (int)$context->employee->id : null;
        
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        // Truncar user_agent a 255 caracteres para evitar errores
        $user_agent = Tools::substr($user_agent, 0, 255);

        $data = array(
            'id_product' => (int)$id_product,
            'id_product_attribute' => (int)$id_product_attribute,
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

        return Db::getInstance()->insert('stock_audit', $data);
    }

    /**
     * Registrar stock inicial de todos los productos al instalar
     */
    private function registerInitialStock()
    {
        // Solo registrar productos con stock > 0 o productos activos importantes
        // Evitar registrar miles de productos con stock 0
        
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'stock_audit` 
            (id_product, id_product_attribute, quantity_before, quantity_after, 
             quantity_diff, movement_type, movement_source, reason, date_add)
            SELECT 
                s.id_product,
                s.id_product_attribute,
                0,
                s.quantity,
                s.quantity,
                "initial_stock",
                "Sistema",
                "Stock inicial al instalar el módulo",
                NOW()
            FROM `' . _DB_PREFIX_ . 'stock_available` s
            INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = s.id_product
            WHERE p.active = 1
            AND s.quantity != 0';  // Solo productos con stock diferente de 0
        
        return Db::getInstance()->execute($sql);
    }

    /**
     * Determinar origen del movimiento
     */
    private function getMovementSource()
    {
        $context = Context::getContext();
        
        if (defined('_PS_ADMIN_DIR_')) {
            return 'BackOffice';
        } elseif (Tools::getValue('controller') == 'AdminProducts') {
            return 'AdminProducts';
        } elseif (isset($context->controller) && get_class($context->controller)) {
            return get_class($context->controller);
        }
        
        return 'Unknown';
    }

    /**
     * Configuración del módulo
     */
    public function getContent()
    {
        $output = '';
        
        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $this->displayConfirmation($this->l('Configuración guardada correctamente'));
        }

        $output .= $this->displayInfo();
        
        return $output . $this->renderForm();
    }

    /**
     * Mostrar información del módulo
     */
    private function displayInfo()
    {
        $total_movements = Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'stock_audit`');
        
        $html = '
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-info"></i> ' . $this->l('Información del módulo') . '
            </div>
            <div class="panel-body">
                <p>' . $this->l('Este módulo registra automáticamente todos los movimientos de stock de tus productos.') . '</p>
                <p><strong>' . $this->l('Movimientos registrados:') . '</strong> ' . (int)$total_movements . '</p>
                <p><a href="' . $this->context->link->getAdminLink('AdminStockAudit') . '" class="btn btn-primary">
                    <i class="icon-search"></i> ' . $this->l('Ver historial completo') . '
                </a></p>
            </div>
        </div>';
        
        return $html;
    }

    /**
     * Formulario de configuración
     */
    private function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuración'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'html',
                        'name' => 'info',
                        'html_content' => '<p>' . $this->l('El módulo funciona automáticamente. No requiere configuración adicional.') . '</p>'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm(array($fields_form));
    }
}
