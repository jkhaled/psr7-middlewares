<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Transformers;
use RuntimeException;

class Minify
{
    use Utils\ResolverTrait;
    use Utils\AttributeTrait;
    use Utils\StreamTrait;

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!self::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('Minify middleware needs FormatNegotiator executed before');
        }

        $response = $next($request, $response);

        $resolver = $this->resolver ?: new Transformers\Minifier();
        $transformer = $resolver->resolve(FormatNegotiator::getFormat($request));

        if ($transformer) {
            return $response->withBody($transformer($response->getBody(), self::createStream()));
        }

        return $response;
    }
}
