{*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author    Goryachev Dmitry    <dariusakafest@gmail.com>
* @copyright 2007-2017 Goryachev Dmitry
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
    <div class="panel-heading">
        {l s='PayMaster Tax' mod='paymaster'}
    </div>
    <div class="panel-body">
        <div class="row">
            <label class="control-label col-lg-3">{l s='Select tax' mod='paymaster'}</label>
            <div class="col-lg-5">
                <select name="product_tax">
                    {if is_array($taxes) && count($taxes)}
                        {foreach from=$taxes item=tax}
                            <option {if $product_tax == $tax.id}selected{/if} value="{$tax.id|escape:'quotes':'UTF-8'}">{$tax.name|escape:'quotes':'UTF-8'}</option>
                        {/foreach}
                    {/if}
                </select>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('[name="product_tax"]').change(function () {
            $.ajax({
                url: document.location.href.replace('#'+document.location.hash, ''),
                type: 'POST',
                dataType: 'json',
                data: {
                    ppm_ajax: true,
                    method: 'save_product_tax',
                    id_product: {$id_product|intval},
                    tax: $(this).val()
                },
                success: function (json) {
                    if (!json.hasError) {
                        alert(json.result.message);
                    } else {
                        var  errors = [];
                        $.each(json.errors, function (key, error) {
                            if (error.type == 'error') {
                                error.push(error.message);
                            }
                        });
                        alert(errors.join('\n'));
                    }
                }
            });
        });
    });
</script>
