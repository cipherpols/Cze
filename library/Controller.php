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
     * @var View
     */
    public $view;
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


    /**
     * Disables view
     * to be used on ajax actions and responce an xml
     */
    public function disableView()
    {
        $this->getHelper('viewRenderer')->setNoRender();
    }

    /**
     * @inheritdoc
     */
    public function postDispatch()
    {
        parent::postDispatch();

        // Render view if it was not rendered before
        if (!$this->view->isRendered()) {
            $actionName = $this->getRequest()->getActionName();
            $this->render($actionName);
        }
    }
}
