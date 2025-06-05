<?php

namespace Portfolion\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Portfolion\Queue\QueueInterface
     */
    public function connect(array $config);
} 