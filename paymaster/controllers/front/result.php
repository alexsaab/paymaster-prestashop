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

class PayMasterResultModuleFrontController extends ModuleFrontControllerPPM
{
    public function initContent()
    {
        $server = &${'_SERVER'};
        if ($server['REQUEST_METHOD'] == 'POST') {
            $id_order = Tools::getValue('LMI_PAYMENT_NO');

            $amount = Tools::getValue('LMI_PAYMENT_AMOUNT');
            if (Tools::getValue('LMI_PREREQUEST') == '1'
                || Tools::getValue('LMI_PREREQUEST') == '2') {
                $order = new Order($id_order);
                if (Validate::isLoadedObject($order)) {
                    $cart = new Cart($order->id_cart);
                    $amount_order = number_format(
                        $this->module->getTotalCart($cart),
                        2,
                        '.',
                        ''
                    );

                    if ($amount_order == $amount) {
                        echo 'YES';
                    }
                }

                die();
            } else {
                $hash = $this->module->getHash($id_order);
                $sign = $this->module->getSign(Tools::getValue('LMI_PAYMENT_NO'),
                    Tools::getValue('LMI_PAID_AMOUNT'), Tools::getValue('LMI_CURRENCY'),
                    Tools::getValue('LMI_MERCHANT_ID'), ConfPPM::getConf('paymaster_secret'));

                if ((Tools::getValue('LMI_HASH') == $hash) && (Tools::getValue('SIGN') == $sign)) {
                    $order = new Order($id_order);
                    if (Validate::isLoadedObject($order)) {
                        $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                        PaymentTransaction::createTransaction($order->id, Tools::getValue('LMI_SYS_PAYMENT_ID'));
                    } else {
                        die();
                    }
                } else {
                    die();
                }
            }
        }
        die();
    }
}
