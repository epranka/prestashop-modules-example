<div id="HOOK_CARRIER_FORM">
    <h1 class="page-heading step-num"><span>2</span> {l s='Information' mod='kodminacustomcheckout'}</h1>
    <div class="box">
        <form id="customCheckoutForm" class="std">
            <fieldset>
            	<input autocomplete="off" type="hidden" id="is_new_customer" name="is_new_customer" value="0" />
                <input autocomplete="off" type="hidden" id="form_type" name="form_type" value="small" />
                <input autocomplete="off" type="hidden" id="opc_id_customer" name="opc_id_customer" value="{if isset($customer) && $customer->id}{$customer->id}{else}0{/if}" />
                <input autocomplete="off" type="hidden" id="address1" name="address1" value="{if isset($customerData)}{$customerData->address1|escape:'html':'UTF-8'}{/if}" />
                <input autocomplete="off" type="hidden" id="postcode" name="postcode" value="{if isset($customerData)}{$customerData->postcode|escape:'html':'UTF-8'}{/if}" />
                <input autocomplete="off" type="hidden" id="city" name="city" value="{if isset($customerData)}{$customerData->city|escape:'html':'UTF-8'}{/if}" />
                <input autocomplete="off" type="hidden" id="id_country" name="id_country" value="{if isset($customerData)}{$customerData->id_country}{/if}" />
                <div class="row">
                    <div class="col-md-6">
					    <h3 class="page-subheading top-indent">{l s='Delivery address' mod='kodminacustomcheckout'}</h3>
				        <div id="opc_delivery_address_errors" class="alert alert-danger" style="display:none;"></div>
                        {if !$logged}
                            <div class="required text form-group">
                                <label for="email">{l s='Email' mod='kodminacustomcheckout'} <sup>*</sup></label>
                                <input autocomplete="off" type="email" class="text form-control" id="email" name="email" value="{if isset($customerData)}{$customerData->email|escape:'html':'UTF-8'}{/if}" />
                            </div>
                        {else}
                            <input autocomplete="off" type="hidden" name="email" id="email" value="{if isset($customerData) && $customerData->email}{$customerData->email|escape:'html':'UTF-8'}{/if}" />
                        {/if}
                        <div class="required form-group">
                            <label for="firstname">{l s='First name' mod='kodminacustomcheckout'} <sup>*</sup></label>
                            <input autocomplete="off" type="text" class="text form-control" id="firstname" name="firstname" value="{if isset($customerData)}{$customerData->firstname|escape:'html':'UTF-8'}{/if}" />
                        </div>
                        <div class="required form-group">
                            <label for="firstname">{l s='Last name' mod='kodminacustomcheckout'} <sup>*</sup></label>
                            <input autocomplete="off" type="text" class="text form-control" id="lastname" name="lastname" value="{if isset($customerData)}{$customerData->lastname|escape:'html':'UTF-8'}{/if}" />
                        </div>
                        <div class="required form-group">
                            <label for="phone_mobile">{l s='Mobile phone' mod='kodminacustomcheckout'} <sup>*</sup></label>
                            <input autocomplete="off" type="text" placeholder="+370" class="text form-control" name="phone_mobile" id="phone_mobile" value="{if isset($customerData) && $customerData->phone_mobile}{$customerData->phone_mobile|escape:'html':'UTF-8'}{/if}" />
                        </div>
                        {if !$customer->id || $customer->is_guest}
                            <div class="checkbox">
                                <input autocomplete="off" type="checkbox" name="newsletter" id="newsletter" {if isset($customerData) && $customerData->newsletter} checked="checked" {/if} />
                                <label for="newsletter">{l s='Sign up for our newsletter!' mod='kodminacustomcheckout'}</label>
                            </div>
                        {/if}
                        <div class="checkbox">
                            <label for="need_invoice">
                            <input autocomplete="off" type="checkbox" name="need_invoice" id="need_invoice" {if isset($customerData) && $customerData->need_invoice} checked="checked" {/if} autocomplete="off" />
                            {l s='Please use another address for invoice' mod='kodminacustomcheckout'}</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="invoiceAddressForm" class="invoice_address" {if isset($customerData) && $customerData->need_invoice} style="display: block;" {else} style="display: none;" {/if}>
						    <h3 class="page-subheading top-indent">{l s='Invoice address' mod='kodminacustomcheckout'}</h3>
				            <div id="opc_invoice_address_errors" class="alert alert-danger" style="display:none;"></div>
                            <div class="required text form-group">
                                <label for="company_invoice">{l s='Company' mod='kodminacustomcheckout'} <sup>*</sup></label>
                                <input autocomplete="off" type="text" class="text form-control" id="company_invoice" name="company_invoice" value="{if isset($customerData)}{$customerData->company_invoice|escape:'html':'UTF-8'}{/if}" />
                            </div>
                            <div class="required text form-group">
                                <label for="address1_invoice">{l s='Address' mod='kodminacustomcheckout'} <sup>*</sup></label>
                                <input autocomplete="off" type="text" class="text form-control" id="address1_invoice" name="address1_invoice" value="{if isset($customerData)}{$customerData->address1_invoice|escape:'html':'UTF-8'}{/if}" />
                            </div>
                            <div class="required postcode text form-group">
                                <label for="postcode_invoice">{l s='Zip/Postal code' mod='kodminacustomcheckout'} <sup>*</sup></label>
                                <div class="input-group" style="max-width: 100px;">
                                    <div class="input-group-addon">
                                        <div class="input-group-text">LT-</div>
                                    </div>
                                    <input autocomplete="off" type="text" class="text form-control uniform-input" name="postcode_invoice" id="postcode_invoice" value="{if isset($customerData)}{$customerData->postcode_invoice|escape:'html':'UTF-8'}{/if}"/>
                                </div>
                            </div>
                            <div class="required text form-group">
                                <label for="city_invoice">{l s='City' mod='kodminacustomcheckout'} <sup>*</sup></label>
                                <input autocomplete="off" type="text" class="text form-control" name="city_invoice" id="city_invoice" value="{if isset($customerData)}{$customerData->city_invoice|escape:'html':'UTF-8'}{/if}" />
                            </div>
                            <div class="required text form-group">
                                <label for="dni_invoice">{l s='DNI' mod='kodminacustomcheckout'} <sup>*</sup></label>
                                <input autocomplete="off" type="text" class="text form-control" id="dni_invoice" name="dni_invoice" value="{if isset($customerData)}{$customerData->dni_invoice|escape:'html':'UTF-8'}{/if}" />
                            </div>
                            <div class="required text form-group">
                                <label for="vat_number_invoice">{l s='Vat number' mod='kodminacustomcheckout'} </label>
                                <input autocomplete="off" type="text" class="text form-control" id="vat_number_invoice" name="vat_number_invoice" value="{if isset($customerData)}{$customerData->vat_number_invoice|escape:'html':'UTF-8'}{/if}" />
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
           </form>
    </div>
</div>
