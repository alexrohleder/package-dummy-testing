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
interface CollectorInterface
{

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Collector $collector The collector to save routes.
     */
    public function __construct(Collector $collector);

    /**
     * Register all the collector extension methods.
     */
    public function register();
    
}
