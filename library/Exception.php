<?php
/**
 * File Exception.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;


/**
 * Class Exception
 * @package Cze
 */
class Exception extends \Exception
{
    /**
     * @var string
     */
    protected $originalMessage;

    /**
     * Exception constructor
     *
     * @param object|string|null $object
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($object, $message, $code = 0, Exception $previous = null)
    {
        $this->originalMessage = $message;

        if (is_object($object)) {
            $message = get_class($object) . ': ' . $message;
        } elseif (is_string($object)) {
            $message = $object . (empty($message) ? '' : ': ' . $message);
        }

        parent::__construct($message, $code, $previous);
    }


    /**
     * @return string
     */
    public function getOriginalMessage()
    {
        return $this->originalMessage;
    }
}
