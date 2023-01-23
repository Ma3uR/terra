<?php declare(strict_types=1);

namespace MndCookie\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Psr\Container\ContainerInterface;
use Doctrine\DBAL\Connection;

class Frontend implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /** @var ContainerInterface  */
    private $container;

    public function __construct(SystemConfigService $systemConfigService, ContainerInterface $container)
    {
        $this->systemConfigService = $systemConfigService;
        $this->container = $container;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CmsPageLoadedEvent::class => 'onPageLoad'
        ];
    }

    public function onPageLoad(CmsPageLoadedEvent $event): void
    {
        $result = $event->getResult();
        $pageIds = $result->getIds();
        $mndConfigType = $this->systemConfigService->get('MndCookie.config.type');
        $mndConfigPagesAccess = $this->systemConfigService->get('MndCookie.config.cmsPagesAccess');
        $isAccessPage = false;

        // Check if MndFacebookPixelTracking is active
        $connection = $this->container->get(Connection::class);
        $pixelActive = $connection->executeQuery('SELECT name FROM plugin WHERE name="MndFacebookPixelTracking" AND active=1')->fetch();

        if($pixelActive !== false) {
            $this->systemConfigService->set('MndCookie.config.fpIsActive', true);
        } else {
            $this->systemConfigService->set('MndCookie.config.fpIsActive', false);
        }

        // Check if access page
        if($pageIds && $mndConfigPagesAccess) {
            foreach($pageIds as $pageId) {
                if(in_array($pageId, $mndConfigPagesAccess)) {
                    $isAccessPage = true;
                }
            }
        }

        $this->systemConfigService->set('MndCookie.config.isAccessPage', $isAccessPage);
    }
}
