<?php
/**
 * File Cli.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Response;

/**
 * Class Cli
 * @package Cze\Controller\Response
 */
class Cli extends \Zend_Controller_Response_Cli
{
    /**
     * send headers
     *
     * @return Cli|\Zend_Controller_Response_Abstract
     */
    public function sendHeaders()
    {
        return $this;
    }
}
