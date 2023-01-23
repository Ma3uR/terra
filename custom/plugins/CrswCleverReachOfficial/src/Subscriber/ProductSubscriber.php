<?php

namespace Crsw\CleverReachOfficial\Subscriber;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class ProductSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber
 */
class ProductSubscriber implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * ProductSubscriber constructor.
     *
     * @param SessionInterface $session
     * @param RequestStack $requestStack
     * @param Initializer $initializer
     */
    public function __construct(SessionInterface $session, RequestStack $requestStack, Initializer $initializer)
    {
        $initializer->registerServices();
        $this->session = $session;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductLoaded',
        ];
    }

    /**
     * @param $event
     */
    public function onProductLoaded(EntityLoadedEvent $event): void
    {
        /** @var ConfigService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setContext($event->getContext());
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $crMailing = $request->get('crmailing');
            if (!empty($crMailing)) {
                $ids = json_encode($event->getIds());
                Logger::logInfo("Product loaded with crmailing parameter. Crmailing: {$crMailing}, product ids: {$ids}", 'Integration');
                $this->session->set('crMailing', $crMailing);
            }
        }
    }
}