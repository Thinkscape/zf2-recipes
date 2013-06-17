<?php
class Module implements AutoloaderProviderInterface,
    ConfigProviderInterface,
    ServiceProviderInterface
{
    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $sm;

    public function onBootstrap(MvcEvent $e)
    {
        $this->sm = $e->getApplication()->getServiceManager();
        $e->getApplication()->getServiceManager()->get('translator');
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(MvcEvent::EVENT_ROUTE,function(MvcEvent $event){
            $event->getViewModel()->setVariable('page',$event->getRouteMatch()->getMatchedRouteName());
        });
    }
}

/*
Inside view script:

<ul class="nav">
    <a class="<?php if($this->page == 'home') echo 'active'; ?>">Home</a>
    <a class="<?php if($this->page == 'page1') echo 'active'; ?>">Page 1</a>
</ul>
*/
