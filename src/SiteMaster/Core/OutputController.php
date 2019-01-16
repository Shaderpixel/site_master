<?php
namespace SiteMaster\Core;

use SiteMaster\Core\Events\RegisterTheme;
use SiteMaster\Core\Plugin\PluginManager;

class OutputController extends \Savvy_Turbo
{
    public $format = 'html';
    protected $options = array();
    protected $theme = 'bootstrap';
    protected $webDir = '';

    private $scriptDeclarations = array();
    private $scripts = array();

    const SCRIPT_LOCATION_ANY = 'SCRIPT_LOCATION_ANY';
    const SCRIPT_LOCATION_HEAD = 'SCRIPT_LOCATION_HEAD';
    const SCRIPT_LOCATION_BODY = 'SCRIPT_LOCATION_BODY';

    public function __construct($options = array())
    {
        parent::__construct();
        $this->options = $options;
        $this->webDir = dirname(dirname(dirname(__DIR__))) . '/www';
    }

    /**
     * Set a specific theme for this instance
     *
     * @param string $theme Theme name, which corresponds to a directory in www/
     *
     * @throws Exception
     */
    function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * @param $theme
     * @throws RuntimeException
     * @return string - the absolute path to the theme directory
     */
    function getThemeDir($theme)
    {
        $event = PluginManager::getManager()->dispatchEvent(
            RegisterTheme::EVENT_NAME,
            new RegisterTheme($theme)
        );

        $dir = Util::getRootDir() . '/www/themes/' . $theme;

        if ($plugin = $event->getPlugin()) {
            $dir = $plugin->getRootDirectory() . '/www/themes/' . $theme;
        }

        if (!is_dir($dir)) {
            throw new RuntimeException('Invalid theme, there are no files in '.$dir);
        }

        return $dir;
    }

    public function initialize()
    {
        switch ($this->options['format']) {
            case 'partial':
            case 'html':
                // Always escape output, use $context->getRaw('var'); to get the raw data.
                $this->setEscape(function($data) {
                    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8', false);
                });
                header('Content-type:text/html;charset=UTF-8');
                $this->setTemplateFormatPaths('html');
                break;
            case 'json':
                header('Content-type:application/json');
                $this->setTemplateFormatPaths('json');
                break;
            case 'email':
                $this->setTemplateFormatPaths('email');
                break;
            default:
                throw new UnexpectedValueException('Invalid/unsupported output format', 500);
        }
    }

    public function getBaseTemplatePath($format) {
        return $this->webDir . '/templates/' . $format;
    }

    /**
     * Set the array of template paths necessary for this format
     *
     * @param string $format Format to use
     */
    public function setTemplateFormatPaths($format)
    {
        $plugin_dir = dirname(dirname(dirname(__DIR__))) . '/plugins';

        if ($format == 'partial') {
            //Trick the partial format into looking for html templates
            $format = 'html';
        }
        $this->format = $format;

        $this->setTemplatePath(
            array(
                $this->getBaseTemplatePath($format),
                $plugin_dir,
                $this->getThemeDir($this->theme) . '/' . $format
            )
        );
    }

    /**
     * Find and render with these closest parent template
     * 
     * @param null $mixed
     * @param null $template
     * @return string
     */
    function renderWithParent($mixed = null, $template = null)
    {
        /**
         * going up the template paths, find the closest parent template, and render with that.
         * If nothing was found, throw and exception
         */
        if (!$template) {
            if ($mixed instanceof \Savvy_ObjectProxy) {
                $class = $mixed->__getClass();
            } else {
                $class = get_class($mixed);
            }

            $template = $this->getClassToTemplateMapper()->map($class);
        }
        
        $fullname = $this->findParentTemplateFile($template);
        
        return $this->render($mixed, $fullname);
    }

    /**
     * Finds the parent template for a given file.
     * 
     * It finds the next closest template for the file, and returns it.
     * 
     * @param $file
     * @return string
     * @throws Savvy_TemplateException
     */
    public function findParentTemplateFile($file)
    {
        //First, find the closest template
        $closest_fullname = $this->findTemplateFile($file);
        
        // start looping through the path set
        foreach ($this->template_path as $path) {
            // get the path to the file
            $fullname = $path . $file;
            
            if ($fullname == $closest_fullname) {
                //We want the next closest template, so skip the closest one.
                continue;
            }

            if (isset($this->templateMap[$fullname])) {
                return $fullname;
            }

            if (!@is_readable($fullname)) {
                continue;
            }

            return $fullname;
        }

        // could not find the file in the set of paths
        throw new Savvy_TemplateException('Could not find a parent template for ' . $file);
    }

    /**
     * Find the template for a filename
     * 
     * @param string $file
     * @return bool|string
     */
    public function findTemplateFile($file)
    {
        //If this file is readable, return it right away.
        if (is_readable($file)) {
            return $file;
        }
        
        //take of the plugin namespace
        $tmp = str_replace("SiteMaster/Plugins/", "", $file, $count);

        //if the plugin namespace wasn't found... continue normally
        if (!$count) {
            return parent::findTemplateFile($file);
        }

        $parts = explode("/", $tmp);

        //If there is nothing after the plugin, don't continue.
        if (!$plugin = array_shift($parts)) {
            return false;
        }

        //start the starting directory (plugin/src/) for plugin classes
        $file = strtolower($plugin) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $this->format . DIRECTORY_SEPARATOR;

        //convert the namespace to a path
        $file .=  implode(DIRECTORY_SEPARATOR, $parts);

        return parent::findTemplateFile($file);
    }

    /**
     *  Load a script tag declaration to be applied to page later
     *
     * @param string $content Content of script tag
     * @param string $type Type of script tag
     * @param boolean $appendToHead whether to append tag to head
     *
     * @return void
     */
    public function loadScriptDeclaration($content, $type = '', $appendToHead = FALSE) {
        $this->scriptDeclarations[] = new ScriptDeclaration($content, $type, $appendToHead);
    }

    /**
     *  Get loaded script declaration tags
     *
     * @return void
     */
    public function getScriptDeclarations($location = self::SCRIPT_LOCATION_ANY) {
        if ($location == self::SCRIPT_LOCATION_ANY) {
            return $this->scriptDeclarations;
        }
        $appendHeadCheck = FALSE;
        if ($location == self::SCRIPT_LOCATION_HEAD) {
            $appendHeadCheck = TRUE;
        }

        $scriptDeclarations = array();
        foreach ($this->scriptDeclarations as $scriptDeclaration) {
            if ($scriptDeclaration->appendToHead() === $appendHeadCheck) {
                $scriptDeclarations[] = $scriptDeclaration;
            }
        }
        return $scriptDeclarations;
    }

    public function renderScriptDeclarations(Array $scriptDeclarations) {
        foreach($scriptDeclarations as $scriptDeclaration) {
            if ($scriptDeclaration instanceof ScriptDeclaration) {
                echo '<script type="' . $scriptDeclaration->type() . '" >' . $scriptDeclaration->content() . '</script>';
            }
        }

    }

    /**
     *  Load a script src tag to be applied to page later
     *
     * @param string $url Content of script tag
     * @param string $type Type of script tag
     * @param boolean $appendToHead whether to append tag to head
     *
     * @return void
     */
    public function loadScript($url, $type = '', $appendToHead = FALSE) {
        $this->scripts[] = new Script($url, $type, $appendToHead);
    }

    /**
     *  Get loaded script src tags
     *
     * @return void
     */
    public function getScripts($location = self::SCRIPT_LOCATION_ANY) {
        if ($location == self::SCRIPT_LOCATION_ANY) {
            return $this->scripts;
        }
        $appendHeadCheck = FALSE;
        if ($location == self::SCRIPT_LOCATION_HEAD) {
            $appendHeadCheck = TRUE;
        }

        $scripts = array();
        foreach ($this->scripts as $script) {
            if ($script->appendToHead() === $appendHeadCheck) {
                $scripts[] = $script;
            }
        }
        return $scripts;
    }

    public function renderScripts(Array $scripts) {
        foreach($scripts as $script) {
            if ($script instanceof Script) {
                echo '<script src="' . $script->url() . '" type="' . $script->type() . '" ></script>';
            }
        }
    }
}

class ScriptDeclaration {
    private $content;
    private $type;
    private $appendToHead;

    public function __construct($content, $type = 'text/javascript', $appendToHead = FALSE)
    {
        $this->content = $content;
        $this->type = !empty($type) ? $type : 'text/javascript';
        $this->appendToHead = ($appendToHead === TRUE);
    }

    public function content() {
        return $this->content;
    }

    public function type() {
        return $this->type;
    }

    public function appendToHead() {
        return $this->appendToHead;
    }
}

class Script {
    private $url;
    private $type;
    private $appendToHead;

    public function __construct($url, $type = 'text/javascript', $appendToHead = FALSE)
    {
        $this->url = $url;
        $this->type = !empty($type) ? $type : 'text/javascript';
        $this->appendToHead = ($appendToHead === TRUE);
    }

    public function url() {
        return $this->url;
    }

    public function type() {
        return $this->type;
    }

    public function appendToHead() {
        return $this->appendToHead;
    }
}