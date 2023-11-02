<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Traits\Store;

use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\RawActivity;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use Throwable;

trait GettableActivityTrait
{
    /**
     * @throws StoreException
     */
    public function getActivity(string $userIdentifier, int $maxResults = null, int $firstResult = 0): Activity\Bag
    {
        $userIdentifierHashSha256 = $this->helpersManager->getHash()->getSha256($userIdentifier);

        $results =  $this->repository->getActivity($userIdentifierHashSha256, $maxResults, $firstResult);

        $activityBag = new Activity\Bag();

        if (empty($results)) {
            return $activityBag;
        }

        try {
            /** @var array $result */
            foreach ($results as $result) {
                $rawActivity = new RawActivity(
                    $result,
                    $this->connection->dbal()->getDatabasePlatform(),
                    $this->serializer
                );
                $serviceProvider = $this->helpersManager
                    ->getProviderResolver()
                    ->forServiceFromMetadataArray($rawActivity->getServiceProviderMetadata());
                $user = new User($rawActivity->getUserAttributes());

                $activityBag->add(
                    new Activity(
                        $serviceProvider,
                        $user,
                        $rawActivity->getHappenedAt(),
                        $rawActivity->getClientIpAddress(),
                        $rawActivity->getAuthenticationProtocolDesignation()
                    )
                );
            }
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error populating activity bag. Error was: %s',
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $activityBag;
    }
}
