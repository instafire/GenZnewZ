<?php

namespace Botble\ACL\Forms;

use Botble\ACL\Http\Requests\UpdateProfileRequest;
use Botble\ACL\Models\User;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;

class ProfileForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->model(User::class)
            ->template('core/base::forms.form-no-wrap')
            ->setFormOption('id', 'profile-form')
            ->setValidatorClass(UpdateProfileRequest::class)
            ->setMethod('PUT')
            ->columns()
            ->add(
                'first_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.info.first_name'))
                    ->placeholder(trans('core/acl::users.first_name_placeholder'))
                    ->required()
                    ->maxLength(30)
            )
            ->add(
                'last_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.info.last_name'))
                    ->placeholder(trans('core/acl::users.last_name_placeholder'))
                    ->required()
                    ->maxLength(30)
            )
            ->add(
                'username',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.username'))
                    ->placeholder(trans('core/acl::users.username_placeholder'))
                    ->required()
                    ->maxLength(30)
            )
            ->add('email', TextField::class, EmailFieldOption::make()->required()->placeholder(trans('core/acl::users.email_placeholder')))
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.phone'))
                    ->placeholder(trans('core/acl::users.phone_placeholder'))
                    ->maxLength(20)
            )
            ->setActionButtons(view('core/acl::users.profile.actions')->render());
    }
}
