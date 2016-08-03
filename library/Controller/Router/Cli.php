<?php
/**
 * File Cli.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Router;

/**
 * Class Cli
 * @package Cze\Controller\Router
 */
class Cli extends \Zend_Controller_Router_Rewrite
{
    /**
     * assemble the ...
     *
     * @param $userParams
     * @param null $name
     * @param bool $reset
     * @param bool $encode
     * @return string
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true)
    {
        return '';
    }

    /**
     * route
     *
     * @param \Zend_Controller_Request_Abstract $dispatcher
     * @return bool|\Zend_Controller_Request_Abstract
     */
    public function route(\Zend_Controller_Request_Abstract $dispatcher)
    {
        $frontController = $this->getFrontController();

        $module     = $dispatcher->getParam('module', $frontController->getDefaultModule());
        $controller = $dispatcher->getParam('controller', $frontController->getDefaultControllerName());
        $action     = $dispatcher->getParam('action', $frontController->getDefaultAction());

        $dispatcher->setModuleName($module);
        $dispatcher->setControllerName($controller);
        $dispatcher->setActionName($action);

        return $this;
    }
}
