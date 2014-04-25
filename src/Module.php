<?php namespace Creolab\LaravelModules;

use Illuminate\Foundation\Application;

/**
 * Single module definition
 * @author Boris Strahija <bstrahija@gmail.com>
 */
class Module extends \Illuminate\Support\ServiceProvider
{

    /**
     * Name of the module
     * @var string
     */
    protected $name;

    /**
     * Path to module directory
     * @var string
     */
    protected $path;

    /**
     * Path to module definition JSON file
     * @var string
     */
    protected $definitionPath;

    /**
     * Module definition
     * @var array
     */
    protected $definition;

    /**
     * Is the module enabled
     * @var boolean
     */
    protected $enabled = true;

    /**
     * Order to register the module
     * @var integer
     */
    public $order = 0;

    /**
     * IoC
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Path for module group
     * @var string
     */
    public $group;

    /**
     * friendly name for the module
     * @var string
     */
    public $label;

    /**
     * version of the module
     * @var string
     */
    public $version;

    /**
     * array of the default permissions
     * @var array
     */
    public $default_permissions;

    /**
     * which category should the module appear in
     * @var string
     */
    public $category;

    /**
     * Initialize a module
     * @param Application $app
     */
    public function __construct($name, $path = null, $definition = null, Application $app, $group = null)
    {
        $this->name = $name;
        $this->app = $app;
        $this->path = $path;
        $this->group = $group;

        // Get definition
        if ($path and !$definition) {
            $this->definitionPath = $path . '/module.json';
        } elseif (is_array($definition)) {
            $this->definition = $definition;
        }

        // Try to get the definition
        $this->readDefinition();
    }

    /**
     * Read the module definition
     * @return array
     */
    public function readDefinition()
    {
        // Read mode from configuration
        $mode = $this->app['config']->get('modules::mode');

        if ($mode == 'auto' or ($mode == 'manifest' and !$this->app['modules']->manifest())) {
            if ($this->definitionPath) {
                $this->definition = @json_decode($this->app['files']->get($this->definitionPath), true);

                if (!$this->definition or (isset($this->definition['enabled']) and $this->definition['enabled'] === false)) {
                    $this->enabled = false;
                }
            } else {
                $this->enabled = false;
            }
        } else {
            if ((isset($this->definition['enabled']) and $this->definition['enabled'] === false)) {
                $this->enabled = false;
            }
        }

        // Add name to defintion
        if (!isset($this->definition['name'])) $this->definition['name'] = $this->name;

        // Assign order number
        if (!isset($this->definition['order'])) {
            $this->definition['order'] = $this->order = 0;
        } else {
            $this->definition['order'] = $this->order = (int)$this->definition['order'];
        }

        if (!isset($this->definition['label'])) {
            $this->definition['label'] = $this->label = $this->name;
        } else {
            $this->definition['label'] = $this->label = $this->definition['label'];
        }

        if (!isset($this->definition['version'])) {
            $this->definition['version'] = $this->version = 0;
        } else {
            $this->definition['version'] = $this->version = (int)$this->definition['version'];
        }

        if (!isset($this->definition['default_permissions'])) {
            $this->definition['default_permissions'] = $this->default_permissions = [];
        } else {
            $this->definition['default_permissions'] = $this->default_permissions = $this->definition['default_permissions'];
        }

        if (!isset($this->definition['category'])) {
            $this->definition['category'] = $this->category = [];
        } else {
            $this->definition['category'] = $this->category = $this->definition['category'];
        }

        // Add group to definition
        $this->definition['group'] = $this->group;

        return $this->definition;
    }

    /**
     * Register the module if enabled
     * @return boolean
     */
    public function register()
    {
        if ($this->enabled) {
            // Register module as a package
            $this->package('modules/' . $this->name, $this->name, $this->path());

            // Register service provider
            $this->registerProviders();

            // Get files for inclusion
            $moduleInclude = (array)array_get($this->definition, 'include');
            $globalInclude = $this->app['config']->get('modules::include');
            $include = array_merge($globalInclude, $moduleInclude);

            // Include all of them if they exist
            foreach ($include as $file) {
                $path = $this->path($file);
                if ($this->app['files']->exists($path)) require $path;
            }

            // Log it
            $this->app['modules']->logDebug('Module "' . $this->name . '" has been registered.');
        }
    }

    /**
     * Register service provider for module
     * @return void
     */
    public function registerProviders()
    {
        $providers = $this->def('provider');

        if ($providers) {
            if (is_array($providers)) {
                foreach ($providers as $provider) {
                    $this->app->register($instance = new $provider($this->app));
                }
            } else {
                $this->app->register($instance = new $providers($this->app));
            }
        }
    }

    /**
     * Run the seeder if it exists
     * @return void
     */
    public function seed()
    {
        $class = $this->def('seeder');

        if (class_exists($class)) {
            $seeder = new $class;
            $seeder->run();
        }
    }

    /**
     * Return name of module
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Module path
     * @param  string $path
     * @return string
     */
    public function path($path = null)
    {
        if ($path) return $this->path . '/' . ltrim($path, '/');
        else       return $this->path;
    }

    /**
     * Check if module is enabled
     * @return boolean
     */
    public function enabled()
    {
        return (bool)$this->enabled;
    }

    /**
     * Get definition value
     * @param  stirng $key
     * @return mixed
     */
    public function def($key = null)
    {
        if (!isset($this->definition['enabled'])) $this->definition['enabled'] = $this->enabled;

        if ($key) return isset($this->definition[$key]) ? $this->definition[$key] : null;
        else      return $this->definition;
    }

}
