<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Broadcast\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Broadcast\Config\WebsocketsConfig;
use Spiral\Core\Exception\LogicException;
use Spiral\Core\ResolverInterface;
use Spiral\Core\ScopeInterface;
use Spiral\Http\Exception\ClientException;

final class WebsocketsMiddleware implements MiddlewareInterface
{
    private WebsocketsConfig $config;
    private ScopeInterface $scope;
    private ResolverInterface $resolver;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        WebsocketsConfig $config,
        ScopeInterface $scope,
        ResolverInterface $resolver,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->config = $config;
        $this->scope = $scope;
        $this->resolver = $resolver;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @throws ClientException
     * @throws \Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== $this->config->getPath()) {
            return $handler->handle($request);
        }

        // server authorization
        if ($request->getAttribute('ws:joinServer', null) !== null) {
            if (!$this->authorizeServer($request)) {
                return $this->responseFactory->createResponse(403);
            }

            return $this->responseFactory->createResponse(200);
        }

        // topic authorization
        $topics = $request->getAttribute('ws:joinTopics');
        if (\is_string($topics)) {
            foreach (\explode(',', $topics) as $topic) {
                if (!$this->authorizeTopic($request, $topic)) {
                    return $this->responseFactory->createResponse(403);
                }
            }

            return $this->responseFactory->createResponse(200);
        }

        return $this->responseFactory->createResponse(403);
    }

    /**
     * @throws \Throwable
     */
    private function authorizeServer(ServerRequestInterface $request): bool
    {
        $callback = $this->config->getServerCallback();

        if ($callback === null) {
            return true;
        }

        return $this->invoke($request, $callback, []);
    }

    /**
     * @throws \Throwable
     */
    private function authorizeTopic(ServerRequestInterface $request, string $topic): bool
    {
        $parameters = [];
        $callback = $this->config->findTopicCallback($topic, $parameters);
        if ($callback === null) {
            return false;
        }

        return $this->invoke($request, $callback, $parameters + ['topic' => $topic]);
    }

    /**
     * @throws \Throwable
     */
    private function invoke(ServerRequestInterface $request, callable $callback, array $parameters = []): bool
    {
        switch (true) {
            case $callback instanceof \Closure:
            case \is_string($callback):
                $reflection = new \ReflectionFunction($callback);
                break;

            case \is_array($callback) && \is_object($callback[0]):
                $reflection = (new \ReflectionObject($callback[0]))->getMethod($callback[1]);
                break;

            case \is_array($callback):
                $reflection = (new \ReflectionClass($callback[0]))->getMethod($callback[1]);
                break;

            default:
                throw new LogicException('Unable to invoke callable function');
        }

        $scoped = function () use ($reflection, $parameters, $callback) {
            return \call_user_func_array($callback, $this->resolver->resolveArguments($reflection, $parameters));
        };

        return $this->scope->runScope([ServerRequestInterface::class => $request], $scoped);
    }
}
