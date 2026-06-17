<?php namespace Syncronika\Mall;

use Backend;
use System\Classes\PluginBase;
use OFFLINE\Mall\Classes\Customer\SignUpHandler;
use Syncronika\Mall\Classes\Customer\CustomSignUpHandler;
use Syncronika\Mall\Classes\Registration\BootExtentions;
use Syncronika\Mall\Classes\Registration\BootEvents;

/**
 * mall Plugin Information File
 */
class Plugin extends PluginBase
{
    use BootExtentions;
    use BootEvents;

    /**
     * @var array Plugin dependencies
     */
    public $require = ['OFFLINE.Mall'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'mall',
            'description' => 'Override di Offline Mall',
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
        $this->app->bind(SignUpHandler::class, function () {
            return new CustomSignUpHandler();
        });
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        $this->registerExtensions();
        $this->registerEvents();
        $this->registerGenericEvents();
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Syncronika\Mall\Components\Cart' => 'cart',
            'Syncronika\Mall\Components\QuickCheckout' => 'quickCheckout',
            'Syncronika\Mall\Components\Products' => 'products',
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
            'syncronika.mall.some_permission' => [
                'tab' => 'mall',
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
        return [];

        return [
            'mall' => [
                'label'       => 'Customers',
                'url'         => Backend::url('syncronika/mall/customers'),
                'icon'        => 'icon-user-circle-o',
                'permissions' => ['syncronika.mall.*'],
                'order'       => 500,
            ],
        ];
    }
}
