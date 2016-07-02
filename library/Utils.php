<?php
/**
 * File Utils.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;

/**
 * Class Utils
 * @package Cze
 */
class Utils
{
    const SQL_DATETIME_FORMAT = 'Y-m-d\TH:i:s';
    const ISO_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Get the current time in format accepted by MySQL DATETIME and TIMESTAMP fields.
     *
     * @return string
     */
    public static function sqlNowTime()
    {
        return static::prepareDateTimeSql('now');
    }

    /**
     * Get datetime in format accepted by MySQL DATETIME and TIMESTAMP fields.
     *
     * @param string $date
     * @return string
     */
    public static function prepareDateTimeSql($date)
    {
        return (new \DateTime($date))->format(static::SQL_DATETIME_FORMAT);
    }
}
