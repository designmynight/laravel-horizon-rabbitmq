<?php

namespace DesignMyNight\Laravel\Horizon\Jobs;

use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseJob;

class RabbitMQJob extends BaseJob
{
    /**
     * {@inheritdoc}
     */
    public function delete(): void
    {
        parent::delete();

        $this->connection->deleteReserved($this->queue, $this);
    }
}
