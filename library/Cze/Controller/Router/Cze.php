<?php
/**
 * File Cze.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Router;

/**
 * Class Cze
 * @package Cze\Controller\Router
 */
class Cze extends \Zend_Controller_Router_Rewrite
{
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
