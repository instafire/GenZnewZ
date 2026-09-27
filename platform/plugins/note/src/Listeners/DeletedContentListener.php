<?php

namespace Botble\Note\Listeners;

use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Facades\BaseHelper;
use Botble\Note\Note;
use Exception;

class DeletedContentListener
{
    public function handle(DeletedContentEvent $event): void
    {
        try {
            Note::deleteNote($event->data);
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
