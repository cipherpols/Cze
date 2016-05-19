<?php
/**
 * File Base.php
 * @package Cze
 */
namespace Cze\Application;

use Cze\Application;
use Cze\Constants;
use Cze\Controller\Router\Api as ApiRouter;
use Cze\Controller\Router\Base as BaseRouter;
use Cze\Controller\Request\Api as ApiRequest;
use Cze\Exception;

/**
 * Class Base
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze\Application
 */
abstract class Base
{
    const MAGIC_PREFIX = 'init';

    protected static $className = Application::class;

    /**
     * @var Application
     */
    protected static $instance = null;

    /**
     * @var array
     */
    protected static $config = array();

    /**
     * Array of resource objects
     *
     * @var array
     */
    protected static $resources = array();

    /**
     * Array of local resource names mapped to init methods
     *
     * @var array
     */
    protected static $localResources = array();

    /**
     * Constructor
     *
     * @param string $env
     * @param \Zend_Config|array $configData
     * @throws Exception
     */
    public function __construct($env, $configData)
    {
        if (!class_exists(static::$className, false)) {
            throw new Exception($this, 'Class Application not found');
        }

        $config = $this->buildConfigData($env, $configData);

        // manual registers config resource
        $this->saveResource('config', $config);
        static::$config =  $config->toArray();
        \Zend_Registry::set('config', $config);

        static::$instance = $this;
        static::$localResources = $this->discoverLocalResources();
    }

    /**
     * registers a list of local (method-based resources)
     *
     * @return array
     */
    protected function discoverLocalResources()
    {
        $resources = array();
        $methods = new \ReflectionClass($this);
        foreach ($methods->getMethods() as $method) {
            if (mb_substr($method->name, 0, strlen(static::MAGIC_PREFIX)) == static::MAGIC_PREFIX) {
                $resources[] = strtolower(mb_substr($method->name, strlen(static::MAGIC_PREFIX)));
            }
        }

        return $resources;
    }

    /**
     * Dynamic loading/discovery of resources
     *
     * @param string $name
     * @param mixed $arguments Not used
     * @return null
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, 0, 3) == 'get') {
            $name = strtolower(mb_substr($name, 3));
            return static::getResource($name);
        }

        return null;
    }

    /**
     * Checks if a given resource is registered
     *
     * @param string $name
     * @return boolean
     */
    public static function hasResource($name)
    {
        return isset(static::$resources[$name]);
    }

    /**
     * Fetches a resource
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public static function getResource($name)
    {
        if (array_key_exists($name, static::$resources)) {
            return static::$resources[$name];
        } else {
            // not initialized, check if local bootstrap method exists
            if (in_array($name, static::$localResources)) {
                $method = static::MAGIC_PREFIX . ucfirst($name);
                $result = call_user_func(array(static::$className, $method));
                static::$resources[$name] = $result;

                return $result;
            }
        }

        throw new Exception(static::$className, 'Could not identify resource with name ' . $name);
    }

    /**
     * Bootstrap MVC app
     */
    public function init()
    {
    }

    /**
     * Runs MVC Application
     *
     */
    public function run()
    {
        \Zend_Registry::set(Constants::CZE_APPLICATION, static::$instance);

        $this->init();

        static::getRouter();

        try {
            /* @var \Zend_Controller_Front $front */
            $front = static::getFrontController();
            if ($front) {
                if (isset($_SERVER['REQUEST_URI'])) {
                    if (preg_match('#^/api/#', $_SERVER['REQUEST_URI']) === 1) {
                        $this->routeToApi($front);
                    }
                }

                $front->dispatch();
            }
        } catch (\Exception $e) {
            Application::getLog()->crit(Error::toString($e));
            if (APPLICATION_ENV === Constants::ENV_DEVELOPMENT) {
                throw $e;
            }

        }
    }

    /**
     * Singleton initialization
     *
     * @return Base
     * @throws Exception
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            throw new Exception(static::$className, 'Application must be initialized via constructor');
        }

        return static::$instance;
    }

    /**
     * Registers a resource record
     *
     * @param string $name
     * @param mixed $item
     * @param bool $override
     * @throws Exception
     */
    public static function saveResource($name, $item, $override = true)
    {
        $name = strtolower($name);
        if (isset(static::$resources[$name]) && !$override) {
            throw new Exception(static::$instance, 'Resource ' . $name . ' already initialized');
        } else {
            static::$resources[$name] = $item;
        }
    }

    /**
     * @param string $env
     * @param \Zend_Config|array $configData
     * @return \Zend_Config|null
     * @throws Exception
     */
    private function buildConfigData($env, $configData)
    {
        $config = null;
        if (is_string($configData)) {
            $tokens = explode('.', $configData);

            switch (array_pop($tokens)) {
                case 'ini':
                    $config = new \Zend_Config_Ini($configData, $env);
                    break;
                case 'xml':
                    $config = new \Zend_Config_Xml($configData, $env);
                    break;
                case 'json':
                    $config = new \Zend_Config_Json($configData, $env);
                    break;
                case 'php':
                    $content = include $configData;
                    if (is_array($content)) {
                        $config = new \Zend_Config($content);
                    } else {
                        throw new Exception(static::$className, 'PHP config files must return an array');
                    }
                    break;
                default:
                    throw new Exception(static::$className, 'Invalid configuration format for ' . $config);
            }
        } else {
            if (is_array($configData)) {
                $config = new \Zend_Config($configData);
            } else {
                if (!($configData instanceof \Zend_Config)) {
                    throw new Exception(static::$className, 'Invalid Config file format');
                }
            }
        }

        return $config;
    }

    /**
     * Route to APImodule
     * @param \Zend_Controller_Front $front
     */
    private function routeToApi(\Zend_Controller_Front $front)
    {
        $errorPlugin = new \Zend_Controller_Plugin_ErrorHandler();
        $errorPlugin->setErrorHandlerController(BaseRouter::DEFAULT_ERROR_CONTROLLER);
        $errorPlugin->setErrorHandlerAction(BaseRouter::DEFAULT_ERROR_ACTION);
        $errorPlugin->setErrorHandlerModule(ApiRouter::API_MODULE);
        $front->setParam('noViewRenderer', true);
        $front
            ->setRequest(new ApiRequest())
            ->setRouter(new ApiRouter())
            ->registerPlugin($errorPlugin);
    }
}
