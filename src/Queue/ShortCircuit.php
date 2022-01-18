<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Queue;

use Ramsey\Uuid\Uuid;
use Spiral\Queue\HandlerRegistryInterface;
use Spiral\Queue\OptionsInterface;
use Spiral\Queue\QueueInterface;
use Spiral\RoadRunnerBridge\Queue\Failed\FailedJobHandlerInterface;

/**
 * Runs all the jobs in the same process.
 */
final class ShortCircuit implements QueueInterface
{
    use QueueTrait;

    private HandlerRegistryInterface $registry;
    private FailedJobHandlerInterface $failedJobHandler;

    public function __construct(
        HandlerRegistryInterface $registry,
        FailedJobHandlerInterface $failedJobHandler
    ) {
        $this->registry = $registry;
        $this->failedJobHandler = $failedJobHandler;
    }

    /** @inheritdoc */
    public function push(string $jobType, array $payload = [], OptionsInterface $options = null): string
    {
        if ($options !== null && $options->getDelay()) {
            sleep($options->getDelay());
        }

        $id = (string) Uuid::uuid4();

        try {
            $this->registry->getHandler($jobType)->handle($jobType, $id, $payload);
        } catch (\Throwable $e) {
            $this->failedJobHandler->handle(
                'sync', 'default', $jobType, $payload, $e
            );
        }

        return $id;
    }
}