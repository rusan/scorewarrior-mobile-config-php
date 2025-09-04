<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\RequestParameterService;
use App\Validators\RequestValidator;
use Phalcon\Di\Di;
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
    
    public static function createDefault(RequestValidator $requestValidator, RequestParameterService $parameterService): self
    {
        $manager = new self();

        $validationMiddleware = new ValidationMiddleware($requestValidator, $parameterService);
        
        $manager->add(new NotFoundMiddleware())
                ->add(new LoggingMiddleware())
                ->add($validationMiddleware)
                ->add(new ErrorHandlerMiddleware());
        
        return $manager;
    }
}
