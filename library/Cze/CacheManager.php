<?php
/**
 * File CacheManager.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;

use Cze\Application\Config;
use Cze\Cache\Backend\Redis;

/**
 * Class CacheManager
 * @package Cze
 */
class CacheManager
{
    /**
     * @var CacheManager
     */
    protected static $instance;

    const PREFIX_PATTERN = 'CZE_CACHE_';

    /**
     * @var string Prefix for narrowing cache scope to single domain / entity
     */
    protected static $keyPrefix;

    /**
     * @var \Zend_Cache_Core|Redis
     */
    private $cacheAdapter;

    private $backends  = array ('File', 'Memcached', 'Libmemcached', 'Redis');

    /**
     * Singleton implementation
     *
     * @return CacheManager
     */
    final public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private constructor
     */
    final private function __construct()
    {
        $this->init();
    }

    /**
     * Object initalize
     * Initializes the cache adapter from application settings
     *
     * @throws Exception
     */
    protected function init()
    {
        $options = Config::get('cache');
        if (!$options) {
            throw new Exception($this, 'Cache configuration not found');
        }

        if (!in_array($options['type'], $this->backends)) {
            throw new Exception($this, 'Backend not found or not supported');
        }

        // if disabled, exit
        if (isset($options['enabled'])) {
            if ((int) $options['enabled'] !== 1) {
                $this->cacheAdapter = null;
                return;
            }
        }

        $frontendOptions = array('automatic_serialization' => true);
        $backendOptions = isset($options['backend']['common']) && count($options['backend']['common']) > 0
            ? $options['backend']['common']
            : array();
        $backendOptions = array_merge($backendOptions, $options['backend'][strtolower($options['type'])]);

        if ($options['type'] == 'Redis') {
            $backend = new Redis($backendOptions);
        } else {
            $backend = $options['type'];
        }
        $this->cacheAdapter = \Zend_Cache::factory(
            'Core',
            $backend,
            $frontendOptions,
            $backendOptions
        );

        $this->createKeyPrefix();

        $this->logMessage('Initialized Backend ' . get_class($this->cacheAdapter));
    }

    /**
     * @return \Zend_Cache_Core
     */
    public function getCacheAdapter()
    {
        return $this->cacheAdapter;
    }

    /**
     * Returns the backend adapter
     *
     * @return \Zend_Cache_Backend
     */
    public function getBackendAdapter()
    {
        return $this->cacheAdapter->getBackend();
    }

    /**
     * Fetch an entry from cache
     *
     * @param string $key
     * @return mixed|NULL
     */
    public function load($key)
    {
        if (null !== $this->cacheAdapter) {
            $prefixedKey = $this->prefixKey($key);
            return $this->cacheAdapter->load($prefixedKey);
        }
        return null;
    }

    /**
     * Writes an entry to cache
     *
     * @param mixed $data
     * @param string $key
     * @param bool|int|null $lifeTime false or 0 means default value, null means maximum lifetime
     * @return boolean
     */
    public function save($data, $key, $lifeTime = 0)
    {
        if (null !== $this->cacheAdapter) {
            $this->logMessage(
                'Saved key ' . $key . ' with size '
                . sizeof($data)
                . ' and lifetime of '
                . $lifeTime
            );
            $prefixedKey = $this->prefixKey($key);
            return $this->cacheAdapter->save($data, $prefixedKey, array(), $lifeTime);
        }
        return false;
    }

    /**
     * Removes a key from the store
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key)
    {
        if (null !== $this->cacheAdapter) {
            $this->logMessage('Removed key ' . $key);
            $prefixedKey = $this->prefixKey($key);
            return $this->cacheAdapter->remove($prefixedKey);
        }
        return false;
    }

    /**
     * Checks the availability of a given tag
     *
     * @param string $key
     * @return boolean
     */
    public function exists($key)
    {
        if (null !== $this->cacheAdapter) {
            $prefixedKey = $this->prefixKey($key);
            return $this->cacheAdapter->test($prefixedKey) !== false;
        }
        return false;
    }

    /**
     * flushes all cache contents
     *
     * @param array $tags
     * @return boolean
     */
    public function flush(array $tags = array())
    {
        if (null !== $this->cacheAdapter) {
            $prefixedKeys = array();
            foreach($tags as $key) {
                $prefixedKeys[] = $this->prefixKey($key);
            }
            return $this->cacheAdapter->clean('all', $prefixedKeys);
        }
        return false;
    }

    /**
     * Returns true if caching is enabled and a cache backend exists
     * @return boolean
     */
    public function cacheEnabled()
    {
        return (null !== $this->cacheAdapter);
    }

    /**
     * Disables the cache system
     */
    public function disableCache()
    {
        $this->cacheAdapter = null;
    }

    /**
     * Redis support
     *
     * @return Redis | null
     */
    public function getRedisAdapter()
    {
        if (isset ($this->cacheAdapter) && (get_class($this->cacheAdapter) == Redis::class)) {
            return $this->cacheAdapter->getAdapter();
        }
        return null;
    }

    /**
     * Logs a message
     * @param string $message
     */
    protected function logMessage($message)
    {
        Application::getLog()->info($message);
    }

    /**
     * Prefixes the passed key with the current class key prefix
     *
     * @param $key
     * @return string
     */
    protected function prefixKey($key)
    {
        return static::$keyPrefix . $key;
    }

    /**
     * Creates the cache key prefix
     */
    protected function createKeyPrefix()
    {
        static::$keyPrefix = static::PREFIX_PATTERN;
    }

    final private function __clone()
    {
    }
}
