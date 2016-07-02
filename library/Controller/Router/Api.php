<?php
/**
 * File Api.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Router;

/**
 * Class Api
 * @package Cze\Controller\Router
 */
class Api extends \Zend_Controller_Router_Rewrite
{
    const API_MODULE = 'api';

    /**
     * @inheritdoc
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true)
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function route(\Zend_Controller_Request_Abstract $dispatcher)
    {
    }
}
