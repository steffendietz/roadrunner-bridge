<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Broadcast\Broadcast;
use Spiral\RoadRunner\Broadcast\BroadcastInterface;

final class BroadcastBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        RoadRunnerBootloader::class
    ];

    public function boot(Container $container): void
    {
        $this->registerBroadcast($container);
    }

    private function registerBroadcast(Container $container)
    {
        $container->bind(BroadcastInterface::class, Broadcast::class);
        $container->bindSingleton(
            Broadcast::class,
            function (RPCInterface $rpc): Broadcast {
                return new Broadcast($rpc);
            }
        );
    }
}
