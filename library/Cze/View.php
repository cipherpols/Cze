<?php
/**
 * File View.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;

/**
 * Class View
 *
 * @method \Zend_View_Helper_HeadLink headLink
 * @method \Zend_View_Helper_HeadScript headScript
 * @property Exception $exception
 * @property string $flashMessages
 * @property int $errorCode
 * @property string $errorMessage
 * @property bool $displayExceptions
 * @property string $metaTitle
 * @property \Zend_Controller_Request_Abstract $request
 * @package Cze
 */
class View extends \Zend_View
{
    const THEME_DEFAULT = 'default';
    const THEME_CIPHERPOLS = 'cipherpols';
    const LAYOUT_DEFAULT = 'main';
    const LAYOUT_LOGIN  = 'login';

    const VIEW_SUFFIX = 'phtml';

    const ASSET_JS = 'js';
    const ASSET_CSS = 'css';
    const ASSET_DOCLOAD_JS = 'docloadjs';
    const ASSET_INLINE_JS = 'inlinejs';
    const ASSET_INLINE_CSS = 'inlinecss';
    const ASSET_META = 'meta';

    /**
     * Assets variables
     *
     * @var array
     */
    protected $_assets = array(
        self::ASSET_JS          => array(),
        self::ASSET_CSS         => array(),
        self::ASSET_DOCLOAD_JS  => array(),
        self::ASSET_INLINE_JS   => array(),
        self::ASSET_INLINE_CSS  => array(),
        self::ASSET_META        => array(),
    );

    /**
     * Valid asset types
     *
     * @var array
     */
    protected $_validAssetTypes = array(
        self::ASSET_JS,
        self::ASSET_CSS,
        self::ASSET_DOCLOAD_JS,
        self::ASSET_INLINE_JS,
        self::ASSET_INLINE_CSS,
        self::ASSET_META

    );

    /**
     * template manifest file name
     * @var string
     */
    private $_manifest = 'manifest.php';

    /**
     * @var string
     */
    private $moduleName;

    /**
     * Base URL
     * @var string
     */
    private $_baseUrl;

    /**
     * @var string
     */
    protected $_viewScript = '';

    /**
     * @var string
     */
    private $_scriptResult = '';

    /**
     * @var
     */
    private $_theme = '';

    /**
     * @var bool
     */
    private $_skipTheme = false;

    /**
     * @var string
     */
    private $_layout = '';

    /**
     * @var bool
     */
    private $_skipLayout = false;

    /**
     * @var string
     */
    private $_templatePath = '';

    /**
     * @var string
     */
    private $_templateBasePath = '';

    /**
     * Template configuration
     *
     * @var array
     */
    private $_templateOptions = array();

    public function init()
    {
        parent::init();
        $this->addHelperPath(VENDOR_PATH . '/zendframework/zendframework1/library/Zend/View/Helper/', 'Zend_View_Helper');
        $this->addHelperPath(VENDOR_PATH . '/cipherpols/cze/library/Cze/View/Helper/', '\\Cze\\View\\Helper');
    }

    /**
     * Return the base url
     * @return string
     */
    public function getBaseUrl()
    {
        if (!$this->_baseUrl) {
            $this->_baseUrl = Application::getFrontController()->getRequest()->getBaseUrl();
        }

        return $this->_baseUrl;
    }

    /**
     * Append style sheets
     * @param array $scripts
     */
    public function appendStylesheets(array $scripts)
    {
        foreach ($scripts as $script) {
            $this->appendStylesheet($script);
        }
    }

    /**
     * Append style sheet
     * @param $script
     */
    public function appendStylesheet($script)
    {
        $this->headLink()->appendStylesheet($script);
    }

    /**
     * Append script files
     * @param array $scriptFiles
     */
    public function appendScriptFiles(array $scriptFiles)
    {
        foreach ($scriptFiles as $scriptFile) {
            $this->appendScriptFile($scriptFile);
        }
    }

    /**
     * Append script file - almost is javascript file
     * @param string $scriptFile
     * @param string $type
     * @param array $attrs
     */
    public function appendScriptFile($scriptFile, $type = 'text/javascript', array $attrs = array())
    {
        $this->headScript()->appendFile($scriptFile, $type, $attrs);
    }

    /**
     * Add a ondocumentload() inline script
     *
     * @param string $tag
     * @param string $text
     */
    public function addDocLoadJS($tag, $text)
    {
        $this->addAsset($tag, $text, static::ASSET_DOCLOAD_JS);
    }

    /**
     * Generates the inline JS code for the existing tags
     *
     * @return string
     */
    public function getDocLoadJS()
    {
        $result = '';
        foreach ($this->getAssets(static::ASSET_DOCLOAD_JS) as $content) {
            $result .= $content . "\n";
        }
        return $result;
    }

    /**
     * Translation hook
     *
     * @param string $message
     * @param string $source
     * @return string
     */
    public function _($message, $source = 'default')
    {
        return $this->translate($message, $source);
    }

    /**
     * @return mixed
     */
    public function getTheme()
    {
        if (empty($this->_theme)) {
            $this->setTheme(static::THEME_DEFAULT);
        }

        return $this->_theme;
    }

    /**
     * @param mixed $theme
     */
    public function setTheme($theme)
    {
        $this->_theme = $theme;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->_layout = $layout;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        if (empty($this->_layout)) {
            $this->setLayout(static::LAYOUT_DEFAULT);
        }

        return $this->_layout;
    }

    /**
     * @param string $moduleName
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Overrides view render method to allow for template loading
     *
     * @param mixed $name
     * @return mixed
     */
    public function render($name)
    {
        if ($this->_skipTheme !== true) {
            $this->loadTheme();
        }

        $this->appendScriptFiles($this->getAssets(static::ASSET_JS));
        $this->appendStylesheets($this->getAssets(static::ASSET_CSS));
        // find the script file name using the parent private method
        $file = $this->_script($name);
        unset($name); // remove $name from local scope

        ob_start();
        $this->_viewScript = $file;
        $this->_run();

        return ob_get_clean(); // filter output
    }

    /**
     * Renders a partial block
     *
     * @param string $name
     * @return string
     * @throws Exception
     */
    public function renderBlock($name)
    {
        $block = $this->getTemplatePath() . '/blocks/' . $name;
        if (!file_exists($block)) {
            $block = $this->getTemplateBasePath() . '/blocks/' . $name;
        }

        if (!file_exists($block)) {
            throw new Exception($this, 'Block ' . $name . ' not found');
        }

        ob_start();
        include $block;

        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function getTemplateBasePath()
    {
        return APPLICATION_PATH . '/templates/' . static::THEME_DEFAULT;
    }

    /**
     * Theme-aware js resource management
     *
     * @param string $tag
     * @param string $file
     * @param boolean $isAbsolute if it is an absolute path inside public or not
     */
    public function addJS($tag, $file, $isAbsolute = false)
    {
        if (!$isAbsolute) {
            $file = '/assets/js/' . $file;
        }
        $this->addAsset($tag, $file, self::ASSET_JS);
    }

    /**
     * Disable Layout
     * @return void
     */
    public function disableLayout()
    {
        $this->_skipLayout = true;
    }

    /**
     * Enable Layout
     * @return void
     */
    public function enableLayout()
    {
        $this->_skipLayout = false;
    }

    /**
     * @return string
     */
    protected function getTemplatePath()
    {
        return APPLICATION_PATH . '/templates/' . $this->getTheme();
    }

    protected function loadTheme()
    {
        $this->_templatePath = $this->getTemplatePath();
        $this->_templateBasePath = $this->getTemplateBasePath();

        if (!file_exists($this->_templatePath)) {
            throw new Exception($this, 'Template not found in ' . $this->_templatePath);
        }

        if (!file_exists($this->_templateBasePath)) {
            throw new Exception($this, 'Template base path not found in ' . $this->_templateBasePath);
        }

        // template manifest
        $this->loadManifest();

        /* First the theme path */
        $this->addScriptPath($this->getTemplatePath());
        $moduleUri = '/modules/' . strtolower($this->moduleName) . '/';
        /* And the template path */
        $this->addScriptPath($this->getTemplatePath() . $moduleUri);

        // Init head scripts
        $this->initManifestHeadScript();
        $this->initManifestStylesheet();

    }

    protected function initManifestHeadScript()
    {
        $scriptFiles = $this->_templateOptions['layout'][$this->getLayout()]['js'];
        $this->appendScriptFiles($scriptFiles);
    }

    protected function initManifestStylesheet()
    {
        $stylesheetFiles = $this->_templateOptions['layout'][$this->getLayout()]['css'];
        $this->appendStylesheets($stylesheetFiles);
    }


    /**
     * Loads the current theme and the manifest file
     *
     * @throws Exception
     */
    protected function loadManifest()
    {
        // load manifest file
        $file = $this->getTemplatePath() . '/' . $this->_manifest;

        if (file_exists($file)) {
            $this->_templateOptions = require $file;
        } else {
            $this->_templateOptions = array();
            throw new Exception($this, 'Manifest file not found for template ' . $file);
        }
    }

    /**
     * Adds an asset to the array
     *
     * @param string $tag
     * @param string $content
     * @param string $type
     * @throws Exception
     */
    protected function addAsset($tag, $content, $type)
    {
        if (!in_array($type, $this->_validAssetTypes)) {
            throw new Exception($this, 'Asset type ' . $type . ' not supported.');
        }

        $this->_assets[$type][$tag] = $content;
    }

    /**
     * Returns an array with all the assets for a given type
     *
     * @param string $type
     * @return array
     * @throws Exception
     */
    protected function getAssets($type)
    {
        if (!in_array($type, $this->_validAssetTypes)) {
            throw new Exception($this, 'Asset type ' . $type . ' not supported.');
        }
        return $this->_assets[$type];
    }

    /**
     * Template entry point
     *
     */
    protected function _run()
    {
        $this->_execScript();
        if (true === $this->_skipTemplate) {
            $this->renderContent();
        } else {
            if (true !== $this->_skipLayout) {
                // Executes the main layout
                $page = $this->getTemplatePath() . '/' . $this->getLayout() . '.' . static::VIEW_SUFFIX;
                if (!file_exists($page)) {
                    throw new Exception($this, 'Base layout file not found - ' . $page);
                }
                include $page;
            } else {
                /* just output the page */
                $this->renderContent();
            }
        }
    }


    /**
     * Outputs the rendering result
     */
    protected function renderContent()
    {
        echo $this->_scriptResult;
    }

    /**
     * Runs the view script
     *
     */
    private function _execScript()
    {
        ob_start();
        include $this->_viewScript;
        $this->_scriptResult = ob_get_contents();
        ob_end_clean();
    }
}
