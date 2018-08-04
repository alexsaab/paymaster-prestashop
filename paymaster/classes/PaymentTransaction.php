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

class PaymentTransaction extends ObjectModelPPM
{
    /**
     * @var int
     */
    public $id_order;

    /**
     * @var string
     */
    public $payment_id;

    public static $definition = array(
        'table' => 'payment_transaction',
        'primary' => 'id_payment_transaction',
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => ValidateTypePPM::IS_INT),
            'payment_id' => array('type' => self::TYPE_STRING, 'validate' => ValidateTypePPM::IS_STRING)
        )
    );

    public static function getInstanceByOrder($id_order)
    {
        $id = (int)Db::getInstance()->getValue(
            'SELECT `'.pSQL(self::$definition['primary'])
            .'` FROM '._DB_PREFIX_.pSQL(self::$definition['table'])
            .' WHERE `id_order` = '.(int)$id_order
        );
        return ($id ? new self($id) : false);
    }

    public static function createTransaction($id_order, $payment_id)
    {
        $object = new self();
        $object->id_order = $id_order;
        $object->payment_id = $payment_id;
        return $object->save();
    }
}
