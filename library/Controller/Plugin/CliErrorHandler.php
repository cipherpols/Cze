<?php
/**
 * File CliErrorHandler.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Plugin;

use Cze\Application\Error;

/**
 * Class CliErrorHandler
 * @package Cze\Controller\Plugin
 */
class CliErrorHandler extends \Zend_Controller_Plugin_Abstract
{
    /**
     * Flag; are we already inside the error handler loop?
     *
     * @var bool
     */
    protected $isInsideErrorHandlerLoop = false;

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError();
    }

    /**
     * Route shutdown hook -- check for router exceptions
     *
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeShutdown(\Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError();
    }

    /**
     * Post dispatch hook -- check for exceptions and dispatch error handler if
     * necessary
     *
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(\Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError();
    }

    /**
     * Handle errors and exceptions
     *
     * If the 'noErrorHandler' front controller flag has been set, returns early.
     *
     * @return void
     */
    protected function _handleError()
    {
        if ($this->isInsideErrorHandlerLoop) {
            return;
        }

        $response = $this->getResponse();

        // check for an exception
        if ($response->isException()) {
            $this->isInsideErrorHandlerLoop = true;

            // Get exception information
            $exceptions = $response->getException();
            $exception = $exceptions[0];

            if ($exception instanceof \Exception) {
                $message = Error::toString($exception);
                $log = \Zend_Registry::get('log');
                $log->crit($message);
                fputs(STDERR, $message);
            }
        }
    }
}
