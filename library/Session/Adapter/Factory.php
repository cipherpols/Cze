<?php
/**
 * File Factory.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Session\Adapter;
use Cze\Exception;

/**
 * Class Factory
 * @package Cze\Session\Adapter
 */
class Factory
{
    const SESSION = 'Session';
    const MEMCACHED = 'Memcached';
    const REDIS = 'Redis';
    const DATABASE = 'Database';

    /**
     * @var array
     */
    public static $allowedAdapters = array(self::SESSION, self::MEMCACHED, self::REDIS, self::DATABASE);

    /**
     * @var SessionAdapterInterface
     */
    static private $adapter;

    /**
     * Create Session Adapter instance using Factory pattern
     *
     * @param string $adapterName
     * @param string $name
     * @return SessionAdapterInterface
     * @throws Exception
     */
    public static function create($adapterName, $name = '')
    {
        if (!in_array($adapterName, static::$allowedAdapters)) {
            throw new Exception(null, $adapterName . ' is not allowed as Session adapter');
        }

        $adapterName = ucfirst($adapterName);
        switch ($adapterName) {
            case static::SESSION:
                static::$adapter = new Session($name);
                break;
            default:
                throw new Exception(null, $adapterName . ' is not implemented as Session adapter');
                break;
        }

        return static::$adapter;
    }
}
