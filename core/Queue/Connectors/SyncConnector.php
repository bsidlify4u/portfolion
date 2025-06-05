<?php

namespace Portfolion\Queue\Connectors;

use Portfolion\Queue\SyncQueue;

class SyncConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Portfolion\Queue\SyncQueue
     */
    public function connect(array $config)
    {
        return new SyncQueue();
    }
} 