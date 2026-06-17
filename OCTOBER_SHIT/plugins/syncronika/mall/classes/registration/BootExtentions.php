<?php

namespace Syncronika\Mall\Classes\Registration;

use OFFLINE\Mall\Controllers\Products as ProductsController;
use OFFLINE\Mall\Models\Product;
use Event;
use RainLab\User\Controllers\Users as UserController;
use RainLab\User\Models\User;

trait BootExtentions
{
    protected function registerExtensions()
    {
        $this->extendOfflineProduct();
        $this->extendRainlabUser();
    }

    protected function extendRainlabUser()
    {
        User::extend(function ($model) {
            $model->addFillable('company_name');
            $model->addFillable('fiscal_code');
            $model->addFillable('phone_number');
            $model->rules['fiscal_code'] = 'required';
            $model->rules['phone_number'] = 'required';
        });

        Event::listen('backend.form.extendFields', function($widget) {
            if (!$widget->getController() instanceof UserController) {
                return;
            }

            if (!$widget->model instanceof User) {
                return;
            }

            if ($widget->isNested) {
                return;
            }

            $widget->addFields([
                'company_name' => [
                    'label' => 'syncronika.mall::lang.user.fields.company_name',
                    'type' => 'text',
                    'span' => 'auto',
                ],
                'fiscal_code' => [
                    'label' => 'syncronika.mall::lang.user.fields.fiscal_code',
                    'type' => 'text',
                    'span' => 'auto',
                ],
                'phone_number' => [
                    'label' => 'syncronika.mall::lang.user.fields.phone_number',
                    'type' => 'text',
                    'span' => 'auto',
                ],
            ]);
        });
    }

    protected function extendOfflineProduct()
    {
        Product::extend(function ($model) {
            $model->addJsonable('details');
            $model->addJsonable('specs');
        });

        Event::listen('backend.form.extendFields', function($widget) {
            if (!$widget->getController() instanceof ProductsController) {
                return;
            }

            if (!$widget->model instanceof Product) {
                return;
            }

            if ($widget->isNested) {
                return;
            }

            $widget->addTabFields([
                'price_us' => [
                    'label' => 'Dollar price',
                    'span' => 'auto',
                    'tab' => 'syncronika.mall::lang.product.general',
                ],
                'price_us_old' => [
                    'label' => 'Old dollar price',
                    'span' => 'auto',
                    'tab' => 'syncronika.mall::lang.product.general',
                ],
                'details' => [
                    'label' => 'syncronika.mall::lang.common.showcase',
                    'span' => 'full',
                    'type' => 'repeater',
                    'tab' => 'syncronika.mall::lang.common.showcase',
                    'form' => [
                        'fields' => [
                            'title' => [
                                'label' => 'syncronika.mall::lang.common.showcase_title',
                                'span' => 'auto',
                                'type' => 'text',
                            ],
                            'image' => [
                                'label' => 'syncronika.mall::lang.common.showcase_image',
                                'mode' => 'image',
                                'thumbOptions' => [
                                    'mode' => 'crop',
                                    'extension' => 'auto',
                                ],
                                'span' => 'auto',
                                'type' => 'mediafinder',
                            ],
                            'text' => [
                                'label' => 'syncronika.mall::lang.common.showcase_detail',
                                'size' => 'small',
                                'span' => 'full',
                                'type' => 'richeditor',
                            ],
                        ],
                    ],
                ],
                'specs' => [
                    'label' => 'syncronika.mall::lang.common.specs',
                    'span' => 'full',
                    'type' => 'repeater',
                    'tab' => 'syncronika.mall::lang.common.specs',
                    'form' => [
                        'fields' => [
                            'color' => [
                                'label' => 'syncronika.mall::lang.common.specs_color',
                                'span' => 'auto',
                                'type' => 'text',
                            ],
                            'size' => [
                                'label' => 'syncronika.mall::lang.common.specs_size',
                                'span' => 'auto',
                                'type' => 'text',
                            ],
                            'weight' => [
                                'label' => 'syncronika.mall::lang.common.specs_weight',
                                'span' => 'auto',
                                'type' => 'text',
                            ],
                            'warranty' => [
                                'label' => 'syncronika.mall::lang.common.specs_warranty',
                                'span' => 'auto',
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
                'cover' => [
                    'label' => 'Cover',
                    'mode' => 'image',
                    'thumbOptions' => [
                        'mode' => 'crop',
                        'extension' => 'auto',
                    ],
                    'span' => 'auto',
                    'type' => 'mediafinder',
                    'tab' => 'offline.mall::lang.common.attachments',
                ],
            ]);
        });
    }
}
