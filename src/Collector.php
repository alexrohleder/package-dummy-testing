<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Collectors\CollectorInterface;
use Codeburner\Router\Collectors\ControllerCollector;
use Codeburner\Router\Collectors\ResourceCollector;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
class Collector extends Collection
{

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
     * The prefix for all the subsequent route patterns.
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * The namespace for all the subsequent route class action.
     *
     * @var string
     */
    protected $namespace = '';

    /**
     * Register a route into GET method.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function get($pattern, $action, $strategy = 'default')
    {
        $this->set('get', $this->getPrefixed($pattern), $this->getNamespaced($action), $strategy);
    }

    /**
     * Register a route into POST method.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function post($pattern, $action, $strategy = 'default')
    {
        $this->set('post', $this->getPrefixed($pattern), $this->getNamespaced($action), $strategy);
    }

    /**
     * Register a route into PUT method.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function put($pattern, $action, $strategy = 'default')
    {
        $this->set('put', $this->getPrefixed($pattern), $this->getNamespaced($action), $strategy);
    }

    /**
     * Register a route into PATCH method.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function patch($pattern, $action, $strategy = 'default')
    {
        $this->set('patch', $this->getPrefixed($pattern), $this->getNamespaced($action), $strategy);
    }

    /**
     * Register a route into DELETE method.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function delete($pattern, $action, $strategy = 'default')
    {
        $this->set('delete', $this->getPrefixed($pattern), $this->getNamespaced($action), $strategy);
    }

    /**
     * Register a route into all HTTP methods.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function any($pattern, $action, $strategy = 'default')
    {
        $this->match($this->methods, $pattern, $action, $strategy);
    }

    /**
     * Register a route into all HTTP methods except by $method.
     *
     * @param string                $method   The method that must be excluded.
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function except($method, $pattern, $action, $strategy = 'default')
    {
        $this->match(array_diff($this->methods, (array) $method), $pattern, $action, $strategy);
    }

    /**
     * Register a route into given HTTP method(s).
     *
     * @param string|array          $methods  The method that must be matched.
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The strategy that will be used to execute the $action.
     */
    public function match($methods, $pattern, $action, $strategy = 'default')
    {
        foreach ((array) $methods as $method) {
            $this->set($method, $this->getPrefixed($pattern), $this->getNamespaced($action), $strategy);
        }
    }

    /**
     * Integrate a new collector method to this collector.
     *
     * @param \Codeburner\Collectors\CollectorInterface $collector The collector instance.
     * @param string|array                              $methods   All the collectors methods that must be registered.
     */
    public function accept(CollectorInterface $collector, $methods)
    {
        foreach ((array) $methods as $method) {
            $this->collectors[$method] = $collector;
        }
    }

    /**
     * Group a given route definitions with shared attributes between then.
     *
     * @param array   $attributes All the attributes that will be shared between the given routes.
     * @param closure $definition A callable that receive this colletor to add new routes.
     */
    public function group($attributes, $definition)
    {
        $this->setCollectorAttributes($attributes);
        
        call_user_func($definition, $this);

        $this->removeCollectorAttributes(array_keys($attributes));
    }

    /**
     * Set the user given attributes to the collector.
     *
     * @param array $attributes
     */
    protected function setCollectorAttributes($attributes)
    {
        foreach ($attributes as $attribute => $value) {
            if (!isset($this->$attribute)) {
                throw new \Exception("There is no \"$attribute\" attribute for grouped routes.");
            }

            $method = 'set' . ucfirst(strtolower($attribute));
            $this->$method($value);
        }
    }

    /**
     * Restaure the collector initial attributes.
     *
     * @param array $attributes
     */
    protected function removeCollectorAttributes($attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $method = 'set' . ucfirst(strtolower($attribute));
            $this->$method('');
        }
    }

    /**
     * Seek for a more specific collector method.
     *
     * @param string $method The collector method requested.
     * @param array  $params The parameters passed to the method.
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (isset($this->collectors[$method])) {
            return call_user_func_array([$this->collectors[$method], $method], $params);
        }

        throw new \Exception("Collector method \"$method\" not found, maybe no collector was registered for this method.");
    }

    /**
     * Give a prefix for all the subsequent routes.
     *
     * @param string $prefix The subsequent routes prefix.
     */
    public function setPrefix($prefix)
    {
        if (empty($prefix)) $this->prefix = '';
        else $this->prefix .= '/' . trim($prefix, '/');
    }

    /**
     * Get the current routes prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Prefix the route pattern.
     *
     * @param string $pattern The route pattern.
     * @return string
     */
    protected function getPrefixed($pattern)
    {
        return $this->prefix . '/' . ltrim($pattern, '/');
    }

    /**
     * Set a namespace for each string action of the subsequent routes.
     *
     * @param string $namespace The subsequent routes string action namespace.
     * @return string
     */
    public function setNamespace($namespace)
    {
        if (empty($namespace)) $this->namespace = '';
        else $this->namespace .= '\\' . trim($namespace, '\\');
    }

    /**
     * Get the current namespace for string action.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Namespace the given string action.
     *
     * @return string
     */
    protected function getNamespaced($action)
    {
        if (is_string($action)) {
            return $this->namespace . '\\' . ltrim($action, '\\');
        }

        return $action;
    }

}
