<?php namespace Hagane\Cartbutton;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Cart Button',
            'description' => 'Floating/inline cart button with item count (replacement for the missing Pixel.Shop cartButton).',
            'author'      => 'Hagane',
            'icon'        => 'icon-shopping-cart',
        ];
    }

    public function registerComponents()
    {
        return [\Hagane\Cartbutton\Components\CartButton::class => 'cartButton'];
    }
}
