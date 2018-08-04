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

/**
 * Class ToolsModulePPM
 */
class ToolsModulePPM
{
    public static $languages = array();
    public static function getLanguages($active = true)
    {
        $cache_id = md5($active);
        if (array_key_exists($cache_id, self::$languages)) {
            return self::$languages[$cache_id];
        }
        $languages = Language::getLanguages($active);
        foreach ($languages as &$l) {
            $l['is_default'] = (Configuration::get('PS_LANG_DEFAULT') == $l['id_lang']);
        }
        self::$languages[$cache_id] = $languages;
        return $languages;
    }

    /**
     * @param $module_name string
     * @param $class_name string
     * @param $parent string
     * @param $name mixed
     * @return void
     */
    public static function createTab($module_name, $class_name, $parent = null, $name = 'Tab')
    {
        if (!is_array($name)) {
            $name = array('en' => $name);
        } elseif (is_array($name) && !count($name)) {
            $name = array('en' => $class_name);
        } elseif (is_array($name) && count($name) && !isset($name['en'])) {
            $name['en'] = current($name);
        }

        $tab = new Tab();
        $tab->class_name = $class_name;
        $tab->module = $module_name;
        $tab->id_parent = (!is_null($parent) ? Tab::getIdFromClassName($parent) : 0);
        if (is_null($parent)) {
            self::copyTabIconInRoot($class_name);
        }
        $tab->active = true;
        foreach (self::getLanguages() as $l) {
            $tab->name[$l['id_lang']] = (isset($name[$l['iso_code']]) ? $name[$l['iso_code']] : $name['en']);
        }
        $tab->save();
    }

    public static function copyTabIconInRoot($icon)
    {
        $icon = $icon.'.gif';
        $path = _PS_MODULE_DIR_.basename(dirname(__FILE__)).'/';
        if (!file_exists($path.$icon) && file_exists($path.'views/img/'.$icon)
            && _PS_VERSION_ < 1.6) {
            copy($path.'views/img/'.$icon, $path.$icon);
        }
    }

    /**
     * @param $class_name string
     * @return void
     */
    public static function deleteTab($class_name)
    {
        $tab = Tab::getInstanceFromClassName($class_name);
        if (!Validate::isLoadedObject($tab)) {
            return null;
        }
        $tab->delete();
        self::deleteTab($class_name);
    }

    public static function validateObject($object, $definition_fields = null)
    {
        $errors = array();
        $definition = ObjectModel::getDefinition($object);
        if (is_null($definition_fields)) {
            $definition_fields = $definition['fields'];
        }
        $languages = self::getLanguages(true);
        $t = TransModPPM::getInstance();

        $empty_field = $t->l('%s is empty', __FILE__);
        $empty_lang_field = $t->l('%s for lang %s is empty', __FILE__);

        $wrong_field = $t->l('%s wrong', __FILE__);
        $wrong_lang_field = $t->l('%s for lang %s wrong', __FILE__);

        $max_length_field = $t->l('%s size more %s', __FILE__);
        $max_length_lang_field = $t->l('%s for lang %s size more %s', __FILE__);

        $fields = array_keys($definition_fields);
        foreach ($fields as $field) {
            $l_field = $t->ld($field);
            if (array_key_exists($field, $definition_fields)) {
                $object_field = $object->{$field};
                if (array_key_exists('lang', $definition_fields[$field]) && $definition_fields[$field]['lang']) {
                    foreach ($languages as $lang) {
                        if (isset($definition_fields[$field]['required']) && $definition_fields[$field]['required']
                            && empty($object_field[$lang['id_lang']])) {
                            $errors[] = sprintf($empty_lang_field, $l_field, $lang['name']);
                        }

                        if (!empty($object_field[$lang['id_lang']])
                            && !forward_static_call_array(
                                array('Validate', $definition_fields[$field]['validate']),
                                array(
                                    $object_field[$lang['id_lang']]
                                )
                            )) {
                            $errors[] = sprintf($wrong_lang_field, $l_field, $lang['name']);
                        }

                        if (!empty($object_field[$lang['id_lang']])
                            && forward_static_call_array(
                                array('Validate', $definition_fields[$field]['validate']),
                                array(
                                    $object_field[$lang['id_lang']]
                                )
                            )
                            && array_key_exists('size', $definition_fields[$field])
                            && Tools::strlen($object_field[$lang['id_lang']]) > $definition_fields[$field]['size']) {
                            $errors[] = sprintf(
                                $max_length_lang_field,
                                $l_field,
                                $lang['name'],
                                $definition_fields[$field]['size']
                            );
                        }
                    }
                } else {
                    if (isset($definition_fields[$field]['required'])
                        && $definition_fields[$field]['required']
                        && empty($object_field)
                        && $definition_fields[$field]['type'] != ObjectModel::TYPE_BOOL) {
                        $errors[] = sprintf($empty_field, $l_field);
                    }

                    if (!empty($object_field)
                        && array_key_exists('validate', $definition_fields[$field])
                        && !forward_static_call_array(
                            array('Validate', $definition_fields[$field]['validate']),
                            array(
                                $object_field
                            )
                        )) {
                        $errors[] = sprintf($wrong_field, $l_field);
                    }

                    if (!empty($object_field)
                        && array_key_exists('validate', $definition_fields[$field])
                        && forward_static_call_array(
                            array('Validate', $definition_fields[$field]['validate']),
                            array(
                                $object_field
                            )
                        )
                        && array_key_exists('size', $definition_fields[$field])
                        && Tools::strlen($object_field) > $definition_fields[$field]['size']) {
                        $errors[] = sprintf($max_length_field, $l_field, $definition_fields[$field]['size']);
                    }
                }
            }
        }
        return $errors;
    }

    public static function copyFromPost(&$object, $post_array = null)
    {
        if (!is_null($post_array)) {
            $post = $post_array;
        } else {
            $post = &${'_POST'};
        }

        $definition = ObjectModel::getDefinition($object);
        $table = $definition['table'];
        /* Classical fields */
        foreach ($post as $key => $value) {
            if (key_exists($key, $object) && $key != 'id_'.$table) {
                /* Do not take care of password field if empty */
                if ($key == 'passwd' && Tools::getValue('id_'.$table) && empty($value)) {
                    continue;
                }
                /* Automatically encrypt password in MD5 */
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::encrypt($value);
                }
                $object->{$key} = $value;
            }
        }

        /* Multilingual fields */
        $rules = call_user_func(array(get_class($object), 'getValidationRules'), get_class($object));
        if (count($rules['validateLang'])) {
            $languages = self::getLanguages(false);
            foreach ($languages as $language) {
                foreach (array_keys($rules['validateLang']) as $field) {
                    if (isset($post[$field.'_'.(int)$language['id_lang']])) {
                        $object->{$field}[(int)$language['id_lang']] = $post[$field.'_'.(int)$language['id_lang']];
                    }
                }
            }
        }
    }

    public static $module_name = null;
    public static function getModNameForPath($path)
    {
        if (!is_null(self::$module_name)) {
            return self::$module_name;
        }
        $path = str_replace(_PS_ROOT_DIR_, '', $path);
        $map_dir = explode(DIRECTORY_SEPARATOR, $path);
        $key_module = array_search('modules', $map_dir);
        self::$module_name = $map_dir[$key_module + 1];
        return self::$module_name;
    }

    public static function getTemplateDir($path)
    {
        if (Tools::file_exists_cache(
            _PS_THEME_DIR_.'modules/'
            .self::getModNameForPath(__FILE__).'/views/templates/'.$path
        )) {
            return _PS_THEME_DIR_.'modules/'.self::getModNameForPath(__FILE__).'/views/templates/'.$path;
        } else {
            return _PS_MODULE_DIR_.self::getModNameForPath(__FILE__).'/views/templates/'.$path;
        }
    }

    /**
     * @param $path
     * @param $variables
     *
     * @return string
     */
    public static function fetchTemplate($path, $variables = array())
    {
        Context::getContext()->smarty->assign($variables);
        return Context::getContext()->smarty->fetch(self::getTemplateDir($path));
    }

    /**
     * @void
     */
    public static function registerSmartyFunctions()
    {
        $smarty = Context::getContext()->smarty;
        if (!array_key_exists('no_escape', $smarty->registered_plugins['modifier'])) {
            smartyRegisterFunction(
                $smarty,
                'modifier',
                'no_escape',
                array(__CLASS__, 'noEscape')
            );
        }
        if (!array_key_exists('get_image_lang', $smarty->registered_plugins['function'])) {
            smartyRegisterFunction(
                $smarty,
                'function',
                'get_image_lang',
                array(__CLASS__, 'getImageLang')
            );
        }
        if (!array_key_exists('renderTemplate', $smarty->registered_plugins['function'])) {
            smartyRegisterFunction(
                $smarty,
                'function',
                'renderTemplate',
                array(__CLASS__, 'renderTemplate')
            );
        }
        if (!array_key_exists('categoryImage', $smarty->registered_plugins['function'])) {
            smartyRegisterFunction(
                $smarty,
                'function',
                'categoryImage',
                array(__CLASS__, 'categoryImage')
            );
        }
        if (!array_key_exists('versionCompare', $smarty->registered_plugins['function'])) {
            smartyRegisterFunction(
                $smarty,
                'function',
                'versionCompare',
                array(__CLASS__, 'versionCompare')
            );
        }
        if (!array_key_exists('displayPrice', $smarty->registered_plugins['function'])) {
            smartyRegisterFunction(
                $smarty,
                'function',
                'displayPrice',
                array('Tools', 'displayPriceSmarty')
            );
        }
        if (!array_key_exists('pluralForm', $smarty->registered_plugins['modifier'])) {
            smartyRegisterFunction(
                $smarty,
                'modifier',
                'pluralForm',
                array(__CLASS__, 'pluralForm')
            );
        }
        if (class_exists('TransModPPM')) {
            if (!array_key_exists('ld', $smarty->registered_plugins['modifier'])) {
                smartyRegisterFunction(
                    $smarty,
                    'modifier',
                    'ld',
                    array(TransModPPM::getInstance(), 'ld')
                );
            }
        }
        if (!array_key_exists('dt', $smarty->registered_plugins['modifier'])) {
            smartyRegisterFunction(
                $smarty,
                'modifier',
                'dt',
                array(__CLASS__, 'dateFormatTranslate')
            );
        }
    }

    public static function globalAssignVar()
    {
        Context::getContext()->smarty->assign(array(
            'is_15_ps' => self::is15ps()
        ));
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return array
     */
    public static function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        if (!$files) {
            $files = array();
        }

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $files = array_merge($files, self::globRecursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }

    public static function noEscape($value)
    {
        return $value;
    }

    public static function pluralForm($params, &$smarty)
    {
        $n = $params['n'];
        $form1 = $params['form1'];
        $form2 = $params['form2'];
        $form5 = $params['form5'];

        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) {
            return $form5;
        }
        if ($n1 > 1 && $n1 < 5) {
            return $form2;
        }
        if ($n1 == 1) {
            return $form1;
        }
        unset($smarty);
        return $form5;
    }

    public static function getImageLang($smarty)
    {
        $path = $smarty['path'];
        $module_path = self::getModNameForPath(__FILE__).'/views/img/';
        $module_lang_path = $module_path.Context::getContext()->language->iso_code.'/';
        $module_lang_default_path = $module_path.'en/';
        $path_image = false;
        if (file_exists(_PS_MODULE_DIR_.$module_lang_path.$path)) {
            $path_image = _MODULE_DIR_.$module_lang_path.$path;
        } elseif (file_exists(_PS_MODULE_DIR_.$module_lang_default_path.$path)) {
            $path_image = _MODULE_DIR_.$module_lang_default_path.$path;
        }

        $attrs = '';
        if (isset($smarty['attrs'])) {
            foreach ($smarty['attrs'] as $name => $attr) {
                $attrs .= ' '.$name.'="'.$attr.'"';
            }
        }

        if ($path_image) {
            return '<img src="'.$path_image.'" '.$attrs.'>';
        } else {
            return '[can not load image "'.$path.'"]';
        }
    }

    public static function renderTemplate($smarty)
    {
        $file = $smarty['file'];
        return self::fetchTemplate($file);
    }

    public static function categoryImage($smarty)
    {
        $id_category = $smarty['id'];
        return (Tools::file_exists_cache(_PS_CAT_IMG_DIR_.(int)$id_category.'.jpg')
            || Tools::file_exists_cache(_PS_CAT_IMG_DIR_.(int)$id_category.'_thumb.jpg'))
            ? (int)$id_category : Language::getIsoById(Context::getContext()->language->id).'-default';
    }

    public static function versionCompare($smarty)
    {
        $version = $smarty['v'];
        $operand = $smarty['op'];
        return version_compare(_PS_VERSION_, $version, $operand);
    }

    public static function is15ps()
    {
        return self::isLower('1.6') && !self::isLower('1.5');
    }

    /**
     * @param string $version
     * @return bool
     */
    public static function isGreater($version)
    {
        return version_compare(_PS_VERSION_, $version, '>');
    }

    /**
     * @param string $version
     * @return bool
     */
    public static function isLower($version)
    {
        return version_compare(_PS_VERSION_, $version, '<');
    }

    public static function autoloadCSS($uri_path)
    {
        $full_path = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR
            .self::strReplaceFirst(__PS_BASE_URI__, '', $uri_path);
        $context = Context::getContext();
        $files = self::globRecursive($full_path.'*.css');

        if (is_array($files) && count($files)) {
            foreach ($files as $file) {
                $file_path = str_replace($full_path, '', $file);
                $context->controller->addCSS($uri_path.$file_path);
            }
        }
    }

    public static function autoloadJS($uri_path)
    {
        $full_path = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR
            .self::strReplaceFirst(__PS_BASE_URI__, '', $uri_path);
        $context = Context::getContext();
        $files = self::globRecursive($full_path.'*.js');

        if (is_array($files) && count($files)) {
            foreach ($files as $file) {
                $file_path = str_replace($full_path, '', $file);
                $context->controller->addJS($uri_path.$file_path);
            }
        }
    }

    public static function convertJSONRequestToPost()
    {
        $post = &$_POST;
        $params = Tools::jsonDecode(Tools::file_get_contents('php://input'), true);
        if (is_array($params) && count($params)) {
            foreach ($params as $key => $value) {
                $post[$key] = $value;
            }
        }
    }

    public static function strReplaceFirst($search, $replace, $subject)
    {
        $pos = call_user_func('strpos', $subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, Tools::strlen($search));
        }
        return $subject;
    }

    public static function setErrorHandler()
    {
        if (!_PS_MODE_DEV_) {
            ini_set('display_errors', 'off');
        }
        restore_error_handler();
        set_error_handler(array(__CLASS__, 'errorHandler'));
        register_shutdown_function(array(__CLASS__, 'shutdown'));
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() === 0) {
            return false;
        }

        if (!defined('E_RECOVERABLE_ERROR')) {
            define('E_RECOVERABLE_ERROR', 4096);
        }

        switch ($errno) {
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
            case E_ERROR:
                throw new Exception('Fatal error: '.$errstr.' in '.$errfile.' on line '.$errline);
                //no break
            case E_USER_WARNING:
            case E_WARNING:
                throw new Exception('Error: '.$errstr.' in '.$errfile.' on line '.$errline);
                //no break
            case E_USER_NOTICE:
            case E_NOTICE:
                if (_PS_MODE_DEV_) {
                    throw new Exception('Notice: '.$errstr.' in '.$errfile.' on line '.$errline);
                }
                return true;
            default:
                throw new Exception('Unknown error: '.$errstr.' in '.$errfile.' on line '.$errline);
        }
    }

    public static function shutdown()
    {
        $l = TransModPPM::getInstance();
        if (function_exists('error_get_last')) {
            $error = error_get_last();
            if ($error && $error['type'] === E_ERROR) {
                $message = $error['message'];
                $memory_regex = '/^Allowed memory size of (\d+) bytes exhausted \(tried to allocate (\d+) bytes\)$/u';
                $time_regex = '/^Maximum execution time of (\d+) second exceeded/u';

                if (preg_match($memory_regex, $message, $matches)) {
                    $message = $l->l('Allowed memory size of', __FILE__).' ';
                    $message .= self::convertMemory($matches[1]).' ';
                    $message .= $l->l('exhausted', __FILE__).' (';
                    $message .= $l->l('tried to allocate', __FILE__).' ';
                    $message .= self::convertMemory($matches[2]).' ';
                    $message .= ')';
                    LoggerPPM::getInstance()->error($message);
                    LoggerPPM::getInstance()->error(
                        $l->l('Your web-server is too slow, not enough RAM.', __FILE__)
                    );
                    LoggerPPM::getInstance()->error(
                        $l->l('Try to reduce some of expert\'s settings.', __FILE__)
                    );
                } elseif (preg_match($time_regex, $message, $matches)) {
                    $message = $l->l('Maximum execution time of', __FILE__).' ';
                    $message .= (int)$matches[1].' ';
                    $message .= $l->l('second exceeded', __FILE__);

                    LoggerPPM::getInstance()->error($message);
                    LoggerPPM::getInstance()->error(
                        $l->l('Your web-server is too slow, increase PHP execution time limit.', __FILE__)
                    );
                    LoggerPPM::getInstance()->error(
                        $l->l('Try to reduce some of expert\'s settings.', __FILE__)
                    );
                } else {
                    LoggerPPM::getInstance()->error($message);
                }

                die(Tools::jsonEncode(array(
                    'hasError' => LoggerPPM::getInstance()->hasError(),
                    'log' => LoggerPPM::getInstance()->getMessages()
                )));
            }
        }
        exit;
    }

    public static $memory_units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    /**
     * @param $size
     * @return bool
     */
    public static function convertMemory($size)
    {
        if (!$size) {
            return '0B';
        }

        $i = floor(log($size, 1024));
        $size = round($size / pow(1024, $i), 2);

        return $size.' '.self::$memory_units[(int)$i];
    }

    public static function createAjaxApiCall($class)
    {
        $method = Tools::getValue('method');
        $call_method = 'ajaxProcess'.Tools::toCamelCase($method, 1);
        if (method_exists($class, $call_method)) {
            try {
                $result = call_user_func(array($class, $call_method));
                die(Tools::jsonEncode(array(
                    'hasError' => LoggerPPM::getInstance()->hasError(),
                    'result' => $result,
                    'log' => LoggerPPM::getInstance()->getMessages()
                )));
            } catch (Exception $e) {
                LoggerPPM::getInstance()->exception($e);
                die(Tools::jsonEncode(array(
                    'hasError' => LoggerPPM::getInstance()->hasError(),
                    'log' => LoggerPPM::getInstance()->getMessages()
                )));
            }
        }
    }

    public static function buildSQLSearchWhereFromQuery($query, $detailed_search, $field)
    {
        if (!$query || !$field) {
            return '1';
        }

        if ((int)$detailed_search) {
            return $field.' LIKE "%'.pSQL($query).'%"';
        } else {
            $sql_where = array();
            $words = explode(' ', $query);
            foreach ($words as $word) {
                $sql_where[] = $field.' LIKE "%'.pSQL($word).'%"';
            }
            return implode(' AND ', $sql_where);
        }
    }

    public static function getIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public static function checkImage($tmp_name, $type = array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG))
    {
        return in_array(exif_imagetype($tmp_name), $type);
    }

    public static function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function fileForceContents($dir, $contents)
    {
        if (!file_exists(dirname($dir))) {
            mkdir(dirname($dir), 0777, true);
        }
        if (file_exists($dir)) {
            unlink($dir);
        }
        file_put_contents($dir, $contents);
    }

    public static function checkItemArray($item_name, $array)
    {
        return (is_array($array) && count($array) && array_key_exists($item_name, $array));
    }

    public static function getCookieKey($name, $default_value = '')
    {
        if (isset(Context::getContext()->cookie->{$name})) {
            return Context::getContext()->cookie->{$name};
        }
        return $default_value;
    }

    public static function setCookieKey($name, $value)
    {
        Context::getContext()->cookie->{$name} = $value;
    }

    /**
     * @param $string
     * @return array
     */
    public static function stringToCss($string)
    {
        $css_features = array();

        if (!$string) {
            return $css_features;
        }
        $features = explode(';', $string);

        if (is_array($features) && count($features)) {
            foreach ($features as $feature) {
                list($property, $value) = explode(':', $feature);
                $css_features[trim($property)] = trim($value);
            }
        }

        return $css_features;
    }

    /**
     * @return string
     */
    public static function getModuleTabAdminLink()
    {
        /**
         * @var $module Module
         */
        $module = Module::getInstanceByName(self::getModNameForPath(__FILE__));
        return Context::getContext()->link->getAdminLink(
            'AdminModules',
            true
        ).'&configure='.self::getModNameForPath(__FILE__)
        .'&tab_module='.$module->name.'&tab_module='.$module->tab.'&module_name='.$module->name;
    }

    public static function addCSS($css_uri, $css_media_type = 'all', $offset = null, $check_path = true)
    {
        Context::getContext()->controller->addCSS(_MODULE_DIR_.self::getModNameForPath(__FILE__)
            .'/views/css/'.$css_uri, $css_media_type, $offset, $check_path);
    }

    public static function addJS($js_uri, $check_path = true)
    {
        Context::getContext()->controller->addJS(_MODULE_DIR_.self::getModNameForPath(__FILE__)
            .'/views/js/'.$js_uri, $check_path);
    }

    public static function simpleArrayToInt(&$var)
    {
        if (!is_array($var)) {
            return false;
        }
        foreach ($var as &$item) {
            $item = (int)$item;
        }
    }

    public static function isSerialized($value, &$result = null)
    {
        if (!is_string($value)) {
            return false;
        }

        if ($value === 'b:0;') {
            $result = false;
            return true;
        }

        $length = Tools::strlen($value);
        $end = '';
        switch ($value[0]) {
            case 's':
                if ($value[$length - 2] !== '"') {
                    return false;
                }
                //no break
            case 'b':
            case 'i':
            case 'd':
                $end .= ';';
                //no break
            case 'a':
            case 'O':
                $end .= '}';
                if ($value[1] !== ':') {
                    return false;
                }

                switch ($value[2]) {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                        break;
                    default:
                        return false;
                }
                //no break
            case 'N':
                $end .= ';';
                if ($value[$length - 1] !== $end[0]) {
                    return false;
                }

                break;
            default:
                return false;
        }

        if (($result = @unserialize($value)) === false) {
            $result = null;
            return false;
        }

        return true;
    }

    public static function formatValue($value, $type)
    {
        switch ($type) {
            case ObjectModel::TYPE_INT:
                $value = (int)$value;
                break;
            case ObjectModel::TYPE_STRING:
                $value = (string)$value;
                break;
            case ObjectModel::TYPE_FLOAT:
                $value = (float)$value;
                break;
        }

        return $value;
    }

    public static function dateFormatTranslate($date, $format = null)
    {
        if (is_null($format)) {
            $format = 'H:i:s d-m-Y';
        }
        $l = TransModPPM::getInstance();
        $months = explode(
            '|',
            'January|February|March|April|May|June|July|August|September|October|November|December'
        );
        $mons = explode('|', 'Jan|Feb|Mar|Apr|May|June|July|Aug|Sept|Oct|Nov|Dec');
        $weekdays = explode('|', 'Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday');
        $weeks = explode('|', 'Mon|Tue|Wed|Thu|Fri|Sat|Sun');
        $date_data = array_merge($months, $mons, $weekdays, $weeks);

        $date = date($format, strtotime($date));
        $date = str_replace($date_data, array_map(array($l, 'ld'), $date_data), $date);
        return $date;
    }
}
