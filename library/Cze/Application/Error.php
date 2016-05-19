<?php
/**
 * File Error.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Application;

use Cze\Application;
use Cze\Exception;

/**
 * Class Error
 * @package Cze\Application
 */
class Error
{
    const GENERAL_CLI_ERROR_CODE = 255;

    /**
     * Prints exception to human readable format
     *
     * @param \Exception $exception
     *
     * @return string
     */
    public static function toString(\Exception $exception)
    {
        $tracesAsStringSeparated = explode(PHP_EOL, $exception->getTraceAsString());
        $errorMsg = array();
        $errorMsg[] = '"' . $exception->getMessage() . '" in '
            . $exception->getFile() . ' on line ' . $exception->getLine();
        $errorMsg[] = ' - Exception Class: ' . get_class($exception);
        $errorMsg[] = ' - Error Code     : ' . $exception->getCode();
        $errorMsg[] = ' - Stack trace    : ';
        foreach ($tracesAsStringSeparated as $traceAsStringSeparated) {
            $errorMsg[] = ' -- ' . $traceAsStringSeparated;
        }
        $previousException = $exception->getPrevious();
        if (null !== $previousException) {
            $tracesAsStringSeparated = explode(PHP_EOL, $previousException->getTraceAsString());
            $errorMsg[] = ' - Previous exception:';
            $errorMsg[] = ' -- "' . $previousException->getMessage() . '" in '
                . $previousException->getFile() . ' on line ' . $previousException->getLine();
            $errorMsg[] = ' -- Exception Class: ' . get_class($previousException);
            $errorMsg[] = ' -- Error Code     : ' . $previousException->getCode();
            $errorMsg[] = ' -- Stack trace    : ';
            foreach ($tracesAsStringSeparated as $traceAsStringSeparated) {
                $errorMsg[] = ' --- ' . $traceAsStringSeparated;
            }
        }

        return implode(PHP_EOL, $errorMsg);
    }

    /**
     * Registers the callback handlers
     *
     * @return void
     */
    public function register()
    {
        set_error_handler(array($this, 'errorHandler'));
        register_shutdown_function(array($this, 'shutdownHandler'));
    }

    /**
     * Conversion: Error to Exception
     * Exception is immediately caught, so that it will not be caught somewhere else!
     *
     * @param int $errorNumber
     * @param string $errorMsg
     * @param string $errorFile
     * @param int $errorLine
     *
     * @throws \Exception
     * @throws Exception
     */
    public function errorHandler($errorNumber, $errorMsg, $errorFile, $errorLine)
    {
        $errorMessage = sprintf(
            "%s in %s (%d)",
            $errorMsg,
            $errorFile,
            $errorLine
        );

        // prevent exception to be thrown when error_reporting is turned off or suppressed with @
        if (error_reporting() === 0) {
            Application::getLog()
                ->crit(
                    sprintf(
                        '%s got: %s',
                        __METHOD__,
                        $errorMessage
                    )
                );

        } elseif (class_exists(Exception::class)) {
            throw new Exception($this, $errorMessage);
        } else {
            throw new \Exception($errorMessage);
        }
    }

    /**
     * @throws Exception
     */
    public function shutdownHandler()
    {
        $error = error_get_last();
        if (isset($error)) {
            $errorMessage = sprintf(
                "%s in %s (%d)",
                $error['message'],
                $error['file'],
                $error['line']
            );

            Application::getLog()
                ->crit(
                    sprintf(
                        '%s got: %s',
                        __METHOD__,
                        $errorMessage
                    )
                );

            if (class_exists(Exception::class)) {
                throw new Exception($this, $errorMessage);
            } else {
                throw new \Exception($errorMessage);
            }
        }
    }
}
