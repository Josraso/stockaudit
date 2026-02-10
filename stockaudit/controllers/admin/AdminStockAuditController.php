<?php
/**
 * Stock Audit Admin Controller
 * Controlador para el panel de administración del módulo
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminStockAuditController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'stock_audit';
        $this->lang = false;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();
        $this->identifier = 'id_stock_audit';
        $this->_defaultOrderBy = 'id_stock_audit';
        $this->_defaultOrderWay = 'DESC';

        // Desactivar edición (los registros de auditoría no se editan)
        $this->actions = array('view', 'delete');

        parent::__construct();

        // Forzar ejecución de JOINs y SELECT
        $this->_select();
        $this->_join();

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Eliminar seleccionados'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('¿Eliminar los elementos seleccionados?')
            )
        );

        $this->fields_list = array(
            'date_add' => array(
                'title' => $this->l('Fecha y Hora'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
                'callback' => 'formatDateSpanish'
            ),
            'product_name' => array(
                'title' => $this->l('Producto'),
                'callback' => 'formatProductNameWithHistory'
            ),
            'ref_display' => array(
                'title' => $this->l('Ref.')
            ),
            'stock_change' => array(
                'title' => $this->l('Cambio de Stock'),
                'align' => 'center',
                'callback' => 'formatStockChange',
                'search' => false,
                'orderby' => false
            ),
            'movement_type' => array(
                'title' => $this->l('Tipo'),
                'type' => 'select',
                'list' => $this->getMovementTypes(),
                'filter_key' => 'a!movement_type',
                'callback' => 'formatMovementType'
            ),
            'id_order' => array(
                'title' => $this->l('Pedido'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'callback' => 'formatOrderLink'
            )
        );
    }

    /**
     * Renderizar vista principal
     */
    public function renderList()
    {
        // Botón de exportación personalizado
        $this->toolbar_btn['export'] = array(
            'href' => self::$currentIndex . '&export' . $this->table . '&token=' . $this->token,
            'desc' => $this->l('Exportar a CSV')
        );

        // Añadir filtros de fecha personalizados
        $this->addCustomDateFilters();

        // Añadir estadísticas
        $this->context->smarty->assign(array(
            'stats' => $this->getStatistics()
        ));

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'stockaudit/views/templates/admin/stats.tpl')
            . parent::renderList();
    }

    /**
     * Renderizar vista de detalle
     */
    public function renderView()
    {
        $id_stock_audit = (int)Tools::getValue('id_stock_audit');

        if (!$id_stock_audit) {
            $this->errors[] = $this->l('ID de auditoría inválido');
            return $this->context->smarty->fetch($this->template);
        }

        // Obtener datos completos del registro
        $sql = 'SELECT a.*,
                p.`reference` AS product_reference,
                pa.`reference` AS combination_reference,
                pl.`name` AS product_name,
                CONCAT(e.`firstname`, " ", e.`lastname`) AS employee_name,
                o.`reference` AS order_reference
            FROM `' . _DB_PREFIX_ . 'stock_audit` a
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = a.`id_product`)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pa.`id_product_attribute` = a.`id_product_attribute` AND a.`id_product_attribute` != 0)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.`id_product` = a.`id_product` AND pl.`id_lang` = ' . (int)$this->context->language->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON (e.`id_employee` = a.`id_employee`)
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_order` = a.`id_order`)
            WHERE a.`id_stock_audit` = ' . $id_stock_audit;

        $record = Db::getInstance()->getRow($sql);

        if (!$record) {
            $this->errors[] = $this->l('Registro no encontrado');
            return $this->context->smarty->fetch($this->template);
        }

        // Obtener información de combinación si existe
        $combination_name = '';
        if (!empty($record['id_product_attribute']) && $record['id_product_attribute'] > 0) {
            $combination = new Combination($record['id_product_attribute']);
            if (Validate::isLoadedObject($combination)) {
                $attributes = $combination->getAttributesName($this->context->language->id);
                if (is_array($attributes) && count($attributes) > 0) {
                    $attr_names = array();
                    foreach ($attributes as $attr) {
                        $attr_names[] = $attr['name'];
                    }
                    $combination_name = implode(', ', $attr_names);
                }
            }
        }

        // Determinar referencia a mostrar
        $reference = !empty($record['combination_reference']) ? $record['combination_reference'] : $record['product_reference'];

        // Asignar variables a Smarty
        $this->context->smarty->assign(array(
            'record' => $record,
            'combination_name' => $combination_name,
            'reference' => $reference,
            'movement_types' => $this->getMovementTypes(),
            'back_url' => self::$currentIndex . '&token=' . $this->token
        ));

        // Cargar y retornar el template
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'stockaudit/views/templates/admin/view.tpl');
    }

    /**
     * Renderizar historial completo del producto
     */
    public function initContent()
    {
        if (Tools::getValue('viewproduct')) {
            $this->display = 'viewproduct';
            $this->content = $this->renderProductHistory();
            return;
        }

        parent::initContent();
    }

    /**
     * Mostrar historial completo de un producto
     */
    public function renderProductHistory()
    {
        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');

        if (!$id_product) {
            $this->errors[] = $this->l('ID de producto inválido');
            return $this->context->smarty->fetch($this->template);
        }

        // Obtener información del producto
        $product = new Product($id_product, false, $this->context->language->id);
        if (!Validate::isLoadedObject($product)) {
            $this->errors[] = $this->l('Producto no encontrado');
            return $this->context->smarty->fetch($this->template);
        }

        // Obtener combinación si existe
        $combination_name = '';
        if ($id_product_attribute > 0) {
            $combination = new Combination($id_product_attribute);
            if (Validate::isLoadedObject($combination)) {
                $attributes = $combination->getAttributesName($this->context->language->id);
                if (is_array($attributes) && count($attributes) > 0) {
                    $attr_names = array();
                    foreach ($attributes as $attr) {
                        $attr_names[] = $attr['name'];
                    }
                    $combination_name = implode(', ', $attr_names);
                }
            }
        }

        // Obtener TODOS los movimientos del producto
        $sql = 'SELECT a.*,
                IFNULL(pa.`reference`, p.`reference`) AS `reference`,
                pl.`name` AS `product_name`,
                CONCAT(IFNULL(e.`firstname`, ""), " ", IFNULL(e.`lastname`, "")) AS `employee_name`,
                o.`reference` AS `order_reference`
            FROM `' . _DB_PREFIX_ . 'stock_audit` a
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = a.`id_product`)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pa.`id_product_attribute` = a.`id_product_attribute` AND a.`id_product_attribute` != 0)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.`id_product` = a.`id_product` AND pl.`id_lang` = ' . (int)$this->context->language->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON (e.`id_employee` = a.`id_employee`)
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_order` = a.`id_order`)
            WHERE a.`id_product` = ' . $id_product;

        if ($id_product_attribute > 0) {
            $sql .= ' AND a.`id_product_attribute` = ' . $id_product_attribute;
        }

        $sql .= ' ORDER BY a.`date_add` DESC';

        $movements = Db::getInstance()->executeS($sql);

        // Asignar variables a Smarty
        $this->context->smarty->assign(array(
            'product' => $product,
            'combination_name' => $combination_name,
            'movements' => $movements,
            'movement_types' => $this->getMovementTypes(),
            'back_url' => self::$currentIndex . '&token=' . $this->token
        ));

        // Cargar y retornar el template
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'stockaudit/views/templates/admin/product_history.tpl');
    }

    /**
     * Consulta SQL personalizada
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    /**
     * Query SELECT personalizada
     */
    protected function _select()
    {
        $this->_select = 'IFNULL(pa.`reference`, p.`reference`) AS `ref_display`,
            IFNULL(pl.`name`, "Producto eliminado") AS `product_name`,
            CONCAT(IFNULL(e.`firstname`, ""), " ", IFNULL(e.`lastname`, "")) AS `employee_name`';
    }

    /**
     * Query JOIN personalizada
     */
    protected function _join()
    {
        $id_lang = (int)$this->context->language->id;

        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = a.`id_product`)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pa.`id_product_attribute` = a.`id_product_attribute` AND a.`id_product_attribute` != 0)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.`id_product` = a.`id_product` AND pl.`id_lang` = ' . $id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON (e.`id_employee` = a.`id_employee`)';
    }

    /**
     * Query GROUP BY personalizada
     */
    protected function _group()
    {
        // No necesitamos GROUP BY ya que cada registro es único
        $this->_group = '';
    }

    /**
     * Tipos de movimiento
     */
    private function getMovementTypes()
    {
        return array(
            'initial_stock' => $this->l('Stock inicial'),
            'order' => $this->l('Pedido'),
            'order_validation' => $this->l('Validación de pedido'),
            'manual_update' => $this->l('Actualización manual'),
            'update' => $this->l('Actualización'),
            'stock_movement' => $this->l('Movimiento de stock'),
            'import' => $this->l('Importación'),
            'return' => $this->l('Devolución'),
            'correction' => $this->l('Corrección'),
            'unknown' => $this->l('Desconocido')
        );
    }

    /**
     * Formatear fecha en español
     */
    public function formatDateSpanish($value, $row)
    {
        if (empty($value)) {
            return '-';
        }

        // Convertir a timestamp
        $timestamp = strtotime($value);

        // Formato: dd/mm/yyyy HH:mm:ss
        return date('d/m/Y H:i:s', $timestamp);
    }

    /**
     * Formatear cambio de stock visual
     */
    public function formatStockChange($value, $row)
    {
        $before = (int)$row['quantity_before'];
        $after = (int)$row['quantity_after'];
        $diff = (int)$row['quantity_diff'];

        $color_class = 'info';
        $icon = 'arrows-h';
        $sign = '';

        if ($diff > 0) {
            $color_class = 'success';
            $icon = 'arrow-up';
            $sign = '+';
        } elseif ($diff < 0) {
            $color_class = 'danger';
            $icon = 'arrow-down';
        }

        return '<span style="white-space: nowrap;">
                    <strong>' . $before . '</strong>
                    <i class="icon-arrow-right text-muted"></i>
                    <strong>' . $after . '</strong>
                    <span class="badge badge-' . $color_class . '" style="margin-left: 5px;">
                        <i class="icon-' . $icon . '"></i> ' . $sign . $diff . '
                    </span>
                </span>';
    }

    /**
     * Formatear nombre del producto con botón de historial
     */
    public function formatProductNameWithHistory($value, $row)
    {
        $name = '<strong>' . $value . '</strong>';

        // Si tiene combinación, añadirla
        if (!empty($row['id_product_attribute']) && $row['id_product_attribute'] > 0) {
            $combination = new Combination($row['id_product_attribute']);
            if (Validate::isLoadedObject($combination)) {
                $attributes = $combination->getAttributesName($this->context->language->id);
                if (is_array($attributes) && count($attributes) > 0) {
                    $attr_names = array();
                    foreach ($attributes as $attr) {
                        $attr_names[] = $attr['name'];
                    }
                    $name .= '<br><small class="text-muted"><i class="icon-sitemap"></i> ' . implode(', ', $attr_names) . '</small>';
                }
            }
        }

        // Añadir botón para ver historial completo del producto
        $history_url = self::$currentIndex . '&viewproduct&id_product=' . (int)$row['id_product'];
        if (!empty($row['id_product_attribute']) && $row['id_product_attribute'] > 0) {
            $history_url .= '&id_product_attribute=' . (int)$row['id_product_attribute'];
        }
        $history_url .= '&token=' . $this->token;

        $name .= '<br><a href="' . $history_url . '" class="btn btn-default btn-xs" style="margin-top: 3px;">
                    <i class="icon-history"></i> ' . $this->l('Ver historial completo') . '
                  </a>';

        return $name;
    }

    /**
     * Formatear diferencia de cantidad
     */
    public function formatDifference($value, $row)
    {
        $diff = (int)$value;
        
        if ($diff > 0) {
            return '<span class="badge badge-success">+' . $diff . '</span>';
        } elseif ($diff < 0) {
            return '<span class="badge badge-danger">' . $diff . '</span>';
        } else {
            return '<span class="badge badge-info">0</span>';
        }
    }

    /**
     * Formatear tipo de movimiento
     */
    public function formatMovementType($value, $row)
    {
        $types = $this->getMovementTypes();
        $type = isset($types[$value]) ? $types[$value] : $value;
        
        $colors = array(
            'initial_stock' => 'primary',
            'order' => 'info',
            'order_validation' => 'primary',
            'manual_update' => 'warning',
            'update' => 'default',
            'stock_movement' => 'info',
            'import' => 'success',
            'return' => 'info',
            'correction' => 'warning',
            'unknown' => 'default'
        );
        
        $color = isset($colors[$value]) ? $colors[$value] : 'default';
        
        return '<span class="label label-' . $color . '">' . $type . '</span>';
    }

    /**
     * Formatear empleado
     */
    public function formatEmployee($value, $row)
    {
        if (empty($value) || trim($value) == '') {
            return '<em class="text-muted">' . $this->l('Sistema/Cliente') . '</em>';
        }
        
        return $value;
    }

    /**
     * Formatear enlace a pedido
     */
    public function formatOrderLink($value, $row)
    {
        if (empty($value) || $value == 0) {
            return '-';
        }
        
        $link = $this->context->link->getAdminLink('AdminOrders') . '&id_order=' . (int)$value . '&vieworder';
        
        return '<a href="' . $link . '" target="_blank">#' . (int)$value . '</a>';
    }

    /**
     * Obtener estadísticas
     */
    private function getStatistics()
    {
        // EXCLUIR registros de stock inicial de las estadísticas
        $sql = 'SELECT 
            COUNT(*) as total_movements,
            SUM(CASE WHEN quantity_diff > 0 AND movement_type != "initial_stock" THEN 1 ELSE 0 END) as increases,
            SUM(CASE WHEN quantity_diff < 0 AND movement_type != "initial_stock" THEN 1 ELSE 0 END) as decreases,
            SUM(CASE WHEN quantity_diff = 0 OR movement_type = "initial_stock" THEN 1 ELSE 0 END) as no_change,
            SUM(CASE WHEN quantity_diff > 0 AND movement_type != "initial_stock" THEN quantity_diff ELSE 0 END) as total_added,
            SUM(CASE WHEN quantity_diff < 0 AND movement_type != "initial_stock" THEN ABS(quantity_diff) ELSE 0 END) as total_removed
        FROM `' . _DB_PREFIX_ . 'stock_audit`';
        
        // Aplicar filtros de fecha si existen
        $where = array();
        
        if ($date_from = Tools::getValue('date_add_from')) {
            $where[] = 'date_add >= "' . pSQL($date_from) . ' 00:00:00"';
        }
        
        if ($date_to = Tools::getValue('date_add_to')) {
            $where[] = 'date_add <= "' . pSQL($date_to) . ' 23:59:59"';
        }
        
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Añadir filtros de fecha personalizados
     */
    private function addCustomDateFilters()
    {
        // Aquí podrías añadir filtros personalizados de fecha si lo necesitas
    }

    /**
     * Procesar exportación
     */
    public function processExport($text_delimiter = '"')
    {
        // Obtener datos completos sin límite
        $this->getList(
            (int)$this->context->language->id,
            $this->table_id,
            'DESC',
            0,
            false,
            false
        );

        if (!count($this->_list)) {
            return;
        }

        // Configurar headers para descarga
        header('Content-type: text/csv');
        header('Content-Type: application/force-download; charset=UTF-8');
        header('Cache-Control: no-store, no-cache');
        header('Content-disposition: attachment; filename="' . $this->table . '_' . date('Y-m-d_His') . '.csv"');

        // BOM para UTF-8
        echo "\xEF\xBB\xBF";

        $fd = fopen('php://output', 'w');

        // Cabeceras
        $headers = array(
            $this->l('ID'),
            $this->l('Fecha'),
            $this->l('Producto'),
            $this->l('Referencia'),
            $this->l('Combinación'),
            $this->l('Stock Anterior'),
            $this->l('Stock Nuevo'),
            $this->l('Diferencia'),
            $this->l('Tipo de Movimiento'),
            $this->l('Origen'),
            $this->l('Usuario'),
            $this->l('ID Pedido'),
            $this->l('Motivo'),
            $this->l('IP'),
            $this->l('User Agent')
        );

        fputcsv($fd, $headers, ';', $text_delimiter);

        // Datos
        foreach ($this->_list as $row) {
            // Obtener nombre de combinación si existe
            $combination_name = '';
            if (!empty($row['id_product_attribute']) && $row['id_product_attribute'] > 0) {
                $combination = new Combination($row['id_product_attribute']);
                if (Validate::isLoadedObject($combination)) {
                    $attributes = $combination->getAttributesName($this->context->language->id);
                    if (is_array($attributes) && count($attributes) > 0) {
                        $attr_names = array();
                        foreach ($attributes as $attr) {
                            $attr_names[] = $attr['name'];
                        }
                        $combination_name = implode(', ', $attr_names);
                    }
                }
            }

            $data = array(
                $row['id_stock_audit'],
                $row['date_add'],
                $row['product_name'],
                !empty($row['ref_display']) ? $row['ref_display'] : '',
                $combination_name,
                $row['quantity_before'],
                $row['quantity_after'],
                $row['quantity_diff'],
                $row['movement_type'],
                $row['movement_source'],
                !empty($row['employee_name']) && trim($row['employee_name']) != '' ? $row['employee_name'] : 'Sistema/Cliente',
                !empty($row['id_order']) ? $row['id_order'] : '',
                !empty($row['reason']) ? $row['reason'] : '',
                !empty($row['ip_address']) ? $row['ip_address'] : '',
                !empty($row['user_agent']) ? $row['user_agent'] : ''
            );

            fputcsv($fd, $data, ';', $text_delimiter);
        }

        fclose($fd);
        exit;
    }
}
