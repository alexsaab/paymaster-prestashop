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

{extends file="helpers/form/form.tpl"}

{block name="defaultForm"}
    <div class="{if {versionCompare v='1.6.0.0' op='<'}}ps_15{/if}">
        <div class="form-group clearfix">
            <div class="col-lg-12">
                <button class="btn btn-default" onclick="$('.doc_panel').toggle('slow');">
                    {l s='Documentation' mod='paymaster'}
                </button>
            </div>
        </div>
        <div style="display: none" class="doc_panel form-group clearfix">
            <div class="col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        {l s='Documentation' mod='paymaster'}
                    </div>
                    {get_image_lang path='1.jpg' attrs=['class' => 'img-responsive']}
                    {get_image_lang path='2.jpg' attrs=['class' => 'img-responsive']}
                </div>
            </div>
        </div>
    </div>
    {$smarty.block.parent}
{/block}

{block name="input"}
    {if {versionCompare v='1.6.0.0' op='<'} && $input.type == 'html'}
        {$input.html_content|no_escape}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}