<?php

namespace Portfolion\Contracts\Queue;

interface ShouldQueue
{
    /**
     * Handle the queued job.
     *
     * @return void
     */
    public function handle();
} 