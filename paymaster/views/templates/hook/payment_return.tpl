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

{if $status == 'ok'}
	<p>{l s='Your order № ' mod='paymaster'} {$id_order|intval} {l s='checkout.' mod='paymaster'}
		<br /><br />{l s='Total order.' mod='paymaster'}<span class="price"><strong>
				{displayPrice price=$total_to_pay|escape:'quotes':'UTF-8'}
			</strong></span>
		<br /><br />{l s='Payment method' mod='paymaster'} <strong>{l s='paymaster' mod='paymaster'}</strong>
	</p>
    {foreach from=$products item=product}
        {if $product.product_quantity > $product.customizationQuantityTotal}
            {if $product.download_hash && $logable && $product.display_filename != '' && $product.product_quantity_refunded == 0 && $product.product_quantity_return == 0}
				<div>
                    {if isset($is_guest) && $is_guest}
					<a href="{$link->getPageLink('get-file', true, NULL, "key={$product.filename|escape:'html':'UTF-8'}-{$product.download_hash|escape:'html':'UTF-8'}&amp;id_order={$order->id}&secure_key={$order->secure_key}")|escape:'html':'UTF-8'}" title="{l s='Download this product' mod='paymaster'}">
                        {else}
						<a href="{$link->getPageLink('get-file', true, NULL, "key={$product.filename|escape:'html':'UTF-8'}-{$product.download_hash|escape:'html':'UTF-8'}")|escape:'html':'UTF-8'}" title="{l s='Download this product' mod='paymaster'}">
                            {/if}
							<img src="{$img_dir|escape:'quotes':'UTF-8'}icon/download_product.gif" class="icon" alt="{l s='Download product' mod='paymaster'}" />
						</a>
                        {if isset($is_guest) && $is_guest}
							<a href="{$link->getPageLink('get-file', true, NULL, "key={$product.filename|escape:'html':'UTF-8'}-{$product.download_hash|escape:'html':'UTF-8'}&id_order={$order->id}&secure_key={$order->secure_key}")|escape:'html':'UTF-8'}" title="{l s='Download this product' mod='paymaster'}">{$product.product_name|escape:'html':'UTF-8'}</a>
                        {else}
							<a href="{$link->getPageLink('get-file', true, NULL, "key={$product.filename|escape:'html':'UTF-8'}-{$product.download_hash|escape:'html':'UTF-8'}")|escape:'html':'UTF-8'}" title="{l s='Download this product' mod='paymaster'}"> {$product.product_name|escape:'html':'UTF-8'}</a>
                        {/if}
				</div>
            {/if}
        {/if}
    {/foreach}
{else}
	<p class="warning">
        {l s='Issue has been identified in your order. If you think this is an error, please' mod='paymaster'}
		<a href="{$link->getPageLink('contact', true)|escape:'quotes':'UTF-8'}">{l s='us' mod='paymaster'}</a>.
	</p>
{/if}
