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

class PayMasterFailModuleFrontController extends ModuleFrontControllerPPM
{
    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('LMI_PAYMENT_NO')
            && Tools::getValue('LMI_MERCHANT_ID') == ConfPPM::getConf('paymaster_merchant_id')) {
            $order = new Order(Tools::getValue('LMI_PAYMENT_NO'));

            if (Validate::isLoadedObject($order)) {
                $link_payment_again = false;
                if (Validate::isLoadedObject($order)) {
                    if ($order->current_state != (int)Configuration::get('PS_OS_ERROR')) {
                        $order->setCurrentState((int)Configuration::get('PS_OS_ERROR'));
                    }
                    $link_payment_again = $this->context->link->getModuleLink(
                        $this->module->name,
                        'paymentagain',
                        array(
                            'id_order' => $order->id
                        )
                    );
                }
                $this->context->smarty->assign('link_payment_again', $link_payment_again);
                $this->context->smarty->assign('path', $this->module->l('fail', 'fail'));

                $this->setTemplate('fail.tpl');
            } else {
                Tools::redirect($this->context->link->getPageLink('index'));
            }
        } else {
            die();
        }
    }
}
