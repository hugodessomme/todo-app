<?php

namespace Core\Router;

class Route
{
    private $uri;
    private $controller;
    private $matches = [];
    private $parameters = [];

    public function __construct($uri, $controller)
    {
        $this->uri = trim($uri, '/');
        $this->controller = $controller;
    }

    /**
     * Check if route pattern matches with uri pattern
     *
     * @param string $uri
     * @return boolean
     */
    public function match($uri)
    {
        $parameters = preg_replace_callback('#:([\w]+)#', [$this, 'paramMatch'], $this->uri);
        $pattern = "#^{$parameters}$#i";
        if (! preg_match($pattern, $uri, $matches)) {
            return false;
        }

        array_shift($matches);
        $this->matches = $matches;

        return true;
    }
    
    /**
     * Constraint route parameter pattern
     *
     * @param string $parameter
     * @param string $pattern
     * @return Route
     */
    public function with($parameter, $pattern)
    {
        $this->parameters[$parameter] = str_replace('(','(?:', $pattern);

        return $this;
    }

    /**
     * Call the right controller and method
     *
     * @return object
     */
    public function call()
    {
        return $this->callMethod(
            ...explode('::', $this->controller)
        );
    }
    
    protected function paramMatch($match)
    {
        if (isset($this->parameters[$match[1]])) {
            return '(' . $this->parameters[$match[1]] . ')';
        }

        return '([^/]+)';
    }

    protected function callMethod($controller, $method)
    {
        
        $controller = "App\\Controller\\{$controller}";
        $controller = new $controller;

        if (! method_exists($controller, $method)) {
            throw new \Exception("{$controller} does not respond to the {$method} method");
        }

        return call_user_func_array([$controller, $method], $this->matches);
    }
}