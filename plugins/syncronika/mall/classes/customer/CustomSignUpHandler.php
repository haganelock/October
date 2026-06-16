<?php

namespace Syncronika\Mall\Classes\Customer;

use DB;
use Event;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Address;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\Customer;
use OFFLINE\Mall\Models\GeneralSettings;
use OFFLINE\Mall\Models\User;
use OFFLINE\Mall\Models\Wishlist;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\UserGroup;
use OFFLINE\Mall\Classes\Customer\SignUpHandler;

class CustomSignUpHandler implements SignUpHandler
{
    protected $asGuest;

    public function handle(array $data, bool $asGuest = false): ?User
    {
        $this->asGuest = $asGuest;

        return $this->signUp($data);
    }

    /**
     * @throws ValidationException
     */
    protected function signUp(array $data): ?User
    {
        if ($this->asGuest) {
            $data['password'] = $data['password_repeat'] = str_random(30);
        }

        $this->validate($data);

        $requiresConfirmation = ($data['requires_confirmation'] ?? false);

        Event::fire('mall.customer.beforeSignup', [$this, $data]);

        $user = DB::transaction(function () use ($data, $requiresConfirmation) {

            $user = $this->createUser($data, $requiresConfirmation);

            $customer = new Customer();
            $customer->firstname = $data['firstname'];
            $customer->lastname = $data['lastname'];
            $customer->user_id = $user->id;
            $customer->is_guest = $this->asGuest;
            $customer->save();

            $addressData = $this->transformAddressKeys($data, 'billing');
            $fullname = $data['firstname'].' '.$data['lastname'];

            $billing = new Address();
            $billing->fill($addressData);
            $billing->name = $addressData['address_name'] ?? $fullname;
            $billing->customer_id = $customer->id;
            $billing->save();
            $customer->default_billing_address_id = $billing->id;

            if (!empty($data['use_different_shipping'])) {
                $addressData = $this->transformAddressKeys($data, 'shipping');

                $shipping = new Address();
                $shipping->fill($addressData);
                $shipping->name = $addressData['address_name'] ?? $fullname;
                $shipping->customer_id = $customer->id;
                $shipping->save();
                $customer->default_shipping_address_id = $shipping->id;
            } else {
                $customer->default_shipping_address_id = $billing->id;
            }

            $customer->save();

            Cart::transferSessionCartToCustomer($user->customer);
            Wishlist::transferToCustomer($user->customer);

            return $user;
        });

        // To prevent multiple guest accounts with the same email address we edit
        // the email of all existing guest accounts registered to the same email.
        $this->renameExistingGuestAccounts($data, $user);

        Event::fire('mall.customer.afterSignup', [$this, $user]);

        if ($requiresConfirmation === true) {
            return $user;
        }

        $credentials = [
            'login' => array_get($data, 'email'),
            'password' => array_get($data, 'password'),
        ];

        return Auth::authenticate($credentials, true);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $data)
    {
        $rules = self::rules();

        if ($this->asGuest) {
            unset($rules['password'], $rules['password_repeat']);
        }

        if (array_key_exists('flag_company', $data) && ($data['flag_company'] == 1)) {
            $rules['billing_company'] = 'required';
        }

        $messages = self::messages();

        $validation = Validator::make($data, $rules, $messages);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }
    }

    protected function createUser($data, $requiresConfirmation): User
    {
        $data = [
            'name' => $data['firstname'],
            'surname' => $data['lastname'],
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password_repeat'],
            'fiscal_code' => $data['fiscal_code'],
            'company_name' => array_key_exists('billing_company', $data) ? $data['billing_company'] : null,
            'phone_number' => $data['phone_number'],
        ];

        $user = Auth::register($data, !$requiresConfirmation);
        if ($this->asGuest && $user && $group = UserGroup::getGuestGroup()) {
            $user->groups()->sync($group);
        } else {
            $user->groups()->sync([]);
        }

        return $user;
    }

    protected function transformAddressKeys(array $data, string $type): array
    {
        return collect($data)->mapWithKeys(function ($value, $key) use ($type) {
            if (starts_with($key, $type)) {
                $newKey = str_replace($type.'_', '', $key);

                return [$newKey => $value];
            }

            return [];
        })->toArray();
    }

    protected function renameExistingGuestAccounts(array $data, $user)
    {
        // Add a "mall-guest_2021-05-31_075100" suffix to the already registered email.
        $parts = explode('@', $data['email']);
        $suffix = 'mall-guest_'.date('Y-m-d_His');

        $newEmail = sprintf('%s+%s@%s', $parts[0], $suffix, $parts[1]);

        User::where('id', '<>', $user->id)
            ->where('email', $data['email'])
            ->whereHas('customer', function ($q) {
                $q->where('is_guest', 1);
            })
            ->update(['email' => $newEmail, 'username' => $newEmail]);
    }

    public static function rules($forSignup = true): array
    {
        $minPasswordLength = \RainLab\User\Models\User::getMinPasswordLength();
        $rules = [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => ['required', 'email', ($forSignup ? 'non_existing_user' : null)],
            'billing_lines' => 'required',
            'billing_zip' => 'required',
            'billing_city' => 'required',
            'billing_country_id' => 'required|exists:rainlab_location_countries,id',
            //'billing_state_id' => 'required|exists:rainlab_location_states,id',
            'shipping_lines' => 'required_if:use_different_shipping,1',
            'shipping_zip' => 'required_if:use_different_shipping,1',
            'shipping_city' => 'required_if:use_different_shipping,1',
            //'shipping_state_id' => 'required_if:use_different_shipping,1|exists:rainlab_location_states,id',
            'shipping_country_id' => 'required_if:use_different_shipping,1|exists:rainlab_location_countries,id',
            'password' => sprintf('required|min:%d|max:255', $minPasswordLength),
            'password_repeat' => 'required|same:password',
            'terms_accepted' => 'required',
            'fiscal_code' => 'required',
            'phone_number' => 'required',
        ];

        if ((bool) GeneralSettings::get('use_state', true) !== true) {
            unset($rules['billing_state_id'], $rules['shipping_state_id']);
        }

        Event::fire('mall.customer.extendSignupRules', [&$rules, $forSignup]);

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'email.required' => trans('syncronika.mall::lang.components.signup.errors.email.required'),
            'email.email' => trans('syncronika.mall::lang.components.signup.errors.email.email'),
            'email.unique' => trans('syncronika.mall::lang.components.signup.errors.email.unique'),
            'email.non_existing_user' => trans('syncronika.mall::lang.components.signup.errors.email.non_existing_user'),

            'firstname.required' => trans('syncronika.mall::lang.components.signup.errors.firstname.required'),
            'lastname.required' => trans('syncronika.mall::lang.components.signup.errors.lastname.required'),
            'billing_lines.required' => trans('syncronika.mall::lang.components.signup.errors.lines.required'),
            'billing_zip.required' => trans('syncronika.mall::lang.components.signup.errors.zip.required'),
            'billing_city.required' => trans('syncronika.mall::lang.components.signup.errors.city.required'),
            'billing_country_id.required' => trans('syncronika.mall::lang.components.signup.errors.country_id.required'),
            'billing_country_id.exists' => trans('syncronika.mall::lang.components.signup.errors.country_id.exists'),
            'billing_state_id.required' => trans('syncronika.mall::lang.components.signup.errors.state_id.required'),
            'billing_state_id.exists' => trans('syncronika.mall::lang.components.signup.errors.state_id.exists'),
            'shipping_lines.required_if' => trans('syncronika.mall::lang.components.signup.errors.lines.required'),
            'shipping_zip.required_if' => trans('syncronika.mall::lang.components.signup.errors.zip.required'),
            'shipping_city.required_if' => trans('syncronika.mall::lang.components.signup.errors.city.required'),
            'shipping_country_id.required_if' => trans('syncronika.mall::lang.components.signup.errors.country_id.required'),
            'shipping_country_id.exists' => trans('syncronika.mall::lang.components.signup.errors.country_id.exists'),

            'password.required' => trans('syncronika.mall::lang.components.signup.errors.password.required'),
            'password.min' => trans('syncronika.mall::lang.components.signup.errors.password.min'),
            'password.max' => trans('syncronika.mall::lang.components.signup.errors.password.max'),

            'password_repeat.required' => trans('syncronika.mall::lang.components.signup.errors.password_repeat.required'),
            'password_repeat.same' => trans('syncronika.mall::lang.components.signup.errors.password_repeat.same'),

            'terms_accepted.required' => trans('syncronika.mall::lang.components.signup.errors.terms_accepted.required'),
            'fiscal_code.required' => trans('syncronika.mall::lang.components.signup.errors.fiscal_code.required'),
            'billing_company.required' => trans('syncronika.mall::lang.components.signup.errors.company_name.required'),
            'phone_number.required' => trans('syncronika.mall::lang.components.signup.errors.phone_number.required'),
        ];
    }
}
