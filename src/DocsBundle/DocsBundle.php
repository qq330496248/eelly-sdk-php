<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\DocsBundle;

use Eelly\Events\Listener\ApiLoggerListener;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use Phalcon\Events\Event;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\View;

class DocsBundle extends Injectable
{
    public function register(): void
    {
        $router = $this->router;
        foreach ($this->config->modules as $moduleName => $value) {
            $value = $value->toArray();
            $namespace = str_replace('Module', 'Logic', $value['className']);
            $router->addGet('/', [
                'namespace'      => __NAMESPACE__,
                'controller'     => 'apiDoc',
                'action'         => 'home',
            ]);
            $router->addGet('/'.$moduleName, [
                'namespace'      => __NAMESPACE__,
                'controller'     => 'apiDoc',
                'action'         => 'module',
                'params'         => $moduleName,
            ]);
            $router->addGet('/'.$moduleName.'/:controller', [
                'namespace'      => __NAMESPACE__,
                'controller'     => 'apiDoc',
                'action'         => 'service',
                'params'         => $moduleName,
                'class'          => 1,
            ]);
            $router->addGet('/'.$moduleName.'/:controller/:action', [
                'namespace'      => __NAMESPACE__,
                'controller'     => 'apiDoc',
                'action'         => 'api',
                'params'         => $moduleName,
                'class'          => 1,
                'method'         => 2,
            ])->setName($moduleName);
        }
        $this->getDI()->setShared('view', function () {
            $view = new View();
            $view->setViewsDir(__DIR__.'/Resources/views/');
            $view->setLayoutsDir(__DIR__.'/Resources/views/');
            $view->setLayout('api_doc/layout');
            $view->setRenderLevel(
                View::LEVEL_LAYOUT
            );
            $view->registerEngines([
                '.phtml'  => View\Engine\Php::class,
            ]);

            return $view;
        });
        $this->getEventsManager()->attach('router:matchedRoute', function (Event $event, Router $router, Router\Route $route): void {
            if (__NAMESPACE__ == $route->getPaths()['namespace']) {
                $this->getDI()->getShared('application')->useImplicitView(true);
            }
        });
        /* @var ApiLoggerListener $apiLoggerListener */
        $apiLoggerListener = $this->di->getShared(ApiLoggerListener::class);
        $apiLoggerListener->pushWhiteName(ApiDocLogic::class.'::home');
        $apiLoggerListener->pushWhiteName(ApiDocLogic::class.'::home');
        $apiLoggerListener->pushWhiteName(ApiDocLogic::class.'::module');
        $apiLoggerListener->pushWhiteName(ApiDocLogic::class.'::service');
        $apiLoggerListener->pushWhiteName(ApiDocLogic::class.'::api');
    }
}
