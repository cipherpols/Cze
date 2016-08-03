<?php
/**
 * File Cli.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Controller\Request;

use Cze\Console\GetOpt;

/**
 * Class Cli
 * @package Cze\Controller\Request
 */
class Cli extends \Zend_Controller_Request_Http
{
    const PARAM_REMAINING_ARGS = '__remaining_args';

    /**
     * initiate request object
     * @param null|bool $clearParams
     */
    public function __construct($clearParams = null)
    {
        if (null !== $clearParams) {
            $this->clearParams();
            return;
        }

        $opts = new GetOpt(
            array(
                'module|m=s'     => 'module name',
                'controller|c-s' => 'controller name',
                'action|a-s'     => 'action name',
                'env|e=s'        => 'environment',
                'help|h-i'       => 'help',
                'force|f-s'      => 'force',
                'verbose|v-i'    => 'verbose level'
            )
        );
        $params = $opts->getParams();
        foreach ($params as $param => $value) {
            $this->setParam($param, $value);
        }
        if (null !== $this->getParam('help', null) && null === $this->getParam('action', null)) {
            $this->setParam('action', 'help');
        }
        $args = $opts->getRemainingArgs();
        if (!empty($args)) {
            $this->setParam(static::PARAM_REMAINING_ARGS, $args);
        }
    }
}
