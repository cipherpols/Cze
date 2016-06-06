<?php
/**
 * File Application.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;

use Cze\Application\Base;
use Cze\Application\Error;
use Cze\Controller\Plugin\ContentType;
use Cze\Controller\Plugin\SecureCookies;
use Cze\Session\Adapter\Factory;
use Cze\Session\SaveHandler;
use Cze\Controller\Router\Base as BaseRouter;

/**
 * DeliverIT Application class
 * @author Tuan Duong <duongthaso@gmail.com>
 * @version 1.0
 * @method static bool getPhpSettings()
 * @method static bool getTimeZone()
 * @method static Error getErrorHandler()
 * @method static \Zend_Session_Namespace getSession()
 * @method static \Zend_Controller_Router_Abstract getRouter()
 * @method static \Zend_Controller_Front getFrontController()
 * @method static \Zend_Config getConfig()
 * @method static Log getLog()
 * @method static CacheManager getCache()
 * @method static void getModules()
 * @method static string getTheme()
 */
class Application extends Base
{
    /**
     * @var string|null unique sha1 identified for the current php run
     */
    protected static $phpRunId = null;

    /**
     * Initialize application pre-configuration
     * - Dispatcher
     * - Request
     */
    public function init()
    {
        static::getPhpRunId();
        mb_internal_encoding('utf-8');
        self::getPhpSettings();
        if (false === static::isInCliCall()) {
            self::getSession();
        }

        self::getTimeZone();
        self::getErrorHandler();
        \Zend_Locale::disableCache(true);
    }

    /**
     * @return string
     */
    public static function getPhpRunId()
    {
        if (null === static::$phpRunId) {
            static::$phpRunId = sha1(microtime());
        }

        return static::$phpRunId;
    }

    /**
     * @return bool
     */
    public static function isInCliCall()
    {
        return 'cli' === strtolower(PHP_SAPI);
    }

    /**
     * Initialize PHP Settings
     * @return bool
     */
    protected static function initPhpSettings()
    {
        if (isset(static::$config['phpSettings'])) {
            foreach (static::$config['phpSettings'] as $key => $value) {
                ini_set($key, $value);
            }
        }

        return true;
    }

    /**
     * @return \Zend_Session_Namespace
     * @throws Exception
     * @throws \Exception
     * @throws \Zend_Exception
     */
    protected static function initSession()
    {
        if (static::isInCliCall()) {
            throw new Exception('Application', 'Cli tasks is not allowed to create session data');
        }

        $cfg = \Zend_Registry::get('config');

        if (!$cfg instanceof \Zend_Config) {
            throw new \Exception('Application', 'Configuration not found');
        }

        $sessionConfig = $cfg->session->toArray();
        \Zend_Session::setOptions(
            array(
                'name' => SaveHandler::getSessionPrefix(),
                'gc_maxlifetime' => $sessionConfig['gc_maxlifetime'],
                'remember_me_seconds' => $sessionConfig['remember_me_seconds']
            )
        );

        if (isset($sessionConfig['adapter'])) {
            $saveHandler = new SaveHandler(
                Factory::create($sessionConfig['adapter']),
                SaveHandler::SESSION_TIMEOUT
            );
            \Zend_Session::setSaveHandler($saveHandler);
        }
        $session = new \Zend_Session_Namespace(SaveHandler::SESSION_NAME);
        \Zend_Registry::set(Constants::CZE_SESSION, $session);
        return $session;
    }

    /**
     * Set application timezone
     */
    protected static function initTimezone()
    {
        if (isset(static::$config['app']['timezone'])) {
            date_default_timezone_set(static::$config['app']['timezone']);
        } else {
            date_default_timezone_set('UTC');
        }

        return true;
    }

    /**
     * Initializes the Error Handler
     *
     * @return Error
     */
    protected static function initErrorHandler()
    {
        $errorHandler = new Error();
        $errorHandler->register();

        return $errorHandler;
    }

    /**
     * Initialize SEO Router
     *
     * @return \Zend_Controller_Router_Abstract
     */
    protected static function initRouter()
    {
        $front = \Zend_Controller_Front::getInstance();
        /** @var \Zend_Controller_Router_Rewrite $router */
        $router = $front->getRouter();
        $router->addRoute(Constants::ROUTER_CZE, new Controller\Router\Base());
        \Zend_Registry::set('router', $router);

        return $router;
    }

    /**
     * @return array
     */
    protected static function initConfig()
    {
        return static::$config;
    }

    /**
     * Front Controller Bootstrap
     *
     * @return \Zend_Controller_Front
     */
    protected static function initFrontController()
    {
        $front = \Zend_Controller_Front::getInstance();
        $front->throwExceptions(false);
        $front->setParam('noViewRenderer', true);

        static::getModules();

        $errorPlugin = new \Zend_Controller_Plugin_ErrorHandler();
        $errorPlugin->setErrorHandlerModule(BaseRouter::DEFAULT_MODULE);
        $errorPlugin->setErrorHandlerController(BaseRouter::DEFAULT_ERROR_CONTROLLER);
        $errorPlugin->setErrorHandlerAction(BaseRouter::DEFAULT_ERROR_ACTION);

        $front->registerPlugin($errorPlugin);

        $contentType = new ContentType();
        $secureCookies = new SecureCookies();
        $front->registerPlugin($contentType);
        $front->registerPlugin($secureCookies);

        return $front;
    }

    /**
     * Init modules for Cze framework
     * @return mixed
     * @throws Exception
     */
    protected static function initModules()
    {
        if (!defined('APPLICATION_PATH')) {
            throw new Exception('Application', 'Please define APPLICATION_PATH first');
        }

        $modulePath = APPLICATION_PATH . '/modules/';
        if (!file_exists($modulePath)) {
            throw new Exception('Application', 'Please create modules directory: ' . $modulePath);
        }
        $front = \Zend_Controller_Front::getInstance();
        $front->setDefaultModule(BaseRouter::DEFAULT_MODULE);
        $front->setModuleControllerDirectoryName(Constants::CONTROLLER_DIRECTORY);
        $front->setDefaultAction(BaseRouter::DEFAULT_ACTION);

        $front->addModuleDirectory($modulePath);

        return $front;
    }

    /**
     * Generic Log constructor
     *
     * @throws \Exception
     * @return Log
     */
    protected static function initLog()
    {
        if (!\Zend_Registry::isRegistered(Constants::CZE_LOG)) {
            if (isset(static::$config['log'])) {
                $log = \Zend_Log::factory(static::$config['log']);
                \Zend_Registry::set(Constants::CZE_LOG, $log);
            } else {
                throw new \Exception('Log configuration not found, aborting');
            }
        }

        return \Zend_Registry::get(Constants::CZE_LOG);
    }

    /**
     * Get CacheManager object
     * @return CacheManager
     * @throws Exception
     */
    protected static function initCache()
    {
        if (!\Zend_Registry::isRegistered(Constants::CZE_CACHE)) {
            \Zend_Registry::set(Constants::CZE_CACHE, CacheManager::getInstance());
        }

        return \Zend_Registry::get(Constants::CZE_CACHE);
    }

    protected static function initTheme()
    {
        if (isset(static::$config['application']['theme'])) {
            return static::$config['application']['theme'];
        } else {
            return View::THEME_DEFAULT;
        }
    }
}
