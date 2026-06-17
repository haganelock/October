<?php namespace Syncronika\Gdpr;

use Backend;
use System\Classes\PluginBase;

/**
 * gdpr Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = ['rainlab.mailchimp'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'GDPR',
            'description' => 'Save GDPR choice for newsletter inscription',
            'author'      => 'Syncronika',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Syncronika\Gdpr\Components\Signup' => 'newsletterSignup',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'syncronika.gdpr.some_permission' => [
                'tab' => 'gdpr',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'gdpr' => [
                'label'       => 'gdpr',
                'url'         => Backend::url('syncronika/gdpr/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['syncronika.gdpr.*'],
                'order'       => 500,
            ],
        ];
    }
}
