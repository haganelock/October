<?php namespace Hagane\Cartbutton\Components;

use Cms\Classes\ComponentBase;

class CartButton extends ComponentBase
{
    public $count = 0;
    public $cartUrl = '#';

    public function componentDetails()
    {
        return ['name' => 'Cart Button', 'description' => 'Cart button with item count'];
    }

    public function defineProperties()
    {
        return [
            'cartPage' => ['title' => 'Cart page', 'type' => 'string', 'default' => 'cart'],
            'colorBG'  => ['title' => 'Background', 'type' => 'string', 'default' => '#C43730'],
            'color'    => ['title' => 'Text color', 'type' => 'string', 'default' => '#FFFFFF'],
            'position' => ['title' => 'Position', 'type' => 'string', 'default' => 'top-right'],
        ];
    }

    public function onRun()
    {
        $page = $this->property('cartPage', 'cart');
        $this->cartUrl = $this->controller->pageUrl($page) ?: ('/' . ltrim($page, '/'));
        $this->count = $this->getCount();
    }

    protected function getCount()
    {
        try {
            $user = class_exists(\RainLab\User\Facades\Auth::class) ? \RainLab\User\Facades\Auth::getUser() : null;
            $cart = \OFFLINE\Mall\Models\Cart::byUser($user);
            return $cart ? (int) $cart->products->sum('quantity') : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
