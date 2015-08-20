<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Collectors;

use Codeburner\Router\Collector;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
class ControllerCollector implements CollectorInterface
{

    /**
     * The route master collector.
     *
     * @var \Codeburner\Router\Collector
     */
    protected $collector;

    /**
     * The PHPDoc types and they constraint.
     *
     * @var array
     */
    protected $types = [
        'int' => '\d+',
        'integer' => '\d+',
        'string' => '\w+',
        'float' => '[-+]?(\d*[.])?\d+',
        'bool' => '^(1|0|true|false|yes|no)$',
        'boolean' => '^(1|0|true|false|yes|no)$',
        'true' => '^(1|true|yes)$',
        'false' => '^(0|false|no)$'
    ];

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Collector $collector The collector to save routes.
     */
    public function __construct(\Codeburner\Router\Collector $collector)
    {
        $this->collector = $collector;
    }

    /**
     * Register all the collector extension methods.
     *
     * @return void
     */
    public function register()
    {
        $methods = ['controller'];
        
        $this->collector->accept($this, $methods);
    }

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a uri. The uri will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix. 
     * e.g. getSomePage will generate a route to: GET some/page
     *
     * @param string|object $controller The controller name or representation.
     * @param bool          $prefix     Dict if the controller name should prefix the path.
     */
    public function controller($controller, $prefix = true)
    {
        if (!$methods = get_class_methods($controller)) {
            throw new \Exception('The controller class coul\'d not be inspected.');
        }

        $methods = $this->getControllerMethods($methods);
        $prefix = $this->getPathPrefix($prefix);

        foreach ($methods as $httpmethod => $classmethods) {
            foreach ($classmethods as $classmethod) {
                $uri = preg_replace_callback('~(^|[a-z])([A-Z])~', [$this, 'getControllerAction'], $classmethod);

                $method  = $httpmethod . $classmethod;
                $dinamic = $this->getMethodDinamicPattern($controller, $method);

                $this->collector->match($httpmethod, $prefix . $uri . $dinamic, $controller . '#' . $method);
            }
        }
    }

    /**
     * Give a prefix for the controller routes paths.
     *
     * @param bool $prefix Must prefix?
     * @return string
     */
    protected function getPathPrefix($prefix)
    {
        $path = '/';

        if ($prefix === true) {
            $path .= $this->getControllerName($controller);
        }

        return $path;
    }

    /**
     * Transform camelcased strings into URIs.
     *
     * @return string
     */
    public function getControllerAction($matches)
    {
        return strtolower(strlen($matches[1]) ? $matches[1] . '/' . $matches[2] : $matches[2]);
    }

    /**
     * Get the controller name without the suffix Controller.
     *
     * @return string
     */
    public function getControllerName($controller)
    {
        if (is_object($controller)) {
            $controller = get_class($controller);
        }

        return strtolower(strstr($controller, 'Controller', true));
    }

    /**
     * Maps the controller methods to HTTP methods.
     *
     * @param array $methods All the controller public methods
     * @return array An array keyed by HTTP methods and their controller methods.
     */
    protected function getControllerMethods($methods)
    {
        $mapmethods = [];
        $httpmethods = ['get', 'post', 'put', 'patch', 'delete'];

        foreach ($methods as $classmethod) {
            foreach ($httpmethods as $httpmethod) {
                if (($pos = strpos($classmethod, $httpmethod)) === 0) {
                    $mapmethods[$httpmethod][] = substr($classmethod, strlen($httpmethod));
                }
            }
        }

        return $mapmethods;
    }

    /**
     * Inspect a method seeking for parameters and make a dinamic pattern.
     *
     * @param string|object $controller The controller representation.
     * @param string        $method     The method to be inspected name.
     *
     * @return string The resulting URi
     */
    protected function getMethodDinamicPattern($controller, $method)
    {
        $method = new \ReflectionMethod($controller, $method);
        $uri    = '';

        if ($parameters = $method->getParameters())
        {
            $count = count($parameters);
            $types = $this->getParamsConstraint($method);

            for ($i = 0; $i < $count; ++$i) {
                $parameter = $parameters[$i];

                if ($parameter->isOptional()) {
                    $uri .= '[';
                }

                $uri .= $this->getUriConstraint($parameter, $types);
            }

            for ($i = $i - 1; $i >= 0; --$i) {
                if ($parameters[$i]->isOptional()) {
                    $uri .= ']';
                }
            }
        }

        return $uri;
    }

    /**
     * Return a URi segment based on parameters constraints.
     *
     * @param \ReflectionParameter $parameter The parameter base to build the constraint.
     * @param array $types All the parsed constraints.
     * @return string
     */
    protected function getUriConstraint($parameter, $types)
    {
        $name = $parameter->name;
        $uri  = '/{' . $name;

        if (isset($types[$name])) {
            return  $uri . ':' . $types[$name] . '}';
        } else {
            return $uri . '}';
        }
    }

    /**
     * Get all parameters with they constraint.
     *
     * @param \ReflectionMethod $method The method to be inspected name.
     * @return array All the parameters with they constraint
     */
    protected function getParamsConstraint($method)
    {
        $params = [];
        preg_match_all('~\@param\s('.implode('|', array_keys($this->types)).')\s\$([a-zA-Z]+)\s(Match \((.+)\))?~', 
            $method->getDocComment(), $types, PREG_SET_ORDER);

        foreach ((array) $types as $type) {
            $params[$type[2]] = $this->getParamConstraint($type);
        }

        return $params;
    }

    /**
     * Convert PHPDoc type to a constraint.
     *
     * @param string $type The PHPDoc type.
     * @return string The Constraint string.
     */
    protected function getParamConstraint($type)
    {
        if (isset($type[4])) {
            return $type[4];
        }

        return $this->types[$type[1]];
    }

}