<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i> {l s='Estadísticas de Movimientos' mod='stockaudit'}
    </div>
    <div class="panel-body">
        {if isset($stats)}
        <div class="row">
            <div class="col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="icon-exchange"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{l s='Total Movimientos' mod='stockaudit'}</span>
                        <span class="info-box-number">{$stats.total_movements|intval}</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="icon-arrow-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{l s='Incrementos' mod='stockaudit'}</span>
                        <span class="info-box-number">{$stats.increases|intval}</span>
                        <small class="text-muted">(+{$stats.total_added|intval} unidades)</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-red"><i class="icon-arrow-down"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{l s='Decrementos' mod='stockaudit'}</span>
                        <span class="info-box-number">{$stats.decreases|intval}</span>
                        <small class="text-muted">(-{$stats.total_removed|intval} unidades)</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="icon-minus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{l s='Sin cambios' mod='stockaudit'}</span>
                        <span class="info-box-number">{$stats.no_change|intval}</span>
                    </div>
                </div>
            </div>
        </div>
        {/if}
        
        <div class="alert alert-info">
            <i class="icon-info-circle"></i>
            {l s='Utiliza los filtros para buscar movimientos específicos. Puedes exportar los resultados a CSV en cualquier momento.' mod='stockaudit'}
        </div>
    </div>
</div>

<style>
.info-box {
    display: block;
    min-height: 90px;
    background: #fff;
    width: 100%;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    border-radius: 2px;
    margin-bottom: 15px;
}

.info-box-icon {
    border-top-left-radius: 2px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 2px;
    display: block;
    float: left;
    height: 90px;
    width: 90px;
    text-align: center;
    font-size: 45px;
    line-height: 90px;
    background: rgba(0,0,0,0.2);
}

.info-box-icon > i {
    color: #fff;
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 90px;
}

.info-box-number {
    display: block;
    font-weight: bold;
    font-size: 24px;
}

.info-box-text {
    display: block;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bg-aqua {
    background-color: #00c0ef !important;
}

.bg-green {
    background-color: #00a65a !important;
}

.bg-red {
    background-color: #dd4b39 !important;
}

.bg-yellow {
    background-color: #f39c12 !important;
}
</style>
