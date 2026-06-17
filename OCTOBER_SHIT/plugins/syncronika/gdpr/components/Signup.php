<?php namespace Syncronika\Gdpr\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use DrewM\MailChimp\MailChimp;
use RainLab\MailChimp\Models\Settings;
use Syncronika\Gdpr\Models\Privacy;
use Validator;
use Lang;

/**
 * Signup Component
 */
class Signup extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Signup Form',
            'description' => 'Signup form to MailChimp with GDPR approvation'
        ];
    }

    public function defineProperties()
    {
        return [
            'privacyPolicyPage' => [
                'title' => 'Privacy policy page',
                'type' => 'dropdown',
                'default' => 'privacy'
            ],
            'list' => [
                'title'       => 'MailChimp List ID',
                'description' => 'In MailChimp account, select List > Tools and look for a List ID.',
                'type'        => 'string'
            ],
            'confirm' => [
                'title'       => 'Double Opt-in',
                'description' => 'Enable confirmation to MailChimp list subscription.',
                'type'        => 'checkbox'
            ],
        ];
    }

    public function getPrivacyPolicyPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onSignup()
    {
        $settings = Settings::instance();
        if (!$settings->api_key) {
            throw new ApplicationException('MailChimp API key is not configured.');
        }

        /*
         * Validate input
         */
        $data = post();

        $rules = [
            'email' => 'required|email|min:2|max:64',
            'gdpr' => 'accepted',
        ];

        $messages = [
            'gdpr.accepted' => Lang::get('syncronika.gdpr::lang.gdpr-accepted')
        ];

        $validation = Validator::make($data, $rules, $messages);
        if ($validation->fails()) {
            $this->page['error'] = $validation->messages()->first();
            return [];
        }

        /*
         * Saves the gdpr approvation
         */
        $this->savePrivacy();

        /*
         * Sign up to Mailchimp via the API
         */
        $this->signupViaMailchimpApi($settings);
        return [];
    }

    private function savePrivacy()
    {
        $email = post('email');
        $gdpr = post('gdpr');

        $privacy = Privacy::where('email', $email)->first();
        if (!$privacy) {
            $privacy = new Privacy();
        }
        $privacy->email = $email;
        $privacy->approved = 1;
        $privacy->save();
    }

    private function signupViaMailchimpApi($settings)
    {
        $MailChimp = new MailChimp($settings->api_key);

        $this->page['error'] = null;

        $email = post('email');
        $subscriptionData = [
            'email_address' => $email,
            'status' => $this->property('confirm') ? 'pending' : 'subscribed',
        ];

        if (isset($data['merge']) && is_array($data['merge']) && count($data['merge'])) {
            $subscriptionData['merge_fields'] = $data['merge'];
        }

        $result = $MailChimp->post("lists/".$this->property('list')."/members", $subscriptionData);

        if (!$MailChimp->success()) {
            $lastError = $MailChimp->getLastError();
            if (strpos($lastError, 'Use PUT to insert or update list members.') > 0) {
                $lastError = $email . Lang::get('syncronika.gdpr::lang.already-subscribed');
            }
            $this->page['error'] = $lastError;
        }
    }

    public function onRun()
    {
        $this->page['privacy_url'] = $this->properties['privacyPolicyPage'];
        return [];
    }
}
