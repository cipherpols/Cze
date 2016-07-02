<?php
/**
 * File Config.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Application;
use Cze\Exception;

/**
 * Cze Application Configuration Class
 * @author  Tuan Duong <duongthaso@gmail.com>
 * @package Cze\Application
 */
class Config
{
    /**
     * @var array
     */
    protected static $validEnvironments = array('live', 'staging', 'development', 'testing');

    /**
     * @var array
     */
    protected static $configFiles = array(
        '/configs/application.ini'   => 'global',
        '/configs/{ENV}.ini' => 'env',
    );

    /**
     * @var array
     */
    private static $data = array();

    /**
     * Returns the config array form the multiple config files
     *
     * @param string $env
     * @return array
     * @throws Exception
     */
    public static function getConfig($env)
    {
        if (!in_array($env, static::$validEnvironments)) {
            throw new Exception('Application Config', 'Environment ' . $env . ' is invalid');
        }

        $config = array();
        foreach (self::$configFiles as $configFile => $type) {
            $configFile = dirname(APPLICATION_PATH) . str_replace('{ENV}', $env, $configFile);
            if (file_exists($configFile)) {
                if ('global' == $type) {
                    $local = new \Zend_Config_Ini($configFile, $env);
                } else {
                    $local = new \Zend_Config_Ini($configFile);
                }
                $config = self::merge($config, $local->toArray());
            }
        }

        static::$data = $config;
        return $config;
    }

    /**
     * Get loaded configs from section string
     * @param string|null $section
     * @return mixed
     */
    public static function get($section = '')
    {
        if ($section == '') {
            return static::$data;
        }
        $sectors = explode (".", $section);
        $config = static::$data;
        foreach ($sectors as $sector) {
            if (!isset($config[$sector])) {
                return null;
            }
            $config = $config[$sector];
        }
        return $config;
    }

    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the
     * one of the first array.
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function merge(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = self::merge($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
}
