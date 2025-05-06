<?php

namespace App\Traits\Controller;

trait HasDispatchesEvents
{
    protected array $events = [];

    /**
     * Hadisələri təyin edir
     *
     * @param array $events
     * @return $this
     */
    public function setEvents(array $events): self
    {
        $this->events = $events;
        return $this;
    }

    /**
     * Konkret bir əməliyyat üçün hadisəni təyin edir
     *
     * @param string $operation
     * @param string $eventClass
     * @return $this
     */
    public function setEvent(string $operation, string $eventClass): self
    {
        $this->events[$operation] = $eventClass;
        return $this;
    }

    /**
     * Hadisəni işə salır (parametr opsionaldır)
     *
     * @param string $operation
     * @param mixed|null $data
     * @return void
     */
    public function dispatchEvent(string $operation, $data = null): void
    {
        if (isset($this->events[$operation])) {
            event(new $this->events[$operation]($data));
        }
    }
}
