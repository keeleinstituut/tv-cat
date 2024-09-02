<?php

namespace Engines\NecTM\Auth;

use INIT;
use Predis\Client;
use Predis\CommunicationException;
use Predis\Connection\ConnectionException;
use RedisHandler;
use ReflectionException;
use RuntimeException;

class CachedKeycloakServiceAccountJwtRetriever implements ServiceAccountJwtRetrieverInterface
{
    private KeycloakServiceAccountJwtRetriever $jwtRetriever;
    private JwtTokenDecoder $decoder;

    private int $cacheExpiryDelay;

    public function __construct(KeycloakServiceAccountJwtRetriever $jwtRetriever, JwtTokenDecoder $decoder)
    {
        $this->jwtRetriever = $jwtRetriever;
        $this->decoder = $decoder;
        $this->cacheExpiryDelay = INIT::$NECTM_KEYCLOAK_CACHE_EXPIRY_DELAY;
    }

    /**
     * @return string
     * @throws ConnectionException
     * @throws ReflectionException
     */
    public function getJwt(): string
    {
        try {
            $cacheClient = $this->getCacheClient();
            if ($cacheClient->exists($this->getCacheKey()) && !empty($jwt = $cacheClient->get($this->getCacheKey()))) {
                return $jwt;
            }
        } catch (CommunicationException $e) {}

        $response = $this->jwtRetriever->sendClientCredentialsGrantRequest();
        $jwtToken = $response['access_token'] ?? '';

        if (!empty($jwtToken)) {
            try {
                isset($cacheClient) && $cacheClient->set($this->getCacheKey(), $jwtToken, 'EX', $this->getCacheTTL($response));
            } catch (CommunicationException $e) {}

            return $jwtToken;
        }

        throw new RuntimeException("Retrieving of service account JWT failed");
    }

    /**
     * @throws ReflectionException
     * @throws ConnectionException
     */
    private function getCacheClient(): Client
    {
        return (new RedisHandler())->getConnection();
    }

    private function getCacheKey(): string
    {
        return "nectm-service-account-jwt-{$this->jwtRetriever->getClientId()}";
    }

    private function getCacheTTL(array $response): int
    {
        $decodedToken = $this->decoder->decode($response['access_token']);

        if (!empty($decodedToken->exp)) {
            if (($ttl = $decodedToken->exp - time()) <= $this->cacheExpiryDelay) {
                throw new RuntimeException('Token expiration less that cache expiry delay: ' . $ttl);
            }

            return $ttl - $this->cacheExpiryDelay;
        }

        throw new RuntimeException("Token 'exp' is not defined");
    }
}