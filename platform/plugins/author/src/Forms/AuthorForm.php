<?php

namespace Botble\Author\Forms;

use Botble\Author\Http\Requests\AuthorRequest;
use Botble\Author\Models\Author;
use Botble\Base\Forms\FieldOptions\DescriptionFieldOption;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\NameFieldOption;
use Botble\Base\Forms\FieldOptions\StatusFieldOption;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;

class AuthorForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->setupModel(new Author())
            ->setValidatorClass(AuthorRequest::class)
            ->withCustomFields()
            ->add('name', TextField::class, NameFieldOption::make()->required()->toArray())
            ->add(
                'email',
                'email',
                EmailFieldOption::make()
                    ->label(trans('plugins/author::author.email'))
                    ->placeholder(trans('plugins/author::author.email'))
                    ->required()
                    ->toArray()
            )
            ->add('description', TextareaField::class, DescriptionFieldOption::make()->toArray())
            ->add('status', SelectField::class, StatusFieldOption::make()->toArray())
            ->add(
                'avatar',
                MediaImageField::class,
                MediaImageFieldOption::make()->label(trans('plugins/author::author.avatar'))->toArray()
            )
            ->setBreakFieldPoint('status');
    }
}
