<?php
/**
 * File Controller.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;

/**
 * Class Controller
 * @package Cze
 */
abstract class Controller extends \Zend_Controller_Action
{
    /**
     * @var string
     */
    protected $defaultEncoding = 'UTF-8';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initView();
    }

    public function initView()
    {
        if ($this->view === null || !$this->view instanceof View) {
            $module = $this->getRequest()->getModuleName();
            $paths = $this->getFrontController()->getControllerDirectory();
            $modulePath = dirname($paths[$module]);
            $viewBaseDir = $modulePath . DIRECTORY_SEPARATOR . 'views';

            $this->view = new View(
                array(
                    'basePath' => $viewBaseDir,
                    'encoding' => $this->defaultEncoding,
                )
            );
            $this->view->setTheme(Application::getTheme());
            $this->view->setModuleName($module);
        }
        return $this->view;
    }
}
