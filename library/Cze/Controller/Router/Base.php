<?php
/**
 * File Base.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Router;

/**
 * Class Base
 * @package Cze\Controller\Router
 */
class Base implements \Zend_Controller_Router_Route_Interface
{
    const DEFAULT_ERROR_CONTROLLER = 'error';
    const DEFAULT_ERROR_ACTION = 'index';

    const DEFAULT_MODULE = 'default';
    const DEFAULT_CONTROLLER = 'index';
    const DEFAULT_ACTION = 'index';

    /**
     * @inheritdoc
     */
    public function match($path)
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function assemble($data = array(), $reset = false, $encode = false)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getInstance(\Zend_Config $config)
    {
        return new Cze();
    }
}
