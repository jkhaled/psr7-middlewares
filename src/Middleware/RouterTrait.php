<?php
namespace Psr7Middlewares\Middleware;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Base functions used by router middlewares
 */
trait RouterTrait
{   
    protected $extraArguments = [];

    /**
     * Execute the target
     * 
     * @param mixed $target
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * 
     * @return ResponseInterface
     */
    protected function executeTarget($target, ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            ob_start();

            $arguments = array_merge([$request, $response], $this->extraArguments);
            $target = static::getCallable($target, $arguments);
            $return = call_user_func_array($target, $arguments);

            if ($return instanceof ResponseInterface) {
                $response = $return;
                $return = '';
            }

            $response->getBody()->write(ob_get_contents().$return);

            return $response;
        } catch (\Exception $exception) {
            throw $exception;
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    /**
     * Resolves the target of the route and returns a callable
     *
     * @param mixed $target
     * @param array $construct_args
     *
     * @throws RuntimeException If the target is not callable
     * 
     * @return callable
     */
    protected static function getCallable($target, array $construct_args)
    {
        if (is_string($target)) {
            //is a static function
            if (function_exists($target)) {
                return $target;
            }

            //is a class (Classname::method)
            if (strpos($target, '::') === false) {
                $class = $target;
                $method = '__invoke';
            } else {
                list($class, $method) = explode('::', $target, 2);
            }

            if (!class_exists($class)) {
                throw new RuntimeException("The class {$class} does not exists");
            }

            $class = new \ReflectionClass($class);
            $instance = $class->hasMethod('__construct') ? $class->newInstanceArgs($construct_args) : $class->newInstance();
            $target = [$instance, $method];
        }

        if (is_callable($target)) {
            return $target;
        }

        throw new RuntimeException('The route target is not callable');
    }
}