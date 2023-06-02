<?php

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Traits\Store;

use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\RawConnectedService;
use SimpleSAML\Module\accounting\Entities\ConnectedService;
use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use Throwable;

trait GettableConnectedServicesTrait
{
    /**
     * @throws StoreException
     */
    public function getConnectedServices(string $userIdentifier): ConnectedService\Bag
    {
        $connectedServiceProviderBag = new ConnectedService\Bag();

        $userIdentifierHashSha256 = $this->helpersManager->getHash()->getSha256($userIdentifier);

        $results = $this->repository->getConnectedServices($userIdentifierHashSha256);

        if (empty($results)) {
            return $connectedServiceProviderBag;
        }

        try {
            $databasePlatform = $this->connection->dbal()->getDatabasePlatform();

            /** @var array $result */
            foreach ($results as $result) {
                $rawConnectedServiceProvider = new RawConnectedService($result, $databasePlatform);

                $serviceProvider = $this->helpersManager
                    ->getProviderResolver()
                    ->forServiceFromMetadataArray($rawConnectedServiceProvider->getServiceProviderMetadata());
                $user = new User($rawConnectedServiceProvider->getUserAttributes());

                $connectedServiceProviderBag->addOrReplace(
                    new ConnectedService(
                        $serviceProvider,
                        $rawConnectedServiceProvider->getNumberOfAuthentications(),
                        $rawConnectedServiceProvider->getLastAuthenticationAt(),
                        $rawConnectedServiceProvider->getFirstAuthenticationAt(),
                        $user
                    )
                );
            }
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error populating connected service provider bag. Error was: %s',
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $connectedServiceProviderBag;
    }
}
