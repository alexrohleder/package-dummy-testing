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
class ResourceCollector
{

    /**
     * The route master collector.
     *
     * @var \Codeburner\Router\Collector
     */
    protected $collector;
    
    /**
     * A map of all routes of resources.
     *
     * @var array
     */
    protected $map = [
        'index' => ['get', '/:name/'],
        'make' => ['get', '/:name/make'],
        'create' => ['post', '/:name'],
        'show' => ['get', '/:name/{id}'],
        'edit' => ['get', '/:name/{id}/edit'],
        'update' => ['put', '/:name/{id}'],
        'delete' => ['delete', '/:name/{id}']
    ];

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
        $name = $this->getName($controller, $options);
        $actions = array_flip($this->getActions(array_keys($this->map), $options));

        foreach ($this->map as $action => $map) {
            if (isset($actions[$action])) {
                $this->collector->match($map[0], str_replace(':name', $name, $map[1]), [$controller, $action]);
            }
        }
    }

    /**
     * Get the name of controller or an defined name, that will be used to make the URis.
     *
     * @return string
     */
    protected function getName($controller, array $options)
    {
        if (isset($options['as'])) {
            return $options['as'];
        }

        if (is_object($controller)) {
            $controller = get_class($controller);
        }

        return strstr(array_reverse(explode('\\', $controller))[0], 'Controller', true);
    }

    /**
     * Parse the options to find out what actions will be registered.
     *
     * @return string
     */
    protected function getActions($actions, $options)
    {
        if (isset($options['only'])) {
            $actions = $options['only'];
        }

        if (isset($options['except'])) {
            $actions = array_diff($actions, $options['except']);
        }

        return $actions;
    }

}
