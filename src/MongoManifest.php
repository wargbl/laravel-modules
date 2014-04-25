<?php namespace Creolab\LaravelModules;

use Creolab\LaravelModules\Documents\Module;
use Illuminate\Foundation\Application;

/**
 * MongoManifest - store our modules in a mongo collection
 * @author James Cogley <james.cogley@gmail.com>
 */
class MongoManifest extends Manifest
{

    /**
     * Initialize the manifest
     * @param Application $app [description]
     */
    public function __construct(Application $app)
    {

    }

    /**
     * Save the manifest data
     * @param $modules
     * @return void
     */
    public function save($modules)
    {
        foreach ($modules as $module) {
            $m = Module::first(['name' => $module->name()]);

            if(!$m){
                $m = new Module();
            }

            $m->name = $module->name();
            $m->enabled = $module->enabled();
            $m->order = $module->order;
            $m->group = $module->group;
            $m->label = $module->label;
            $m->version = $module->version;
            $m->default_permissions = $module->default_permissions;
            $m->category = $module->category;

            $m->save();
        }

        return $this->data;
    }

    /**
     * Get the manifest data as an array
     * @param null $module
     * @return array
     */
    public function toArray($module = null)
    {
        if ($module) {
            return Module::first(['name' => $module->name]);
        }  else  {
            return Module::all();
        }
    }

    /**
     * Delete the manifest file
     * @return void
     */
    public function delete()
    {
        $this->data = null;
        foreach(Module::all() as $module){
            $module->delete();
        }
    }

}
