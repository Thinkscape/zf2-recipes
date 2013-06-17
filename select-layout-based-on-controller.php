<?php
namespace Application;

use Zend\Console\Adapter\AdapterInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Navigation\Page\Mvc;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module implements ServiceProviderInterface, ConsoleUsageProviderInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $sm;

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $this->sm = $e->getApplication()->getServiceManager();

        // start session
        $config = $this->sm->get('config');
        session_set_cookie_params($config['adbcloud']['sessionLifetime']);
        session_start();

        $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this, 'selectLayoutBasedOnController'));
    }

    public function selectLayoutBasedOnController(MvcEvent $e)
    {
        // Get the current route match
        $match = $e->getRouteMatch();

        // Set layout to match the abbreviated controller name
        // i.e.   Application\Controller\Admin  ->   layout/admin
        if ($match instanceof RouteMatch) {
            $controllerName = $match->getParam('controller');
            $layoutName = 'layout/' . strtolower(substr($controllerName, strrpos($controllerName,'\\') + 1 ));
            $e->getViewModel()->setTemplate($layoutName);
        }
    }
}
