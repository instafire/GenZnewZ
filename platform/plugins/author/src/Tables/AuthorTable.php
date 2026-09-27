<?php

namespace Botble\Author\Tables;

use Botble\Author\Models\Author;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\CreatedAtBulkChange;
use Botble\Table\BulkChanges\NameBulkChange;
use Botble\Table\BulkChanges\StatusBulkChange;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\ImageColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Botble\Table\HeaderActions\CreateHeaderAction;

class AuthorTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Author::class)
            ->addColumns([
                IdColumn::make(),
                ImageColumn::make('avatar')->title(trans('plugins/author::author.avatar')),
                NameColumn::make()->route('author.edit'),
                Column::make('email')->title(trans('core/base::tables.email'))->alignLeft(),
                CreatedAtColumn::make(),
                StatusColumn::make(),
            ])
            ->addHeaderAction(CreateHeaderAction::make()->route('author.create'))
            ->addActions([
                EditAction::make()->route('author.edit'),
                DeleteAction::make()->route('author.destroy'),
            ])
            ->addBulkAction(DeleteBulkAction::make()->permission('author.destroy'))
            ->addBulkChanges([
                NameBulkChange::make(),
                StatusBulkChange::make(),
                CreatedAtBulkChange::make(),
            ])
            ->queryUsing(function ($query) {
                return $query
                    ->select([
                        'id',
                        'avatar',
                        'name',
                        'email',
                        'created_at',
                        'status',
                    ]);
            });
    }
}
