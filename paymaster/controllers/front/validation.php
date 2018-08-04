<?php
/**
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2012-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class PayMasterValidationModuleFrontController extends ModuleFrontControllerPPM
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $customer = $this->context->customer;
        $this->module->validateOrder(
            (int)$cart->id,
            (int)ConfPPM::getConf('status_paymaster'),
            $cart->getOrderTotal(),
            $this->module->displayName,
            null,
            array(),
            (int)$this->context->currency->id,
            false,
            $customer->secure_key
        );

        if ($this->module->currentOrder) {
            $return = $this->module->getPaymentUrl($this->module->currentOrder);
            $this->module->makeSubmitForm($return['url'], $return['params']);
        }
        exit;
    }
}
