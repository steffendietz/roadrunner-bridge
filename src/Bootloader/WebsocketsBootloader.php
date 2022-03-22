<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Bootloader\Http\HttpBootloader;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Append;
use Spiral\Config\Patch\Set;
use Spiral\Core\Container\SingletonInterface;
use Spiral\RoadRunnerBridge\Broadcast\Middleware\WebsocketsMiddleware;
use Spiral\RoadRunnerBridge\Config\WebsocketsConfig;

final class WebsocketsBootloader extends Bootloader implements SingletonInterface
{
    protected const DEPENDENCIES = [
        HttpBootloader::class,
        BroadcastBootloader::class,
    ];

    private ConfiguratorInterface $config;

    public function __construct(ConfiguratorInterface $config)
    {
        $this->config = $config;
    }

    public function boot(HttpBootloader $http, EnvironmentInterface $env): void
    {
        $this->config->setDefaults(WebsocketsConfig::CONFIG, [
            'path' => $env->get('RR_BROADCAST_PATH', null),
            'authorizeServer' => null,
            'authorizeTopics' => [],
        ]);

        if ($this->config->getConfig(WebsocketsConfig::CONFIG)['path'] !== null) {
            $http->addMiddleware(WebsocketsMiddleware::class);
        }
    }

    public function authorizeServer(?callable $callback): void
    {
        $this->config->modify(WebsocketsConfig::CONFIG, new Set('authorizeServer', $callback));
    }

    public function authorizeTopic(string $topic, callable $callback): void
    {
        $this->config->modify(WebsocketsConfig::CONFIG, new Append('authorizeTopics', $topic, $callback));
    }
}
