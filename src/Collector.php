<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
class Collector
{

    /**
     * The route collection.
     *
     * @var \Codeburner\Router\Collection
     */
    protected $collection;

    /**
     * All the custom route collectors.
     *
     * @var \Codeburner\Router\Collectors\CollectorInterface
     */
    protected $collectors = [];

    /**
     * Supported HTTP methods.
     *
     * @var array
     */
    protected $methods = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Collection $collection The collection to save routes.
     */
    public function __construct(Collection $collection = null)
    {
        $this->collection = $collection ?: new Collection;
    }

    /**
     * Register a route into GET method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function get($pattern, $action)
    {
        $this->collection->set('get', $pattern, $action);
    }

    /**
     * Register a route into POST method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function post($pattern, $action)
    {
        $this->collection->set('post', $pattern, $action);
    }

    /**
     * Register a route into PUT method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function put($pattern, $action)
    {
        $this->collection->set('put', $pattern, $action);
    }

    /**
     * Register a route into PATCH method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function patch($pattern, $action)
    {
        $this->collection->set('patch', $pattern, $action);
    }

    /**
     * Register a route into DELETE method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function delete($pattern, $action)
    {
        $this->collection->set('delete', $pattern, $action);
    }

    /**
     * Register a route into all HTTP methods.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function any($pattern, $action)
    {
        $this->match($this->methods, $pattern, $action);
    }

    /**
     * Register a route into all HTTP methods except by $method.
     *
     * @param string|array   $method  The method(s) that must be excluded.
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function except($method, $pattern, $action)
    {
        $this->match(array_diff($this->methods, (array) $method), $pattern, $action);
    }

    /**
     * Register a route into given HTTP method(s).
     *
     * @param string|array   $methods The method(s) that must be included.
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function match($methods, $pattern, $action)
    {
        foreach ((array) $methods as $method) {
            $this->collection->set($method, $pattern, $action);
        }
    }

    /**
     * Register a route into given HTTP method.
     *
     * @param string|array   $method The method that must be matched.
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function map($method, $pattern, $action)
    {
        $this->collection->set($method, $pattern, $action);
    }

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a uri. The uri will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix. 
     * e.g. getSomePage will generate a route to: GET some/page
     *
     * @param string|object $controller The controller(s) name(s) or representation(s).
     */
    public function controller()
    {
        $collector = $this->getControllerCollector();
        
        foreach (func_get_args() as $controller) {
            call_user_func([$collector, 'controller'], $controller);
        }
    }

    /**
     * Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. 
     * Instead of declaring separate routes for your index, show, new, edit, create, update and destroy actions, 
     * a resourceful route declares them in a single line of code
     *
     * @param string|object $controller The controller name or representation.
     * @param array         $options Some options like, 'as' to name the route pattern, 'only' to
     *                               explicty say that only this routes will be registered, and 
     *                               except that register all the routes except the indicates.
     */
    public function resource($controller, array $options = array())
    {
        call_user_func([$this->getResourceCollector(), 'resource'], $controller, $options);
    }

    /**
     * Get a instance of Controller Collector.
     *
     * @return \Codeburner\Router\Collectors\ControllerCollector
     */
    protected function getControllerCollector()
    {
        if (!isset($this->collectors['controller'])) {
            return $this->collectors['controller'] = new Collectors\ControllerCollector($this);
        }

        return $this->collectors['controller'];
    }

    /**
     * Get a isntance of Resource Collector.
     *
     * @return \Codeburner\Router\Collectors\ResourceCollector
     */
    protected function getResourceCollector()
    {
        if (!isset($this->collectors['resource'])) {
            return $this->collectors['resource'] = new Collectors\ResourceCollector($this);
        }

        return $this->collectors['resource'];
    }

    /**
     * Get the collection of routes.
     *
     * @return \Codeburner\Router\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * All the supported HTTP methods.
     *
     * @return array
     */
    public function getHttpMethods()
    {
        return $this->methods;
    }

    /**
     * get all the route especific route collectors.
     *
     * @return array
     */
    public function getCollectors()
    {
        return $this->collectors;
    }

    /**
     * Set a new especific collector method into this route collector.
     *
     * @param string|array $methods   All the collector methods that will be acessivel through this class.
     * @param object       $collector The collector instance.
     */
    public function setCollector($methods, $collector)
    {
        foreach ((array) $methods as $collector) {
            $this->collectors[$name] = $collector;
        }
    }

}