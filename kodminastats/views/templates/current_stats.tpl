<div class="panel">
    <h3>{l s='Totals at now' mod='kodminastats'}</h3>
    <div class="form-group">
        <label>
            {l s='Products quantity' mod='kodminastats'}
        </label>
        <div>{$currentStats['total_quantity']|number_format:0:".":" "}</div>
    </div>
    <div class="form-group">
        <label>
            {l s='Total wholesale (without tax)' mod='kodminastats'}
        </label>
        <div>{$currentStats['total_wholesale_price']|number_format:2:".":" "} EUR</div>
    </div>
    <div class="form-group">
        <label>
            {l s="Cron job link" mod='kodminastats'}
        </label>
        <div>{$cronJobLink}modules/kodminastats/kodminastats-cron.php?token={substr(Tools::encrypt('kodminastats/cron'), 0, 10)}</div>
    </div>
</div>