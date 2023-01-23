<?php declare(strict_types=1);

/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Service;

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Store\Services\StoreClient;
use Shopware\Core\Framework\Store\Services\StoreService;
use Shopware\Core\Framework\Store\Struct\StoreLicenseStruct;
use Shopware\Core\Framework\Store\Exception\StoreTokenMissingException;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\Exception\InvalidContextSourceException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class LicenceService implements LicenceServiceInterface {
    const LICENCE_LIST_CACHE_KEY = 'BilobaCache_v1_LicenceList';

    /**
     * @var TagAwareAdapterInterface
     */
    private $cache;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var StoreClient
     */
    private $storeClient;

    /**
     * @var EntityRepositoryInterface
     */
    private $userRepository;

    /**
     * @var StoreService
     */
    private $storeService;

    /**
     * @var EntityRepositoryInterface
     */
    private $pluginRepo;

    /**
     * @var SystemConfigService
     */
    private $configService;

    public function __construct(
        ContainerInterface $container,
        StoreClient $storeClient,
        StoreService $storeService,
        SystemConfigService $configService,
        EntityRepositoryInterface $userRepository,
        EntityRepositoryInterface $pluginRepo,
        TagAwareAdapterInterface $cache
    ) {
        $this->container = $container;
        $this->storeClient = $storeClient;
        $this->storeService = $storeService;
        $this->configService = $configService;
        $this->userRepository = $userRepository;
        $this->pluginRepo = $pluginRepo;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
	public function hasValidLicence(string $pluginName, Context $context): bool
	{
        $hasValidLicence = true;

        // get the licence check from the cache
        $cacheItem = $this->cache->getItem(self::LICENCE_LIST_CACHE_KEY);
        if($cacheItem->isHit()) {
            $hasValidLicence = $cacheItem->get();
        } else {
            try {
                // get the store auth token
                $storeToken = $this->getUserStoreToken($context);

                if($storeToken) {
                    // get the licences 
                    $client = $this->storeService->createClient();

                    $headers = $client->getConfig('headers');

                    if ($storeToken) {
                        $headers['X-Shopware-Platform-Token'] = $storeToken;
                    }

                    $shopSecret = $this->configService->get('core.store.shopSecret');
                    if ($shopSecret) {
                        $headers['X-Shopware-Shop-Secret'] = $shopSecret;
                    }

                    $response = $client->get(
                        '/swplatform/pluginlicenses',
                        [
                            'query' => $this->storeService->getDefaultQueryParameters('en-GB'),
                            'headers' => $headers,
                        ]
                    );
            
                    $data = json_decode($response->getBody()->getContents(), true);
            
                    $pluginCollection = $this->pluginRepo->search(new Criteria(), $context)->getEntities();
                    
                    $installedPlugins = [];
                    foreach ($pluginCollection as $plugin) {
                        $installedPlugins[$plugin->getName()] = $plugin->getVersion();
                    }
            
                    foreach ($data['data'] as $license) {
                        $licenseStruct = new StoreLicenseStruct();
                        $licenseStruct->assign($license);
            
                        if($licenseStruct->getTechnicalPluginName() == $pluginName) {
                            if (isset($license['type']['name']) && $license['type']['name'] == 'test') {
                                $hasValidLicence = false;
                            }
                            if (isset($license['subscription']['expirationDate']) && $license['subscription']['expirationDate'] && new Date() > Date::parseFromString($license['subscription']['expirationDate'])) {
                                $hasValidLicence = false;
                            }
                        }
                    }
                    
                    // save the licence list in the cache
                    $cacheItem->set($hasValidLicence);
                    $this->cache->save($cacheItem);  
                }
                else {
                    $hasValidLicence = false;
                }
            } catch (ClientException $exception) {
                throw new StoreApiException($exception);
            }
        }

        return $hasValidLicence;
	}

    /**
     * Returns a store auth token.
     * 
     * @param  Context $context
     * @return string
     */
    private function getUserStoreToken(Context $context)
    {
        $userToken = null;

        /* if (!$context->getSource() instanceof AdminApiSource) {
            throw new InvalidContextSourceException(AdminApiSource::class, \get_class($context->getSource()));
        }

        $userId = $context->getSource()->getUserId(); */

        /** @var UserEntity|null $user */
        $users = $this->userRepository->search(new Criteria(), $context)->getEntities();

        foreach($users as $user) {
            if($user->getStoreToken()) {
                $userToken = $user->getStoreToken();
            }
        }

        return $userToken;
    }
}