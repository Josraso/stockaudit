{*
* Vista de Detalle - Stock Audit
* Muestra toda la información de un movimiento de stock
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-eye"></i> {l s='Detalle del Movimiento de Stock' mod='stockaudit'}
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-cube"></i> {l s='Información del Producto' mod='stockaudit'}
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <th style="width: 40%">{l s='Producto:' mod='stockaudit'}</th>
                            <td><strong>{$record.product_name|escape:'html':'UTF-8'}</strong></td>
                        </tr>
                        <tr>
                            <th>{l s='Referencia:' mod='stockaudit'}</th>
                            <td><code>{$reference|escape:'html':'UTF-8'}</code></td>
                        </tr>
                        {if $combination_name}
                        <tr>
                            <th>{l s='Combinación:' mod='stockaudit'}</th>
                            <td><span class="badge badge-info">{$combination_name|escape:'html':'UTF-8'}</span></td>
                        </tr>
                        {/if}
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-bar-chart"></i> {l s='Movimiento de Stock' mod='stockaudit'}
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <th style="width: 40%">{l s='Stock Anterior:' mod='stockaudit'}</th>
                            <td><span class="badge badge-default">{$record.quantity_before|escape:'html':'UTF-8'}</span></td>
                        </tr>
                        <tr>
                            <th>{l s='Stock Nuevo:' mod='stockaudit'}</th>
                            <td><span class="badge badge-default">{$record.quantity_after|escape:'html':'UTF-8'}</span></td>
                        </tr>
                        <tr>
                            <th>{l s='Diferencia:' mod='stockaudit'}</th>
                            <td>
                                {if $record.quantity_diff > 0}
                                    <span class="badge badge-success">+{$record.quantity_diff|escape:'html':'UTF-8'}</span>
                                {elseif $record.quantity_diff < 0}
                                    <span class="badge badge-danger">{$record.quantity_diff|escape:'html':'UTF-8'}</span>
                                {else}
                                    <span class="badge badge-info">0</span>
                                {/if}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-info-circle"></i> {l s='Tipo de Movimiento' mod='stockaudit'}
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <th style="width: 40%">{l s='Tipo:' mod='stockaudit'}</th>
                            <td>
                                {if isset($movement_types[$record.movement_type])}
                                    <span class="label label-primary">{$movement_types[$record.movement_type]|escape:'html':'UTF-8'}</span>
                                {else}
                                    <span class="label label-default">{$record.movement_type|escape:'html':'UTF-8'}</span>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <th>{l s='Origen:' mod='stockaudit'}</th>
                            <td><code>{$record.movement_source|escape:'html':'UTF-8'}</code></td>
                        </tr>
                        {if $record.reason}
                        <tr>
                            <th>{l s='Razón:' mod='stockaudit'}</th>
                            <td>{$record.reason|escape:'html':'UTF-8'}</td>
                        </tr>
                        {/if}
                        {if $record.id_order && $record.id_order > 0}
                        <tr>
                            <th>{l s='Pedido:' mod='stockaudit'}</th>
                            <td>
                                <a href="index.php?controller=AdminOrders&id_order={$record.id_order|escape:'html':'UTF-8'}&vieworder&token={Tools::getAdminTokenLite('AdminOrders')}"
                                   target="_blank"
                                   class="btn btn-default btn-xs">
                                    <i class="icon-eye"></i> #{$record.id_order|escape:'html':'UTF-8'}
                                    {if $record.order_reference}
                                        ({$record.order_reference|escape:'html':'UTF-8'})
                                    {/if}
                                </a>
                            </td>
                        </tr>
                        {/if}
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-user"></i> {l s='Información del Usuario' mod='stockaudit'}
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <th style="width: 40%">{l s='Usuario:' mod='stockaudit'}</th>
                            <td>
                                {if $record.employee_name && trim($record.employee_name) != ''}
                                    <i class="icon-user"></i> {$record.employee_name|escape:'html':'UTF-8'}
                                {else}
                                    <em class="text-muted">{l s='Sistema/Cliente' mod='stockaudit'}</em>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <th>{l s='Fecha y Hora:' mod='stockaudit'}</th>
                            <td><i class="icon-calendar"></i> {$record.date_add|escape:'html':'UTF-8'}</td>
                        </tr>
                        {if $record.user_agent}
                        <tr>
                            <th>{l s='Navegador:' mod='stockaudit'}</th>
                            <td>
                                <small class="text-muted" style="word-break: break-all;">
                                    <i class="icon-desktop"></i> {$record.user_agent|escape:'html':'UTF-8'|truncate:100}
                                </small>
                            </td>
                        </tr>
                        {/if}
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-footer">
        <a href="{$back_url|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Volver al listado' mod='stockaudit'}
        </a>
    </div>
</div>
