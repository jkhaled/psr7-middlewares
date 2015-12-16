<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to read the response.
 */
class ReadResponse
{
    private $continue = false;

    use Utils\FileTrait;

    public function continueIfNotFound($continue = true)
    {
        $this->continue = $continue;
    }

    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'GET') {
            return $response->withStatus(405);
        }

        $file = $this->getFilename($request);

        if (!is_file($file)) {
            if ($this->continue) {
                return $next($request, $response);
            }

            return $response->withStatus(404);
        }

        return $next($request, $response->withBody(Middleware::createStream($file)));
    }
}
