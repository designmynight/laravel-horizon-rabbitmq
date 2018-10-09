<?php

namespace DesignMyNight\Laravel\Horizon;

use DesignMyNight\Laravel\Horizon\Connectors\RabbitMQConnector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * All of the Horizon event / listener mappings.
     *
     * @var array
     */
    protected $events = [
        \Illuminate\Queue\Events\JobFailed::class => [
            Listeners\MarshalFailedEvent::class,
        ],
    ];

    /**
     * Register the Horizon job events.
     *
     * @return void
     */
    protected function registerEvents()
    {
        $events = $this->app->make(Dispatcher::class);

        foreach ($this->events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * Register the custom queue connectors for Horizon.
     *
     * @return void
     */
    protected function registerQueueConnectors()
    {
        $this->app->booted(function () {
            $queue = $this->app['queue'];

            $queue->addConnector('rabbitmq', function () {
                return new RabbitMQConnector($this->app['events']);
            });
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerEvents();
        $this->registerQueueConnectors();
    }
}
