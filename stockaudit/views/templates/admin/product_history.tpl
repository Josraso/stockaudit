{*
* Vista de Historial Completo del Producto - Stock Audit
* Muestra todos los movimientos de stock de un producto específico
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-history"></i> {l s='Historial Completo de Movimientos' mod='stockaudit'}
    </div>

    <div class="alert alert-info">
        <h4><i class="icon-cube"></i> <strong>{$product->name|escape:'html':'UTF-8'}</strong></h4>
        {if $product->reference}
            <p><strong>{l s='Referencia:' mod='stockaudit'}</strong> <code>{$product->reference|escape:'html':'UTF-8'}</code></p>
        {/if}
        {if $combination_name}
            <p><strong>{l s='Combinación:' mod='stockaudit'}</strong> <span class="badge badge-info">{$combination_name|escape:'html':'UTF-8'}</span></p>
        {/if}
        <p><strong>{l s='Total de movimientos:' mod='stockaudit'}</strong> {count($movements)}</p>
    </div>

    {if $movements && count($movements) > 0}
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{l s='Fecha y Hora' mod='stockaudit'}</th>
                        <th class="text-center">{l s='Stock Anterior' mod='stockaudit'}</th>
                        <th class="text-center">{l s='Stock Nuevo' mod='stockaudit'}</th>
                        <th class="text-center">{l s='Diferencia' mod='stockaudit'}</th>
                        <th>{l s='Tipo' mod='stockaudit'}</th>
                        <th>{l s='Razón' mod='stockaudit'}</th>
                        <th>{l s='Usuario' mod='stockaudit'}</th>
                        <th>{l s='Pedido' mod='stockaudit'}</th>
                        <th class="text-center">{l s='Acción' mod='stockaudit'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $movements as $movement}
                        <tr>
                            <td style="white-space: nowrap;">
                                <i class="icon-calendar"></i> {$movement.date_add|date_format:'%d/%m/%Y %H:%M:%S'}
                            </td>
                            <td class="text-center">
                                <span class="badge badge-default">{$movement.quantity_before|escape:'html':'UTF-8'}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-default">{$movement.quantity_after|escape:'html':'UTF-8'}</span>
                            </td>
                            <td class="text-center">
                                {if $movement.quantity_diff > 0}
                                    <span class="badge badge-success">
                                        <i class="icon-arrow-up"></i> +{$movement.quantity_diff|escape:'html':'UTF-8'}
                                    </span>
                                {elseif $movement.quantity_diff < 0}
                                    <span class="badge badge-danger">
                                        <i class="icon-arrow-down"></i> {$movement.quantity_diff|escape:'html':'UTF-8'}
                                    </span>
                                {else}
                                    <span class="badge badge-info">
                                        <i class="icon-minus"></i> 0
                                    </span>
                                {/if}
                            </td>
                            <td>
                                {if isset($movement_types[$movement.movement_type])}
                                    <span class="label label-primary">{$movement_types[$movement.movement_type]|escape:'html':'UTF-8'}</span>
                                {else}
                                    <span class="label label-default">{$movement.movement_type|escape:'html':'UTF-8'}</span>
                                {/if}
                            </td>
                            <td>
                                {if $movement.reason}
                                    <small>{$movement.reason|escape:'html':'UTF-8'|truncate:50}</small>
                                {else}
                                    <small class="text-muted">-</small>
                                {/if}
                            </td>
                            <td>
                                {if $movement.employee_name && trim($movement.employee_name) != ''}
                                    <small><i class="icon-user"></i> {$movement.employee_name|escape:'html':'UTF-8'}</small>
                                {else}
                                    <small class="text-muted"><em>{l s='Sistema/Cliente' mod='stockaudit'}</em></small>
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $movement.id_order && $movement.id_order > 0}
                                    <a href="index.php?controller=AdminOrders&id_order={$movement.id_order|escape:'html':'UTF-8'}&vieworder&token={Tools::getAdminTokenLite('AdminOrders')}"
                                       target="_blank"
                                       class="btn btn-default btn-xs">
                                        <i class="icon-search"></i> #{$movement.id_order|escape:'html':'UTF-8'}
                                    </a>
                                {else}
                                    -
                                {/if}
                            </td>
                            <td class="text-center">
                                <a href="index.php?controller=AdminStockAudit&id_stock_audit={$movement.id_stock_audit|escape:'html':'UTF-8'}&viewstock_audit&token={Tools::getAdminTokenLite('AdminStockAudit')}"
                                   class="btn btn-default btn-xs"
                                   title="{l s='Ver detalles completos' mod='stockaudit'}">
                                    <i class="icon-eye"></i>
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <div class="alert alert-warning">
            <i class="icon-warning"></i> {l s='No se encontraron movimientos para este producto.' mod='stockaudit'}
        </div>
    {/if}

    <div class="panel-footer">
        <a href="{$back_url|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Volver al listado' mod='stockaudit'}
        </a>
    </div>
</div>
