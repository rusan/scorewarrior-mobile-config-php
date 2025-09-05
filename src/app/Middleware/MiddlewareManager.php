<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Contracts\LoggerInterface;
use App\Validators\RequestValidator;
use Phalcon\Mvc\Micro;

class MiddlewareManager
{
    /** @var AbstractMiddleware[] */
    private array $middleware = [];
    
    public function add(AbstractMiddleware $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    public function register(Micro $app): void
    {
        foreach (array_reverse($this->middleware) as $middleware) {
            $app->before(function() use ($middleware, $app) {
                return $middleware->call($app);
            });
        }
    }
    
    public static function createDefault(RequestValidator $requestValidator, LoggerInterface $logger): self
    {
        $manager = new self();

        $manager->add(new NotFoundMiddleware())
                ->add(new LoggingMiddleware($logger))
                ->add(new ValidationMiddleware($requestValidator))
                ->add(new ErrorHandlerMiddleware());
        
        return $manager;
    }
}
