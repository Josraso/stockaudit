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
        $this->className = 'StockAuditModel';
        $this->lang = false;
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        parent::__construct();

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Eliminar seleccionados'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('¿Eliminar los elementos seleccionados?')
            )
        );

        $this->fields_list = array(
            'id_stock_audit' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'date_add' => array(
                'title' => $this->l('Fecha'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ),
            'product_name' => array(
                'title' => $this->l('Producto'),
                'filter_key' => 'pl!name',
                'callback' => 'formatProductName'
            ),
            'reference' => array(
                'title' => $this->l('Referencia'),
                'filter_key' => 'p!reference'
            ),
            'quantity_before' => array(
                'title' => $this->l('Stock Anterior'),
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ),
            'quantity_after' => array(
                'title' => $this->l('Stock Nuevo'),
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ),
            'quantity_diff' => array(
                'title' => $this->l('Diferencia'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'callback' => 'formatDifference'
            ),
            'movement_type' => array(
                'title' => $this->l('Tipo'),
                'type' => 'select',
                'list' => $this->getMovementTypes(),
                'filter_key' => 'a!movement_type',
                'callback' => 'formatMovementType'
            ),
            'employee_name' => array(
                'title' => $this->l('Usuario'),
                'filter_key' => 'e!firstname',
                'callback' => 'formatEmployee'
            ),
            'movement_source' => array(
                'title' => $this->l('Origen'),
                'filter_key' => 'a!movement_source'
            ),
            'id_order' => array(
                'title' => $this->l('ID Pedido'),
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
        $this->_select = 'a.`id_product`,
            a.`id_product_attribute`,
            a.`id_order`,
            a.`id_employee`,
            a.`quantity_before`,
            a.`quantity_after`,
            a.`quantity_diff`,
            a.`movement_type`,
            a.`movement_source`,
            a.`reason`,
            a.`date_add`,
            a.`user_agent`,
            a.`ip_address`,
            p.`reference`,
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
     * Formatear nombre del producto
     */
    public function formatProductName($value, $row)
    {
        $name = $value;
        
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
                    $name .= '<br><small class="text-muted">' . implode(', ', $attr_names) . '</small>';
                }
            }
        }
        
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
                !empty($row['reference']) ? $row['reference'] : '',
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
