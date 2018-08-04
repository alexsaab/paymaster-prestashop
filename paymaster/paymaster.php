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
 * @author    Goryachev Dmitry    <dariusakafest@gmail.com>
 * @copyright 2007-2017 Goryachev Dmitry
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

require_once(dirname(__FILE__) . '/classes/tools/config.php');

class PayMaster extends ModulePPM
{

    /**
     * @var int
     */
    private $timeout = 80;
    /**
     * @var int
     */
    private $connectionTimeout = 30;
    /**
     * @var bool
     */
    private $keepAlive = true;
    /**
     * @var resource
     */
    private $curl;
    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * Переменная для инициализации платежа
     * @var string
     */
    public $url = 'https://paymaster.ru/Payment/Init';


    public function __construct()
    {
        $this->name = 'paymaster';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.4';
        //$this->currencies = true;
        //$this->currencies_mode = 'radio';
        $this->bootstrap = true;
        $this->author = 'PrestaInfo';
        $this->need_instance = 0;

        parent::__construct();
        $this->documentation = false;

        $this->displayName = $this->l('PayMaster');
        $this->description = $this->l('Allows you to accept payments through PayMaster');
        $this->confirmUninstall = $this->l('Are you sure you want to delete?');

        $this->config = array(
            'paymaster_merchant_id' => '',
            'paymaster_secret' => '',
            'demo' => 0,
            'status_paymaster' => '',
            'disable_tax_shop' => false,
            'tax_delivery' => 'no_vat'
        );

        $this->hooks = array(
            'displayPayment',
            'paymentOptions',
            'displayHeader',
            'displayOrderDetail',
            'displayPaymentReturn',
            'displayAdminOrder',
            'displayAdminProductsExtra',
            'actionProductDelete',
            'displayBackOfficeHeader'
        );

        $this->classes = array(
            'PaymentTransaction'
        );
    }

    public function install()
    {
        return parent::install() && $this->createStatus() && $this->installTables();
    }

    public function uninstall()
    {
        $this->deleteStatus();
        $this->uninstallTables();
        return parent::uninstall();
    }

    public function installTables()
    {
        HelperDbPPM::loadClass('Product')->installManyToOne(
            'tax',
            array(
                'tax' => array('type' => ObjectModelPPM::TYPE_STRING, 'validate' => ValidateTypePPM::IS_STRING)
            )
        );
        return true;
    }

    public function uninstallTables()
    {
        HelperDbPPM::loadClass('Product')->deleteManyToOne('tax');
    }

    public function createStatus()
    {
        $name = array(
            'en' => 'Pending payment PayMaster',
            'ru' => 'В ожидании оплаты PayMaster'
        );

        $order_state = new OrderState();
        foreach (ToolsModulePPM::getLanguages(false) as $l) {
            $order_state->name[$l['id_lang']] = (
            isset($name[$l['iso_code']])
                ? $name[$l['iso_code']]
                : $name['en']
            );
        }

        $order_state->template = '';
        $order_state->send_email = 0;
        $order_state->module_name = $this->name;
        $order_state->invoice = 0;
        $order_state->color = '#4169E1';
        $order_state->unremovable = 0;
        $order_state->logable = 0;
        $order_state->delivery = 0;
        $order_state->hidden = 0;
        $order_state->shipped = 0;
        $order_state->paid = 0;
        $order_state->pdf_invoice = 0;
        $order_state->pdf_delivery = 0;
        $order_state->deleted = 0;
        $result = $order_state->save();
        ConfPPM::setConf('status_paymaster', $order_state->id);
        return $result;
    }

    public function deleteStatus()
    {
        $order_status = new OrderState((int)ConfPPM::getConf('status_paymaster'));
        if (Validate::isLoadedObject($order_status)) {
            $order_status->delete();
        }
    }

    public function postProcess()
    {
        if (Tools::isSubmit('saveSettings')) {
            ConfPPM::setConf(
                'paymaster_merchant_id',
                Tools::getValue(ConfPPM::formatConfName('paymaster_merchant_id'))
            );
            ConfPPM::setConf(
                'paymaster_secret',
                Tools::getValue(ConfPPM::formatConfName('paymaster_secret'))
            );
            ConfPPM::setConf('demo', Tools::getValue(ConfPPM::formatConfName('demo')));
            ConfPPM::setConf('disable_tax_shop', Tools::getValue(ConfPPM::formatConfName('disable_tax_shop')));
            ConfPPM::setConf('tax_delivery', Tools::getValue(ConfPPM::formatConfName('tax_delivery')));
            Tools::redirectAdmin(ToolsModulePPM::getModuleTabAdminLink() . '&conf=6');
        }
    }

    public function getContent()
    {
        $this->postProcess();
        ToolsModulePPM::registerSmartyFunctions();
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin.css');
        $helper_form = new HelperForm();
        $helper_form->bootstrap = true;
        $helper_form->fields_value = array(
            ConfPPM::formatConfName('paymaster_merchant_id') => ConfPPM::getConf('paymaster_merchant_id'),
            ConfPPM::formatConfName('paymaster_secret') => ConfPPM::getConf('paymaster_secret'),
            ConfPPM::formatConfName('demo') => ConfPPM::getConf('demo'),
            ConfPPM::formatConfName('disable_tax_shop') => ConfPPM::getConf('disable_tax_shop'),
            ConfPPM::formatConfName('tax_delivery') => ConfPPM::getConf('tax_delivery')
        );
        $helper_form->submit_action = 'saveSettings';
        $helper_form->module = $this;
        $helper_form->show_toolbar = true;
        $helper_form->toolbar_btn = array(
            'save' => array(
                'title' => $this->l('Save')
            )
        );
        $helper_form->token = Tools::getAdminTokenLite('AdminModules');
        $helper_form->currentIndex = ToolsModulePPM::getModuleTabAdminLink();
        $form = new FormBuilderPPM($this->displayName);
        $form->addField(
            $this->l('Merchant ID'),
            ConfPPM::formatConfName('paymaster_merchant_id'),
            'text'
        );
        $form->addField(
            $this->l('Secret'),
            ConfPPM::formatConfName('paymaster_secret'),
            'text'
        );
        $form->addField(
            $this->l('Test mode?'),
            ConfPPM::formatConfName('demo'),
            'switch'
        );
        $form->addField(
            $this->l('Use tax mode from module(tax mode shop will be disabled)'),
            ConfPPM::formatConfName('disable_tax_shop'),
            'switch'
        );
        $form->addField(
            $this->l('Delivery tax(When enabled tax mode from module)'),
            ConfPPM::formatConfName('tax_delivery'),
            'select',
            null,
            null,
            null,
            null,
            null,
            array(
                'query' => $this->getTaxes(),
                'id' => 'id',
                'name' => 'name'
            )
        );
        $form->addField(
            $this->l('Url invoice confirmation and payment notification'),
            'result_url',
            'html',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            array(
                'html_content' => ToolsModulePPM::fetchTemplate('hook/link.tpl', array(
                    'link' => $this->context->link->getModuleLink($this->name, 'result')
                ))
            )
        );

        $form->addField(
            $this->l('Fail url'),
            'fail_url',
            'html',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            array(
                'html_content' => ToolsModulePPM::fetchTemplate('hook/link.tpl', array(
                    'link' => $this->context->link->getModuleLink($this->name, 'fail')
                ))
            )
        );

        $form->addField(
            $this->l('Success url'),
            'success_url',
            'html',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            array(
                'html_content' => ToolsModulePPM::fetchTemplate('hook/link.tpl', array(
                    'link' => $this->context->link->getModuleLink($this->name, 'success')
                ))
            )
        );

        $form->addSubmit($this->l('Save'));
        return $helper_form->generateForm(array(
            array(
                'form' => $form->getForm()
            )
        ));
    }


    /**
     * Подготовка для получения формы об оплате
     * @param  [type] $id_order [description]
     * @return [type]           [description]
     */
    public function getPaymentUrl($id_order)
    {

        $order = new Order($id_order);
        $cart = new Cart($order->id_cart);

        $currency = new Currency($order->id_currency);
        $order_id = $order->id;
        $description = $this->l('Payment order') . ' №' . $order_id;
        $paymaster_merchant_id = ConfPPM::getConf('paymaster_merchant_id');
        $paymaster_secret = ConfPPM::getConf('paymaster_secret');

        $order_amount = number_format(($order->total_products_wt + $order->total_shipping_tax_incl), 2, '.', '');

        // НЕ правильно, дожно быть iso_code = RUB
        $iso_code = $currency->iso_code;
        if ($iso_code == 'RUR') {
            $iso_code = 'RUB';
        }

        $address = new Address($cart->id_address_delivery);
        $customer = new Customer($order->id_customer);

        $products = $cart->getProducts(true);


        foreach ($products as &$product) {
            $price_item_with_tax = Product::getPriceStatic(
                $product['id_product'],
                true,
                $product['id_product_attribute']
            );
            $price_item_with_tax = number_format(
                $price_item_with_tax,
                2,
                '.',
                ''
            );

            $product['price_item_with_tax'] = $price_item_with_tax;


            if (!ConfPPM::getConf('disable_tax_shop')) {
                if (Configuration::get('PS_TAX')) {
                    $rate = $product['rate'];
                    switch ($rate) {
                        case 10:
                            $product['tax_value'] = 'vat10';
                            break;
                        case 18:
                            $product['tax_value'] = 'vat18';
                            break;
                        default:
                            $product['tax_value'] = 'vat0';
                    }
                } else {
                    $product['tax_value'] = 'no_vat';
                }
            } else {
                $product['tax_value'] = $this->getProductTax($product['id_product']);
            }
        }

        $params = array(
            'LMI_PAYMENT_AMOUNT' => $order_amount,
            'LMI_PAYMENT_DESC' => $description,
            'LMI_PAYMENT_NO' => $order_id,
            'LMI_MERCHANT_ID' => $paymaster_merchant_id,
            'LMI_CURRENCY' => $iso_code,
            'LMI_INVOICE_CONFIRMATION_URL' => $this->context->link->getModuleLink(
                $this->name,
                'result',
                array(),
                true
            ),
            'LMI_PAYMENT_NOTIFICATION_URL' => $this->context->link->getModuleLink(
                $this->name,
                'result',
                array(),
                true
            ),
            'LMI_SUCCESS_URL' => $this->context->link->getModuleLink(
                $this->name,
                'success',
                array(),
                true
            ),
            'LMI_FAILURE_URL' => $this->context->link->getModuleLink(
                $this->name,
                'fail',
                array(),
                true
            ),
            'LMI_PAYER_EMAIL' => $customer->email,
            'SIGN' => $this->getSign($order_id, $order_amount, $iso_code, $paymaster_merchant_id, $paymaster_secret)
        );

        if (ConfPPM::getConf('demo')) {
            $params['LMI_SIM_MODE'] = 1;
        }

        if (is_array($products) && count($products)) {
            $tax_value_shipping = ConfPPM::getConf('tax_delivery');
            $products = $cart->getProducts(true);
            foreach ($products as $key => $product) {
                if (!ConfPPM::getConf('disable_tax_shop')) {
                    $carrier = new Carrier((int)$cart->id_carrier);

                    $tax_value = 'no_vat';
                    if (Configuration::get('PS_TAX')) {
                        $rate = $carrier->getTaxesRate($address);
                        switch ($rate) {
                            case 10:
                                $tax_value = 'vat10';
                                break;
                            case 18:
                                $tax_value = 'vat18';
                                break;
                            default:
                                $tax_value = 'vat0';
                        }
                    }
                } else {
                    $tax_value = ConfPPM::getConf('tax_delivery');
                }

                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].NAME'] = $product['name'];
                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].QTY'] = $product['cart_quantity'];
                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].PRICE'] = number_format($product['price_wt'], 2, '.', '');
                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].TAX'] = $tax_value;
            }

            if ($order->total_shipping_tax_incl) {
                $key++;
                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].NAME'] = $this->l('Shipping');
                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].QTY'] = 1;
                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].PRICE'] = number_format($order->total_shipping_tax_incl, 2, '.', '');
                $params['LMI_SHOPPINGCART.ITEM[' . $key . '].TAX'] = $tax_value_shipping;
            }

        }

        return array('url' => $this->url, 'params' => $params);

    }


    /**
     * Субмит формы с отправкой Post запроса
     * @param $url
     * @param array $data
     */
    public function makeSubmitForm($url, array $params)
    {
        ?>
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <script type="text/javascript">
                function closethisasap() {
                    document.forms["redirectpost"].submit();
                }
            </script>
        </head>
        <body onload="closethisasap();">
        <form name="redirectpost" method="post" action="<? echo $url; ?>">
            <?php
            if (!is_null($params)) {
                foreach ($params as $k => $v) {
                    echo '<input type="hidden" name="' . $k . '" value="' . $v . '"> ';
                }
            }
            ?>
        </form>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Получение хеша - нужно переделывать функцию частично
     * @param  [type] $id_order [description]
     * @return [type]           [description]
     */
    public function getHash($id_order)
    {
        $order = new Order($id_order);
        $currency = new Currency($order->id_currency);

        if (!Validate::isLoadedObject($order)) {
            return false;
        }

        $secret = ConfPPM::getConf('paymaster_secret');
        $paymaster_merchant_id = ConfPPM::getConf('paymaster_merchant_id');

        $order_id = $order->id;
        $iso_code = $currency->iso_code;

        if ($iso_code == 'RUR') {
            $iso_code = 'RUB';
        }

        // Получаем тотальную сумму к оплате
        $order_amount = number_format(($order->total_products_wt + $order->total_shipping_tax_incl), 2, '.', '');

        return base64_encode(
            pack(
                'H*',
                hash(
                    'sha256',
                    $paymaster_merchant_id . ';'
                    . $order_id . ';'
                    . Tools::getValue('LMI_SYS_PAYMENT_ID') . ';'
                    . Tools::getValue('LMI_SYS_PAYMENT_DATE') . ';'
                    . $order_amount . ';'
                    . $iso_code . ';'
                    . Tools::getValue('LMI_PAID_AMOUNT') . ';'
                    . Tools::getValue('LMI_PAID_CURRENCY') . ';'
                    . Tools::getValue('LMI_PAYMENT_SYSTEM') . ';'
                    . Tools::getValue('LMI_SIM_MODE') . ";" . $secret
                )
            )
        );
    }


    /**
     * Генерация дополнительной подписи
     * Она в принципе не нужна совсем так как проверки достачно по HASH
     * но чтобы служба безопасности Paymaster окочательно успокоилась
     * @param $order_id
     * @param $order_amount
     * @param $order_currency
     * @param $mercant_id
     * @param $secret
     * @return bool|string
     */
    public function getSign($order_id, $order_amount, $order_currency, $mercant_id, $secret)
    {
        $order_amount = number_format($order_amount, 2, '.', '');
        return md5("{$order_id};{$order_amount};{$order_currency};{$mercant_id};{$secret}");
    }


    /**
     * Получение общей сумме по заказу тут косячок был
     * @param  [type] $cart [description]
     * @return [type]       [description]
     */
    public function getTotalCart($cart)
    {
        $products = $cart->getProducts(true);
        $total_products = 0;
        foreach ($products as &$product) {
            $price_item_with_tax = Product::getPriceStatic(
                $product['id_product'],
                true,
                $product['id_product_attribute']
            );
            $price_item_with_tax = (float)number_format(
                $price_item_with_tax,
                2,
                '.',
                ''
            );
            $total_products += ($price_item_with_tax * $product['cart_quantity']);
        }

        $total_shipping = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $total_wrapping = $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);

        $amount = number_format(
            $total_products + $total_wrapping + $total_shipping,
            2,
            '.',
            ''
        );
        return $amount;
    }


    public function hookDisplayHeader()
    {
        if (Tools::getValue('LMI_MERCHANT_ID')
            && Tools::getValue('LMI_MERCHANT_ID') == ConfPPM::getConf('paymaster_merchant_id')
            && $this->context->controller instanceof IndexController) {
            if (Tools::getValue('LMI_MERCHANT_ID')) {
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->name,
                        'success',
                        array(
                            'LMI_MERCHANT_ID' => Tools::getValue('LMI_MERCHANT_ID'),
                            'LMI_PAYMENT_NO' => Tools::getValue('LMI_PAYMENT_NO'),
                        ),
                        true
                    )
                );
            } else {
                Tools::redirect(
                    $this->context->link->getModuleLink(
                        $this->name,
                        'fail',
                        array(
                            'LMI_MERCHANT_ID' => Tools::getValue('LMI_MERCHANT_ID'),
                            'LMI_PAYMENT_NO' => Tools::getValue('LMI_PAYMENT_NO')
                        ),
                        true
                    )
                );
            }
        }

        $this->context->controller->addCSS($this->getPathUri() . 'views/css/front.css');
    }

    public function hookDisplayPayment($params)
    {
        $this->context->smarty->assign(array(
            'paymaster' => array(
                'img_dir' => _MODULE_DIR_ . $this->name . '/views/img/',
                'validation' => $this->context->link->getModuleLink(
                    $this->name,
                    'validation'
                )
            ),
        ));
        return ToolsModulePPM::fetchTemplate('hook/payment.tpl');
    }

    /**
     * @return array
     */
    public function hookPaymentOptions()
    {
        $new_option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $new_option->setCallToActionText($this->displayName)->setAction(
            $this->context->link->getModuleLink(
                $this->name,
                'validation'
            )
        )->setAdditionalInformation(
            $this->l('Pay with PayMaster')
        );

        return array($new_option);
    }

    public function hookDisplayOrderDetail($params)
    {
        /**
         * @var Order $order
         */
        $order = $params['order'];
        if ($order->module == $this->name
            && ($order->current_state == (int)Configuration::get('PS_OS_ERROR')
                || $order->current_state == (int)ConfPPM::getConf('status_paymaster'))) {
            $link_payment_again = $this->context->link->getModuleLink($this->name, 'paymentagain', array(
                'id_order' => $order->id
            ));
            $this->context->smarty->assign('link_payment_again', $link_payment_again);
            return $this->display(__FILE__, 'order_detail.tpl');
        } else {
            $payment_transaction = PaymentTransaction::getInstanceByOrder(
                $order->id
            );
            if ($payment_transaction) {
                $this->context->smarty->assign(
                    'payment_transaction',
                    $payment_transaction
                );
                return $this->display(__FILE__, 'order_detail_payment_transaction.tpl');
            }
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        $id_order = (int)$params['id_order'];
        $order = new Order($id_order);
        if (Validate::isLoadedObject($order)) {
            $payment_transaction = PaymentTransaction::getInstanceByOrder(
                $order->id
            );
            if ($payment_transaction) {
                $this->context->smarty->assign(
                    'payment_transaction',
                    $payment_transaction
                );
                return $this->display(__FILE__, 'order_detail_payment_transaction.tpl');
            }
        }
        return '';
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }
        ToolsModulePPM::registerSmartyFunctions();

        /**
         * @var Order $order
         */
        if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }

        $id_order_state = (int)$order->getCurrentState();
        $order_status = new OrderState((int)$id_order_state, (int)$order->id_lang);
        $products = $order->getProducts();
        $customized_datas = Product::getAllCustomizedDatas((int)$order->id_cart);
        Product::addCustomizationPrice($products, $customized_datas);

        $this->context->smarty->assign(array(
            'status' => 'ok',
            'id_order' => $order->id,
            'total_to_pay' => $order->getTotalPaid(),
            'logable' => (bool)$order_status->logable,
            'products' => $products,
            'is_guest' => false
        ));

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        ToolsModulePPM::registerSmartyFunctions();
        $id_product = (int)Tools::getValue('id_product');
        if (isset($params['id_product'])) {
            $id_product = $params['id_product'];
        }

        return ToolsModulePPM::fetchTemplate('hook/admin_products_extra.tpl', array(
            'taxes' => $this->getTaxes(),
            'product_tax' => $this->getProductTax($id_product),
            'id_product' => $id_product
        ));
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('ppm_ajax')) {
            ToolsModulePPM::createAjaxApiCall($this);
        }
    }

    /**
     * Ajax запись налога
     * @return array
     */
    public function ajaxProcessSaveProductTax()
    {
        $id_product = (int)Tools::getValue('id_product');
        $tax = Tools::getValue('tax');
        $this->setProductTax($id_product, $tax);
        return array(
            'message' => $this->l('Save successfully!')
        );
    }

    /**
     * Хук удаляет налог при удалении продукта
     * @param $params
     */
    public function hookActionProductDelete($params)
    {
        $id_product = isset($params['product'])
        && $params['product'] instanceof Product ? $params['product']->id : null;

        if ($id_product) {
            $this->deleteProductTax($id_product);
        }
    }

    /**
     * Получение налога по продукту
     * @param $id_product
     * @return mixed|string
     */
    public function getProductTax($id_product)
    {
        $tax = Db::getInstance()->getValue('SELECT `tax`
        FROM `' . _DB_PREFIX_ . 'product_tax`
        WHERE `id_product` = ' . (int)$id_product);
        return ($tax ? $tax : 'no_vat');
    }

    /**
     * Установка налога для продутка
     * @param $id_product
     * @param $tax
     */
    public function setProductTax($id_product, $tax)
    {
        $this->deleteProductTax($id_product);
        Db::getInstance()->insert('product_tax', array(
            'id_product' => $id_product,
            'tax' => $tax
        ));
    }

    /**
     * Удаление налога из продукта (для чего???)
     * @param $id_product
     */
    public function deleteProductTax($id_product)
    {
        Db::getInstance()->delete('product_tax', 'id_product = ' . (int)$id_product);
    }

    /**
     * Возвращает просто налоги скорее всего для SELECT
     * @return array
     */
    public function getTaxes()
    {
        $t = TransModPPM::getInstance();
        return array(
            array('id' => 'no_vat', 'name' => $t->ld('No tax')),
            array('id' => 'vat0', 'name' => $t->ld('Tax 0%')),
            array('id' => 'vat18', 'name' => $t->ld('Tax 18%')),
            array('id' => 'vat10', 'name' => $t->ld('Tax 10%')),
            array('id' => 'vat118', 'name' => $t->ld('Tax by formula 18/118')),
            array('id' => 'vat110', 'name' => $t->ld('Tax by formula 10/110')),
        );
    }

}
