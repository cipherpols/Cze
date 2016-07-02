<?php
/**
 * File Log.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;

/**
 * Class Log
 * @method void warn($message)
 * @method void info($message)
 * @method void debug($message)
 * @method void alert($message)
 * @method void crit($message)
 * @method void emerg($message)
 * @package Cze
 */
class Log extends \Zend_Log
{
    const API = -1;
    const DEFAULT_MAX_LENGTH = 1000;

    /**
     * @var array
     */
    protected $customMaxLength = array(
        self::API => 0,// 0 means without truncation
    );

    /**
     * @inheritdoc
     */
    public function log($message, $priority, $extras = null)
    {
        $logLength = isset($this->customMaxLength[$priority])
            ? $this->customMaxLength[$priority]
            : static::DEFAULT_MAX_LENGTH;

        if ($logLength) {
            $truncatedMessage = array();
            $pieces = explode(PHP_EOL, $message);
            foreach ($pieces as $piece) {
                $len = strlen((string)$piece);
                $truncatedMessage[] = $len > $logLength ? substr($piece, 0, $logLength) . '...' : $piece;

            }
            $message = sizeof($truncatedMessage) == 1 ? $truncatedMessage[0] : implode(PHP_EOL, $truncatedMessage);
        }

        parent::log($message, $priority);
    }
}