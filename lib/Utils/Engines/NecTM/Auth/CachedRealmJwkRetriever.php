<?php

namespace Engines\NecTM\Auth;

use Firebase\JWT\JWK;
use INIT;
use Predis\Client;
use Predis\Connection\ConnectionException;
use RedisHandler;
use ReflectionException;

class CachedRealmJwkRetriever implements RealmJwkRetrieverInterface
{
    private ApiRealmJwkRetriever $apiRetriever;

    private string $realm;
    private int $cacheTTL;

    public function __construct(ApiRealmJwkRetriever $apiRetriever)
    {
        $this->apiRetriever = $apiRetriever;
        $this->realm = INIT::$NECTM_KEYCLOAK_REALM;
        $this->cacheTTL = INIT::$NECTM_KEYCLOAK_JWK_CACHE_TTL;
    }


    /**
     * @throws ReflectionException
     * @throws ConnectionException
     */
    public function getJwkOrJwks(?string $kid = null)
    {
        $cacheClient = $this->getCacheClient();
        if ($cacheClient->exists($this->getCacheKey($kid))) {
            $jwks = $cacheClient->get($this->getCacheKey($kid));
            return JWK::parseKeySet(json_decode($jwks, true));
        }

        $jwks = $this->apiRetriever->getJwksAsArray();
        foreach ($jwks['keys'] as $jwk) {
            $cacheClient->set(
                $this->getCacheKey($jwk['kid']),
                json_encode(['keys' => [$jwk]]),
                'EX',
                $this->cacheTTL
            );
        }

        return JWK::parseKeySet($jwks);
    }

    private function getCacheKey($kid): string
    {
        return "$this->realm-realm-jwk-$kid";
    }

    /**
     * @throws ReflectionException
     * @throws ConnectionException
     */
    private function getCacheClient(): Client
    {
        return (new RedisHandler())->getConnection();
    }

}