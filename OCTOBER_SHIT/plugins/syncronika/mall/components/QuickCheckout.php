<?php namespace Syncronika\Mall\Components;

use OFFLINE\Mall\Components\QuickCheckout as QuickCheckoutBase;
use OFFLINE\Mall\Models\Cart;
use RainLab\User\Facades\Auth as FrontendAuth;

class QuickCheckout extends QuickCheckoutBase
{
    /**
     * The user removed an item from the cart.
     *
     * @return array
     */
    public function onRemoveProduct()
    {
        $id = $this->decode(input('id'));

        $cart = Cart::byUser(FrontendAuth::getUser());

        $product = $this->getProductFromCart($cart, $id);

        $cart->removeProduct($product);

        $cart = Cart::byUser(FrontendAuth::getUser());
        
        $this->setData();

        return $this->updateForm([
            'item' => $this->dataLayerArray($product->product, $product->variant),
            'quantity' => $product->quantity,
            'new_items_count' => optional($cart->products)->count() ?? 0,
            'new_items_quantity' => optional($cart->products)->sum('quantity') ?? 0,
        ]);
    }

    /**
     * Return the dataLayer representation of an item.
     *
     * @param null $product
     * @param null $variant
     *
     * @return array
     */
    private function dataLayerArray($product = null, $variant = null)
    {
        $item = $variant ?? $product;

        return [
            'id' => $item->prefixedId,
            'name' => $product->name,
            'price' => $item->price()->decimal,
            'brand' => optional($item->brand)->name,
            'category' => optional($item->categories->first())->name,
            'variant' => optional($variant)->name,
        ];
    }
}
