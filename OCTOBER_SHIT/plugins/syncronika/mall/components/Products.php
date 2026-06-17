<?php namespace Syncronika\Mall\Components;

use OFFLINE\Mall\Components\Products as BaseProducts;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Variant;
use OFFLINE\Mall\Models\Cart as CartModel;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Exceptions\OutOfStockException;
use OFFLINE\Mall\Models\GeneralSettings;
use OFFLINE\Mall\Models\Currency;
use Redirect;
use Auth;

/**
 * The Products components displays a list of Products.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Products extends BaseProducts
{
    /**
     * Add a product to the cart.
     *
     * @return mixed
     * @throws ValidationException
     */
    public function onAddToCart()
    {
        $productId = $this->decode(post('product'));
        $variantId = $this->decode(post('variant'));
        $values    = $this->validateCustomFields(post('fields', []));

        $product = Product::published()->findOrFail($productId);
        $variant = null;
        if ($variantId) {
            $variant = Variant::published()->where('product_id', $product->id)->findOrFail($variantId);
        }

        $cart     = CartModel::byUser(Auth::getUser());
        $quantity = (int)post('quantity', $product->quantity_default ?? 1);
        if ($quantity < 1) {
            throw new ValidationException(['quantity' => trans('offline.mall::lang.common.invalid_quantity')]);
        }

        try {
            $cart->addProduct($product, $quantity, $variant, $values);
        } catch (OutOfStockException $e) {
            throw new ValidationException(['stock' => trans('offline.mall::lang.common.stock_limit_reached')]);
        }

        // If the redirect_to_cart option is set to true the user is redirected to the cart.
        if ((bool)GeneralSettings::get('redirect_to_cart', false) === true) {
            $cartPage = GeneralSettings::get('cart_page');

            return Redirect::to($this->controller->pageUrl($cartPage));
        }

        return [
            'added'    => true,
            'item'     => $this->dataLayerArray($product, $variant),
            'currency' => optional(Currency::activeCurrency())->only('symbol', 'code', 'rate', 'decimals'),
            'new_items_count' => optional($cart->products)->count() ?? 0,
            'new_items_quantity' => optional($cart->products)->sum('quantity') ?? 0,
            'quantity' => $quantity,
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
        $product = $product ?? $this->product;
        $variant = $variant ?? $this->variant;

        $item = $variant ?? $product;

        return [
            'id'       => $item->prefixedId,
            'name'     => $product->name,
            'price'    => $item->price()->decimal,
            'brand'    => optional($item->brand)->name,
            'category' => optional(optional($item->categories)->first())->name,
            'variant'  => optional($variant)->name,
        ];
    }
}
