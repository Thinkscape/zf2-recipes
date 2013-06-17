<?php

/**
 * @category Application
 */
class Module implements ConfigProviderInterface, AutoloaderProviderInterface, ViewHelperProviderInterface,
 ServiceProviderInterface
{
    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceManager;
 
    /**
     * @param \Zend\Mvc\MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {
        Locale::setDefault('sv_SE');
 
        $e->getApplication()
          ->getEventManager()
          ->attach(MvcEvent::EVENT_ROUTE, array($this, 'selectLayoutBasedOnRoute'));
 
        $resDir = __DIR__ . '/../../data/language/sv_SE_Zend_Validate.php';
 
        $translator = new \Zend\I18n\Translator\Translator(
            array(
                'locale'=>'sv_SE'
            )
        );
 
        $translator->addTranslationFile('phpArray', $resDir);
        \Zend\Validator\AbstractValidator::setDefaultTranslator($translator);
    }
 
    /**
     * @param \Zend\Mvc\MvcEvent $e
     *
     * @return mixed
     */
    public function selectLayoutBasedOnRoute(MvcEvent $e)
    {
        // Get the current route match
        $match = $e->getRouteMatch();
 
        // Get the current user
        $user = $e->getApplication()
                  ->getServiceManager()
                  ->get('user.service.authentication')
                  ->getIdentity();
 
        if ($match instanceof RouteMatch) {
 
            $exp = explode('/', $match->getMatchedRouteName());
 
            if ($exp[0] == 'admin') {
 
                if ($user->getRole() != 'administrator') {
 
                    return $e->getResponse()
                             ->setStatusCode(401)
                             ->getHeaders()
                             ->addHeaderLine('location', '/');
                }
 
                $e->getViewModel()
                  ->setTemplate('layout/admin');
 
                // Get the view manager
                $vm = $e->getApplication()
                    ->getServiceManager()
                    ->get('ViewTemplateMapResolver');
 
                $vm->merge(
                    array(
                        'message'    => __DIR__ . '/view/misc/admin/message.phtml',
                        'pagination' => __DIR__ . '/view/misc/admin/pagination.phtml'
                    )
                );
            }
        }
    }
 
    /**
     * Expected to return \Zend\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array|\Zend\ServiceManager\Config
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'acl' => function () {
 
                    $acl = new Acl();
                    $acl->addRole('guest')
                        ->addRole('user', 'guest')
                        ->addRole('administrator', 'user');
 
                    $acl->addResource('guest-only')
                        ->allow('guest', 'guest-only')
                        ->deny('user', 'guest-only');
 
                    $acl->addResource('user')
                        ->allow('user', 'user');
 
                    $acl->addResource('administrator')
                        ->allow('administrator', 'administrator');
 
                    return $acl;
                },
 
                'logger' => function ($sm) {
 
                    $writer = new Stream('data/logs/application.log', 'a');
 
                    $logger = new Logger();
                    $logger->addWriter($writer);
 
                    return $logger;
                },
 
                'memcache' => function ($sm) {
 
                    $cache = new \Memcache();
                    $cache->addServer('localhost');
 
                    return $cache;
                }
            )
        );
    }
 
    /**
     * Expected to return \Zend\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array|\Zend\ServiceManager\Config
     */
    public function getViewHelperConfig()
    {
        return array(
            'initializers' => array(
                function ($instance, $viewServiceManager) {
 
                    if ($instance instanceof ServiceLocatorAwareInterface) {
 
                        $instance->setServiceLocator($viewServiceManager->getServiceLocator());
                    }
                }
            ),
 
            'factories' => array(
                'navigation' => function ($sm) {
 
                    $navigation = $sm->get('Zend\View\Helper\Navigation');
                    $navigation->setAcl($sm->getServiceLocator()->get('acl'));
                    $navigation->setRole($sm->getServiceLocator()->get('user.service.authentication')->getIdentity()->getRole());
 
                    return $navigation;
                }
            )
        );
    }
 
    /**
     * Return an array for passing to Zend\Loader\AutoloaderFactory.
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
 
    /**
     * Returns configuration to merge with application configuration
     *
     * @return array|\Traversable
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
