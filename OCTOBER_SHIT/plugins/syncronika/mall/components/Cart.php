<?php namespace Syncronika\Mall\Components;

use OFFLINE\Mall\Components\Cart as BaseCart;
use OFFLINE\Mall\Models\Cart as CartModel;
use RainLab\User\Facades\Auth;
use RainLab\Translate\Classes\Translator;

/**
 * Cart Component
 */
class Cart extends BaseCart
{
    /**
     * The user removed an item from the cart.
     *
     * @return array
     */
    public function onRemoveProduct()
    {
        $id = $this->decode(input('id'));

        $cart = CartModel::byUser(Auth::getUser());

        $product = $this->getProductFromCart($cart, $id);

        if (!$product) {
            return [];
        }

        $cart->removeProduct($product);

        $cart = CartModel::byUser(Auth::getUser());

        $this->setData();

        return [
            'item' => $this->dataLayerArray($product->product, $product->variant),
            'quantity' => optional($product)->quantity ?? 0,
            'new_items_count' => optional($cart->products)->count() ?? 0,
            'new_items_quantity' => optional($cart->products)->sum('quantity') ?? 0,
        ];
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
            'id'       => $item->prefixedId,
            'name'     => $product->name,
            'price'    => $item->price()->decimal,
            'brand'    => optional($item->brand)->name,
            'category' => optional($item->categories->first())->name,
            'variant'  => optional($variant)->name,
        ];
    }

    public function onCardError()
    {
        $message = post('error_message');
        /*switch($message) {
            case 'Your card number is incomplete.':
                $resp = trans('syncronika.mall::lang.stripe.card_number_incomplete');
                break;
            case 'Your card number is invalid.':
                $resp = trans('syncronika.mall::lang.stripe.card_number_invalid');
                break;
            case 'Your card\'s expiration date is incomplete.':
                $resp = trans('syncronika.mall::lang.stripe.expiration_date_incomplete');
                break;
            case 'Your card\'s security code is incomplete.':
                $resp = trans('syncronika.mall::lang.stripe.security_code_incomplete');
                break;
            case 'Your postal code is incomplete.':
                $resp = trans('syncronika.mall::lang.stripe.postal_code_incomplete');
                break;
            default:
                $resp = $message;
                break;
        }*/

        return ['message' => $message];
    }

    public function onStripeLocalize()
    {
        $translator = Translator::instance();
        $activeLocale = $translator->getLocale();

        return [
            'language' => $activeLocale
        ];
    }
}
