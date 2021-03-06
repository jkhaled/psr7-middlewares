<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to handle php errors and exceptions.
 */
class ErrorHandler
{
    const KEY = 'EXCEPTION';

    use Utils\CallableTrait;
    use Utils\AttributeTrait;

    /**
     * @var callable|string The handler used
     */
    private $handler;

    /**
     * @var bool Whether or not catch exceptions
     */
    private $catchExceptions = false;

    /**
     * Returns the exception throwed.
     *
     * @param ServerRequestInterface $request
     *
     * @return \Exception|null
     */
    public static function getException(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY);
    }

    /**
     * Constructor.
     *
     * @param callable|string|null $handler
     */
    public function __construct($handler = null)
    {
        $this->handler = $handler ?: self::CLASS.'::defaultHandler';
    }

    /**
     * Configure the catchExceptions.
     *
     * @param bool $catch
     *
     * @return self
     */
    public function catchExceptions($catch = true)
    {
        $this->catchExceptions = (boolean) $catch;

        return $this;
    }

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
        ob_start();
        $level = ob_get_level();

        try {
            $response = $next($request, $response);
        } catch (\Exception $exception) {
            if (!$this->catchExceptions) {
                throw $exception;
            }

            $request = self::setAttribute($request, self::KEY, $exception);
            $response = $response->withStatus(500);
        } finally {
            Utils\Helpers::getOutput($level);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            return $this->executeCallable($this->handler, $request, $response);
        }

        return $response;
    }

    public static function defaultHandler(ServerRequestInterface $request, ResponseInterface $response)
    {
        $exception = self::getException($request);

        $message = $exception ? $exception->getMessage() : $response->getReasonPhrase();
        $statusCode = $response->getStatusCode();

        return <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Error {$statusCode}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Error {$statusCode}</h1>
    <p>{$message}</p>
</body>
</html>
EOT;
    }
}
