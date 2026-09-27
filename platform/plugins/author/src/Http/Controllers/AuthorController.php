<?php

namespace Botble\Author\Http\Controllers;

use Botble\Author\Forms\AuthorForm;
use Botble\Author\Http\Requests\AuthorRequest;
use Botble\Author\Models\Author;
use Botble\Author\Tables\AuthorTable;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;

class AuthorController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/author::author.name'), route('author.index'));
    }

    public function index(AuthorTable $table)
    {
        $this->pageTitle(trans('plugins/author::author.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        $this->pageTitle(trans('plugins/author::author.create'));

        return $formBuilder->create(AuthorForm::class)->renderForm();
    }

    public function store(AuthorRequest $request, BaseHttpResponse $response)
    {
        $form = AuthorForm::createFromModel(new Author())
            ->setRequest($request);

        $form->save();

        $author = $form->getModel();

        return $response
            ->setPreviousUrl(route('author.index'))
            ->setNextUrl(route('author.edit', $author->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(Author $author)
    {
        $this->pageTitle(trans('core/base::forms.edit_item', ['name' => $author->name]));

        return AuthorForm::createFromModel($author)->renderForm();
    }

    public function update(Author $author, AuthorRequest $request, BaseHttpResponse $response)
    {
        AuthorForm::createFromModel($author)
            ->setRequest($request)
            ->save();

        return $response
            ->setPreviousUrl(route('author.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(Author $author)
    {
        return DeleteResourceAction::make($author);
    }
}
