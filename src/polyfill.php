<?php

declare(strict_types=1);

class_alias(
    \Spiral\RoadRunnerBridge\Bootloader\RoadRunnerBootloader::class,
    \Spiral\Bootloader\ServerBootloader::class
);

class_alias(
    \Spiral\RoadRunnerBridge\Bootloader\GRPCBootloader::class,
    \Spiral\Bootloader\GRPC\GRPCBootloader::class
);

class_alias(
    \Spiral\RoadRunnerBridge\Bootloader\QueueBootloader::class,
    \Spiral\Bootloader\Jobs\JobsBootloader::class
);

class_alias(
    \Spiral\RoadRunnerBridge\Bootloader\WebsocketsBootloader::class,
    \Spiral\Bootloader\Http\WebsocketsBootloader::class
);

class_alias(
    \Spiral\RoadRunnerBridge\Bootloader\BroadcastBootloader::class,
    \Spiral\Bootloader\Broadcast\BroadcastBootloader::class
);
