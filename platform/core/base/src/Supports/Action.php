<?php

namespace Botble\Base\Supports;

class Action extends ActionHookEvent
{
    public function fire(string $action, array $args): void
    {
        if (! $this->getListeners()) {
            return;
        }

        foreach ($this->getListeners() as $hook => $listeners) {
            if ($hook !== $action) {
                continue;
            }

            krsort($listeners);
            foreach ($listeners as $arguments) {
                $parameters = array_slice($args, 0, $arguments['arguments']);
                call_user_func_array($this->getFunction($arguments['callback']), $parameters);
            }
        }
    }
}
