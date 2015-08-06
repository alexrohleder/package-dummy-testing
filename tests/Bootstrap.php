<?php

namespace TestNamespace {
    class TestController {
        public function test()
        {
            return true;
        }
    }
}

namespace {
    class DummyController
    {
        public function staticRouteAction()
        {
            return true;
        }

        public static function staticRouteActionStatic()
        {
            return true;
        }

        public function dinamicRouteAction($test)
        {
            return !is_null($test);
        }
    }

    class ControllerCollectorResource
    {
        public function getSomeTest()
        {
            return true;
        }

        public function getAnotherTest($id)
        {
            return true;
        }

        public function getAnAnotherTest($id, $name = '')
        {
            return true;
        }

        /**
         * @param integer $id
         * @param string $name
         */
        public function getLastTest($id, $name = '')
        {
            return true;
        }
    }

    class ResourceCollectorResource
    {
        public function index() {
            return true;
        }

        public function make() {
            return true;
        }

        public function create() {
            return true;
        }

        public function show() {
            return true;
        }

        public function edit() {
            return true;
        }

        public function update() {
            return true;
        }

        public function delete() {
            return true;
        }
    }

    include __DIR__ . '/../vendor/autoload.php';
}
