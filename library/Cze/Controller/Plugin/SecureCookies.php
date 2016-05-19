<?php
/**
 * File SecureCookies.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Plugin;

/**
 * Class SecureCookies
 * @package Cze\Controller\Plugin
 */
class SecureCookies extends \Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopShutdown()
    {
        $headers = headers_list();

        foreach ($headers as &$header) {
            if (strpos($header, 'Set-Cookie') === 0
                && strpos($header, '; secure') === false
                && strpos($header, 'XDEBUG_SESSION') === false
            ) {
                $header .= '; secure';
            }

            header($header);
        }
    }
}
