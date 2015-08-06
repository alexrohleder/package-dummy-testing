<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Collectors;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
class ControllerCollector
{

    /**
     * The route master collector.
     *
     * @var \Codeburner\Router\Collector
     */
    protected $collector;

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Collector $collector The collector to save routes.
     */
    public function __construct($collector)
    {
        $this->collector = $collector;
    }

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a uri. The uri will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix. 
     * e.g. getSomePage will generate a route to: GET some/page
     *
     * @param string|object $controller The controller name or representation.
     */
    public function controller($controller)
    {
        if (!$methods = get_class_methods($controller)) {
            throw new \Exception('The controller class coul\'d not be inspected.');
        }

        $methods = $this->getControllerMethods($methods);

        foreach ($methods as $httpmethod => $classmethods) {
            foreach ($classmethods as $classmethod) {
                $uri = preg_replace_callback('~(^|[a-z])([A-Z])~', function ($matches) {
                    return strtolower(strlen($matches[1]) ? $matches[1].'/'.$matches[2] : $matches[2]);
                }, $classmethod);

                $method  = $httpmethod . $classmethod;
                $dinamic = $this->getMethodDinamicPattern($controller, $method);

                $this->collector->map($httpmethod, "/$uri$dinamic", "$controller#$method");   
            }
        }
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
        $httpmethods = $this->collector->getHttpMethods();

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
                if ($parameters[$i]->isOptional()) {
                    $uri .= '[';
                }

                $name = $parameters[$i]->name;
                $uri .= '/{' . $name;

                if (isset($types[$name])) {
                    $uri .= ':' . $types[$name] . '}';
                } else {
                    $uri .= '}';
                }
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
     * Get all parameters with they constraint.
     *
     * @param string $method The method to be inspected name.
     * @return array All the parameters with they constraint
     */
    protected function getParamsConstraint($method)
    {
        preg_match_all('~\@param (int|integer|string) \$([a-zA-Z]+)?~', $method->getDocComment(), $types, PREG_SET_ORDER);
        $params = [];

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
        switch ($type[1]) {
            case 'int': case 'integer':
                return '\d+';
            case 'string':
                return '\w+';
        }
    }

}