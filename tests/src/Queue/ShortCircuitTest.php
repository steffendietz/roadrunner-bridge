<?php

declare(strict_types=1);

namespace Spiral\Tests\Queue;

use Mockery as m;
use Spiral\Queue\HandlerInterface;
use Spiral\Queue\HandlerRegistryInterface;
use Spiral\RoadRunnerBridge\Queue\Failed\FailedJobHandlerInterface;
use Spiral\RoadRunnerBridge\Queue\Job\CallableJob;
use Spiral\RoadRunnerBridge\Queue\Job\ObjectJob;
use Spiral\RoadRunnerBridge\Queue\ShortCircuit;
use Spiral\Tests\TestCase;

final class ShortCircuitTest extends TestCase
{
    private ShortCircuit $queue;
    /** @var m\LegacyMockInterface|m\MockInterface|HandlerRegistryInterface */
    private $registry;
    /** @var m\LegacyMockInterface|m\MockInterface|FailedJobHandlerInterface */
    private $failedJobHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = new ShortCircuit(
            $this->registry = m::mock(HandlerRegistryInterface::class),
            $this->failedJobHandler = m::mock(FailedJobHandlerInterface::class)
        );
    }

    public function testJobShouldBePushed(): void
    {
        $this->registry->shouldReceive('getHandler')->with('foo')->once()->andReturn(
            $handler = m::mock(HandlerInterface::class)
        );

        $handler->shouldReceive('handle')->withSomeOfArgs('foo', ['foo' => 'bar']);

        $id = $this->queue->push('foo', ['foo' => 'bar']);

        $this->assertNotNull($id);
    }

    public function testJobObjectShouldBePushed(): void
    {
        $this->registry->shouldReceive('getHandler')->with(ObjectJob::class)->once()->andReturn(
            $handler = m::mock(HandlerInterface::class)
        );

        $object = new \stdClass();
        $object->foo = 'bar';

        $handler->shouldReceive('handle')->withSomeOfArgs(ObjectJob::class, ['object' => $object]);

        $id = $this->queue->pushObject($object);

        $this->assertNotNull($id);
    }

    public function testJobCallableShouldBePushed(): void
    {
        $this->registry->shouldReceive('getHandler')->with(CallableJob::class)->once()->andReturn(
            $handler = m::mock(HandlerInterface::class)
        );

        $callback = function () {
            return 'bar';
        };

        $handler->shouldReceive('handle')->withSomeOfArgs(CallableJob::class, ['callback' => $callback]);

        $id = $this->queue->pushCallable($callback);

        $this->assertNotNull($id);
    }

    public function testFailedJobShouldBeHandledByFailedJobHandler(): void
    {
        $e = new \Exception('Something went wrong');

        $this->registry->shouldReceive('getHandler')->andThrow($e);
        $this->failedJobHandler->shouldReceive('handle')->with('sync', 'default', 'foo', ['foo' => 'bar'], $e);

        $id = $this->queue->push('foo', ['foo' => 'bar']);
        $this->assertNotNull($id);
    }
}