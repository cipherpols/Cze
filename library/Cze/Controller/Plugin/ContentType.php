<?php
/**
 * File ContentType.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Plugin;

/**
 * Class ContentType
 * @package Cze\Controller\Plugin
 */
class ContentType extends \Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopShutdown()
    {
        $headers = headers_list();

        $hasContentType = false;
        foreach ($headers as $head) {
            if (false !== strpos($head, 'Content-Type')) {
                $hasContentType = true;
                break;
            }
        }

        //if we are using Response Headers, they will still not be set
        if ($hasContentType === false) {
            $headers = $this->getResponse()->getHeaders();
            foreach ($headers as $header) {
                if (isset($header['name']) && $header['name'] == 'Content-Type') {
                    $hasContentType = true;
                    break;
                }
            }
        }

        if ($hasContentType === false) {
            $response = $this->getResponse();
            $response->setHeader('Content-Type', 'text/html; charset=utf-8');
        }
    }
}