<?php

namespace DesignMyNight\Laravel\Horizon;

use DesignMyNight\Laravel\Horizon\Jobs\RabbitMQJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\JobId;
use Laravel\Horizon\JobPayload;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue as BaseQueue;

class RabbitMQQueue extends BaseQueue
{
    /**
     * The job that last pushed to queue via the "push" method.
     *
     * @var object|string
     */
    protected $lastPushed;

    /**
     * Get the number of queue jobs that are ready to process.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function readyNow($queue = null)
    {
        return $this->size($queue);
    }

    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->lastPushed = $job;

        return parent::push($job, $data, $queue);
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = (new JobPayload($payload))->prepare($this->lastPushed)->value;

        return tap(parent::pushRaw($payload, $queue, $options), function () use ($queue, $payload) {
            $this->event($this->getQueue($queue), new JobPushed($payload));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = (new JobPayload($this->createPayload($job, $data)))->prepare($job)->value;

        return tap(parent::pushRaw($payload, $queue, ['delay' => $this->secondsUntil($delay)]), function () use ($payload, $queue) {
            $this->event($this->getQueue($queue), new JobPushed($payload));
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function popRaw($queueName = null)
    {
        try {
            list($queue) = $this->declareEverything($queueName);

            $consumer = $this->getContext()->createConsumer($queue);

            if ($message = $consumer->receiveNoWait()) {
                return new RabbitMQJob($this->container, $this, $consumer, $message);
            }
        } catch (\Exception $exception) {
            $this->reportConnectionError('pop', $exception);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
        return tap($this->popRaw($queue), function ($result) use ($queue) {
            if ($result) {
                $this->event($this->getQueue($queue), new JobReserved($result->getRawBody()));
            }
        });
    }

    /**
     * Fire the given event if a dispatcher is bound.
     *
     * @param  string  $queue
     * @param  mixed  $event
     * @return void
     */
    protected function event($queue, $event)
    {
        if ($this->container && $this->container->bound(Dispatcher::class)) {
            $queue = Str::replaceFirst('queues:', '', $queue);

            $this->container->make(Dispatcher::class)->dispatch(
                $event->connection($this->getConnectionName())->queue($queue)
            );
        }
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @return string
     */
    protected function createPayloadArray($job, $data = '')
    {
        return array_merge(parent::createPayloadArray($job, $data), [
            'id' => $this->getRandomId(),
            'attempts' => 0,
        ]);
    }

    /**
     * Fire the job deleted event.
     *
     * @param  string  $queue
     * @param  \DesignMyNight\Laravel\Horizon\Jobs\RabbitMQJob  $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->event($this->getQueue($queue), new JobDeleted($job, $job->getRawBody()));
    }

    /**
     * Get the queue name.
     *
     * @param string|null $queue
     * @return string
     */
    protected function getQueue($queue = null): string
    {
        return $queue ?: $this->queueName;
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId()
    {
        return JobId::generate();
    }
}
