<div class="panel">
    <div class="panel-header">
        <h3>{l s='Index' mod='kodminasearch'}</h3>
    </div>
    <div class="row">
        <form action="/{$url_index}&fullIndex" method="post">
            <div class="form-group">
                <button type="submit" class="btn btn-default">{l s='Reindex all' mod='kodminasearch'}</button>
            </div>
        </form>
    </div>
</div>
{if Tools::isSubmit('alias')}
    <div class="panel">
        <div class="panel-header">
            {if isset($alias)}
                <h3>{l s='Edit alias' mod='kodminasearch'}</h3>
            {else}
                <h3>{l s='New alias' mod='kodminasearch'}</h3>
            {/if}
        </div>
        <div class="row">
            <form class="col-xs-6" method="post" action="/{$url_index}&alias{if isset($alias)}={$alias['id_alias']}{/if}">
                <div class="form-group">
                    <label>{l s='From' mod='kodminasearch'}</label>
                    <input type="text" name="from" value="{if isset($alias)}{$alias['from']}{/if}" />
                </div>
                <div class="form-group">
                    <label>{l s='To' mod='kodminasearch'}</label>
                    <input type="text" name="to" value="{if isset($alias)}{$alias['to']}{/if}" />
                </div>
                <div class="form-group">
                    <button name="submitAlias" type="submit" class="btn btn-default">{l s='Save' mod='kodminasearch'}</button>
                    <button name="cancelAlias" class="btn btn-default">{l s='Cancel' mod='kodminasearch'}</button>
                </div>
            </form>
        </div>
    </div>
{/if}
{$ALIAS_LIST}

{if Tools::isSubmit('exact')}
    <div class="panel">
        <div class="panel-header">
            {if isset($exact)}
                <h3>{l s='Edit phrase' mod='kodminasearch'}</h3>
            {else}
                <h3>{l s='New phrase' mod='kodminasearch'}</h3>
            {/if}
        </div>
        <div class="row">
            <form class="col-xs-6" method="post" action="/{$url_index}&exact{if isset($exact)}={$exact['id_exact']}{/if}">
                <div class="form-group">
                    <label>{l s='Phrase' mod='kodminasearch'}</label>
                    <input type="text" name="word" value="{if isset($exact)}{$exact['word']}{/if}" />
                </div>
                <div class="form-group">
                    <button name="submitExact" type="submit" class="btn btn-default">{l s='Save' mod='kodminasearch'}</button>
                    <button name="cancelExact" class="btn btn-default">{l s='Cancel' mod='kodminasearch'}</button>
                </div>
            </form>
        </div>
    </div>
{/if}
{$EXACT_LIST}