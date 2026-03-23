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
            && $this->registerHook('actionObjectStockAvailableUpdateAfter')  // PS 1.7+
            && $this->registerHook('actionProductUpdate')  // PS 1.6+
            && $this->registerHook('displayAdminProductsQuantitiesStepBottom')  // PS 1.7+
            && $this->registerHook('actionAdminControllerSetMedia')  // Para detectar controlador
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->installTab()
            && $this->installOverride();  // Instalar override

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
            && $this->uninstallTab()
            && $this->uninstallOverride();  // Desinstalar override
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
     * Instalar override de StockAvailable
     */
    private function installOverride()
    {
        $module_override = dirname(__FILE__) . '/override/classes/StockAvailable.php';
        $ps_override_dir = _PS_OVERRIDE_DIR_ . 'classes/';
        $ps_override_file = $ps_override_dir . 'StockAvailable.php';

        // Verificar que el override del módulo existe
        if (!file_exists($module_override)) {
            $this->_errors[] = 'Override file not found in module';
            return false;
        }

        // Crear directorio si no existe
        if (!file_exists($ps_override_dir)) {
            @mkdir($ps_override_dir, 0755, true);
        }

        // Copiar el override al directorio de PrestaShop
        if (!@copy($module_override, $ps_override_file)) {
            $this->_errors[] = 'Failed to copy override file';
            return false;
        }

        // Regenerar el class_index.php para que PrestaShop reconozca el override
        try {
            if (method_exists('Tools', 'generateIndex')) {
                Tools::generateIndex();
            }
        } catch (Exception $e) {
            $this->clearCache();
        }

        return true;
    }

    /**
     * Desinstalar override de StockAvailable
     */
    private function uninstallOverride()
    {
        $ps_override_file = _PS_OVERRIDE_DIR_ . 'classes/StockAvailable.php';

        if (file_exists($ps_override_file)) {
            // Leer contenido del override
            $content = @file_get_contents($ps_override_file);

            // Solo eliminar si es nuestro override
            if ($content && strpos($content, 'stockaudit') !== false) {
                // Eliminar el archivo
                @unlink($ps_override_file);
            }
        }

        // Limpiar el class_index.php ANTES de regenerar
        $class_index = _PS_ROOT_DIR_ . '/cache/class_index.php';
        if (file_exists($class_index)) {
            @unlink($class_index);
        }

        // Regenerar el class_index.php
        try {
            if (class_exists('Tools') && method_exists('Tools', 'generateIndex')) {
                Tools::generateIndex();
            }
        } catch (Exception $e) {
            // Si falla, al menos intentamos limpiar la caché
            $this->clearCache();
        }

        return true;
    }

    /**
     * Limpiar caché
     */
    private function clearCache()
    {
        Tools::clearCache();
        if (file_exists(_PS_CLASS_INDEX_FILE_)) {
            @unlink(_PS_CLASS_INDEX_FILE_);
        }
    }

    /**
     * Hook: PRINCIPAL - Actualización de stock_available (SE DISPARA SIEMPRE)
     * Este es el más importante - captura TODOS los cambios de stock
     */
    public function hookActionObjectStockAvailableUpdateAfter($params)
    {
        if (!isset($params['object']) || !($params['object'] instanceof StockAvailable)) {
            return;
        }

        $stock_available = $params['object'];
        $id_product = (int)$stock_available->id_product;
        $id_product_attribute = (int)$stock_available->id_product_attribute;

        // Obtener stock ANTES desde el último registro
        $quantity_before = $this->getLastRecordedStock($id_product, $id_product_attribute);

        // Si no hay registro previo, obtener de BD
        if ($quantity_before === null) {
            // Intentar obtener el valor anterior del objeto
            $quantity_before = isset($stock_available->oldvalues['quantity']) ? (int)$stock_available->oldvalues['quantity'] : (int)$stock_available->quantity;
        }

        // Stock DESPUÉS es el nuevo valor
        $quantity_after = (int)$stock_available->quantity;

        // NO registrar si no hay cambio
        if ($quantity_before == $quantity_after) {
            return;
        }

        // Determinar tipo de movimiento según el contexto
        $context = Context::getContext();
        $movement_type = 'update';
        $reason = 'Actualización de stock';
        $id_order = null;

        // Detectar si es desde el backoffice
        if (isset($context->controller) && $context->controller instanceof AdminController) {
            $controller_name = get_class($context->controller);

            if (strpos($controller_name, 'AdminProducts') !== false) {
                $movement_type = 'manual_update';
                $reason = 'Edición manual desde ficha de producto';
            } elseif (strpos($controller_name, 'AdminStock') !== false) {
                $movement_type = 'manual_update';
                $reason = 'Actualización desde control de stocks';
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
     * Hook: Actualización de producto (PS 1.6+)
     * Se dispara cuando se guarda un producto desde el backoffice
     */
    public function hookActionProductUpdate($params)
    {
        if (!isset($params['id_product'])) {
            return;
        }

        $id_product = (int)$params['id_product'];
        $product = new Product($id_product);

        if (!Validate::isLoadedObject($product)) {
            return;
        }

        // Verificar si el producto tiene combinaciones
        $combinations = $product->getAttributeCombinations($this->context->language->id);

        if (empty($combinations)) {
            // Producto simple - verificar cambio de stock
            $this->checkAndRegisterStockChange($id_product, 0, 'manual_update', 'Edición desde ficha de producto');
        } else {
            // Producto con combinaciones - verificar cada combinación
            $combination_ids = array();
            foreach ($combinations as $combination) {
                $id_product_attribute = (int)$combination['id_product_attribute'];
                if (!in_array($id_product_attribute, $combination_ids)) {
                    $combination_ids[] = $id_product_attribute;
                    $this->checkAndRegisterStockChange($id_product, $id_product_attribute, 'manual_update', 'Edición desde ficha de producto');
                }
            }
        }
    }

    /**
     * Verificar y registrar cambio de stock
     */
    private function checkAndRegisterStockChange($id_product, $id_product_attribute, $movement_type, $reason)
    {
        // Stock ACTUAL en la BD
        $quantity_after = $this->getProductStock($id_product, $id_product_attribute);

        // Último stock registrado
        $quantity_before = $this->getLastRecordedStock($id_product, $id_product_attribute);

        // Si no hay registro previo, no registrar (ya se registró en stock inicial)
        if ($quantity_before === null) {
            return;
        }

        // NO registrar si no hay cambio
        if ($quantity_before == $quantity_after) {
            return;
        }

        // Registrar el cambio
        $this->logStockMovement(
            $id_product,
            $id_product_attribute,
            $quantity_before,
            $quantity_after,
            $movement_type,
            $reason,
            null
        );
    }

    /**
     * Hook: Actualización de cantidad (complementario para pedidos)
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

        // NO registrar si no hay cambio en el stock
        if ($quantity_before == $quantity_after) {
            return;
        }

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
     * Hook: Validación de pedido - Solo para log inicial sin duplicar
     * El override de StockAvailable captura el cambio real de stock
     * Este hook solo añade el id_order al registro que ya existe
     */
    public function hookActionValidateOrder($params)
    {
        // NO hacemos nada aquí para evitar duplicados
        // El override de StockAvailable ya captura el cambio real con stock correcto
        // Ver override/classes/StockAvailable.php
        return;
    }

    /**
     * Hook: Cambio de estado de pedido
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        // Este hook se ejecuta cuando cambia el estado del pedido
        // No necesitamos registrar aquí porque el override de StockAvailable
        // ya capturará el cambio real de stock
        // Este comentario es para documentación
        return;
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
