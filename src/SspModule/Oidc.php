<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\SspModule;

use Psr\Log\LoggerInterface;
use SimpleSAML\Database;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\oidc\Repositories\AccessTokenRepository;
use SimpleSAML\Module\oidc\Repositories\ClientRepository;
use SimpleSAML\Module\oidc\Repositories\RefreshTokenRepository;
use SimpleSAML\Module\oidc\Services\Container;

class Oidc
{
    public const MODULE_NAME = 'oidc';
    public const DEFAULT_LIMIT = 50;

    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;
    protected bool $isEnabled = false;
    protected Container $container;
    protected Database $database;

    public function __construct(
        LoggerInterface $logger = null,
        HelpersManager $helpersManager = null,
        Container $container = null,
        Database $database = null
    ) {
        $this->logger = $logger ?? new Logger();
        $this->helpersManager = $helpersManager ?? new HelpersManager();
        $this->container = $container ?? new Container();
        $this->database = $database ?? Database::getInstance();

        $this->isEnabled = $this->helpersManager->getSspModule()->isEnabled(self::MODULE_NAME);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getUsersAccessTokens(
        string $userId,
        ?array $clientIds = null,
        ?int $limit = self::DEFAULT_LIMIT
    ): array {
        $sql = sprintf(
            <<<EOF
            SELECT id, client_id, expires_at, is_revoked
            FROM %s
            WHERE user_id = :user_id AND is_revoked = 0 AND expires_at > '%s'
            EOF,
            $this->getPrefixedTableName(AccessTokenRepository::TABLE_NAME),
            $this->helpersManager->getDateTime()->toFormattedString()
        );

        $params = ['user_id' => $userId,];

        if ($clientIds !== null) {
            $index = 0;
            $clientIdPlaceholders = [];
            $clientIdParams = [];

            /** @var string $clientId */
            foreach ($clientIds as $clientId) {
                $currentPlaceholder = 'client_id' . $index++;
                $clientIdPlaceholders[] = ':' . $currentPlaceholder;
                $clientIdParams[$currentPlaceholder] = $clientId;
            }

            $sql .= ' AND client_id IN (' . implode(', ', $clientIdPlaceholders) . ')';
            $params += $clientIdParams;
        }

        $sql .= ' ORDER BY client_id, expires_at';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $statement = $this->database->read($sql, $params);

        if (!$data = $statement->fetchAll(\PDO::FETCH_ASSOC)) {
            $this->logger->debug('No OIDC access tokens available for user ID ' . $userId);
            return [];
        }

        return $data;
    }

    /**
     * @param string $userId
     * @param string $accessTokenId
     * @return false|int
     */
    public function revokeUsersAccessToken(string $userId, string $accessTokenId)
    {
        $sql = sprintf(
            <<<EOF
            UPDATE %s
            SET is_revoked = 1
            WHERE user_id = :user_id AND id = :access_token_id
            EOF,
            $this->getPrefixedTableName(AccessTokenRepository::TABLE_NAME),
        );

        $params = ['user_id' => $userId, 'access_token_id' => $accessTokenId];

        return $this->database->write($sql, $params);
    }

    public function getUsersRefreshTokens(
        string $userId,
        ?array $clientIds = null,
        ?int $limit = self::DEFAULT_LIMIT
    ): array {
        $sql = sprintf(
            <<<EOF
            SELECT ort.id, oat.client_id, ort.expires_at, ort.is_revoked
            FROM %s AS ort
            INNER JOIN %s as oat ON ort.access_token_id = oat.id
            WHERE oat.user_id = :user_id AND ort.is_revoked = 0 AND ort.expires_at > '%s'
            EOF,
            $this->getPrefixedTableName(RefreshTokenRepository::TABLE_NAME),
            $this->getPrefixedTableName(AccessTokenRepository::TABLE_NAME),
            $this->helpersManager->getDateTime()->toFormattedString()
        );

        $params = ['user_id' => $userId,];

        if ($clientIds !== null) {
            $index = 0;
            $clientIdPlaceholders = [];
            $clientIdParams = [];

            /** @var string $clientId */
            foreach ($clientIds as $clientId) {
                $currentPlaceholder = 'client_id' . $index++;
                $clientIdPlaceholders[] = ':' . $currentPlaceholder;
                $clientIdParams[$currentPlaceholder] = $clientId;
            }

            $sql .= ' AND oat.client_id IN (' . implode(', ', $clientIdPlaceholders) . ')';
            $params += $clientIdParams;
        }

        $sql .= ' ORDER BY oat.client_id, ort.expires_at';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $statement = $this->database->read($sql, $params);

        if (!$data = $statement->fetchAll(\PDO::FETCH_ASSOC)) {
            $this->logger->debug('No OIDC refresh tokens available for user ID ' . $userId);
            return [];
        }

        return $data;
    }

    /**
     * @param string $userId
     * @param string $refreshTokenId
     * @return false|int
     */
    public function revokeUsersRefreshToken(string $userId, string $refreshTokenId)
    {
        $sql = sprintf(
            <<<EOF
            UPDATE %s AS ort
            INNER JOIN %s as oat ON ort.access_token_id = oat.id
            SET ort.is_revoked = 1
            WHERE oat.user_id = :user_id AND ort.id = :refresh_token_id
            EOF,
            $this->getPrefixedTableName(RefreshTokenRepository::TABLE_NAME),
            $this->getPrefixedTableName(AccessTokenRepository::TABLE_NAME),
        );

        $params = ['user_id' => $userId, 'refresh_token_id' => $refreshTokenId];

        return $this->database->write($sql, $params);
    }

    public function getClients(array $clientIds): array
    {
        $index = 0;
        $clientIdPlaceholders = [];
        $clientIdParams = [];

        /** @var string $clientId */
        foreach ($clientIds as $clientId) {
            $currentPlaceholder = 'client_id' . $index++;
            $clientIdPlaceholders[] = ':' . $currentPlaceholder;
            $clientIdParams[$currentPlaceholder] = $clientId;
        }

        $sql = sprintf(
            <<<EOF
            SELECT id, name, description
            FROM %s
            EOF,
            $this->getPrefixedTableName(ClientRepository::TABLE_NAME),
        );

        $sql .= ' WHERE id IN (' . implode(', ', $clientIdPlaceholders) . ')';
        $sql .= ' ORDER BY name';

        $params = $clientIdParams;

        $statement = $this->database->read($sql, $params);

        if (!$data = $statement->fetchAll(\PDO::FETCH_ASSOC)) {
            $this->logger->debug('No OIDC clients available for ' . var_export($clientIds, true));
            return [];
        }

        return $data;
    }

    protected function getPrefixedTableName(string $tableName): string
    {
        return $this->database->applyPrefix($tableName);
    }
}
