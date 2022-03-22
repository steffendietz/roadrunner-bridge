<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Config;

use Spiral\Core\InjectableConfig;

final class WebsocketsConfig extends InjectableConfig
{
    public const CONFIG = 'websockets';

    private array $patterns = [];

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        foreach ($this->getAuthorizeTopics() as $topic => $callback) {
            $this->patterns[$this->compilePattern($topic)] = $callback;
        }
    }

    public function getPath(): ?string
    {
        return $this->config['path'] ?? null;
    }

    public function getServerCallback(): ?callable
    {
        return $this->config['authorizeServer'] ?? null;
    }

    /**
     * @return array<string, callable>
     */
    public function getAuthorizeTopics(): array
    {
        return $this->config['path'] ?? [];
    }

    public function findTopicCallback(string $topic, array &$matches): ?callable
    {
        foreach ($this->patterns as $pattern => $callback) {
            if (preg_match($pattern, $topic, $matches)) {
                return $callback;
            }
        }

        return null;
    }

    private function compilePattern(string $topic): string
    {
        $replaces = [];
        if (preg_match_all('/{(\w+):?(.*?)?}/', $topic, $matches)) {
            $variables = array_combine($matches[1], $matches[2]);
            foreach ($variables as $key => $_) {
                $replaces['{' . $key . '}'] = '(?P<' . $key . '>[^\/\.]+)';
            }
        }

        return '/^' . strtr($topic, $replaces + ['/' => '\\/', '[' => '(?:', ']' => ')?', '.' => '\.']) . '$/iu';
    }
}
