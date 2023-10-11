<?php

namespace Engines\NecTM\Auth;

use Firebase\JWT\JWK;
use INIT;
use RuntimeException;

class ApiRealmJwkRetriever implements RealmJwkRetrieverInterface
{
    private string $keycloakBaseUrl;

    private string $realm;

    public function __construct()
    {
        $this->keycloakBaseUrl = INIT::$NECTM_KEYCLOAK_BASE_URL;
        $this->realm = INIT::$NECTM_KEYCLOAK_REALM;
    }


    /**
     * @inheritDoc
     */
    public function getJwkOrJwks(?string $kid = null)
    {
        if (empty($kid)) {
            return JWK::parseKeySet($this->getJwksAsArray());
        }

        $jwks = $this->getJwksAsArray();
        foreach ($jwks['keys'] as $jwk) {
            if ($jwk['kid'] === $kid) {
                return JWK::parseKeySet(['keys' => [$jwk]]);
            }
        }

        throw new RuntimeException("jwk not found for the ID: $kid");
    }

    public function getJwksAsArray(): array
    {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $this->getJwksUrl());

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($httpCode != 200) {
                throw new RuntimeException('Public key retrieval failed. The keycloak response status code is '.$httpCode);
            }

            $responseContent = json_decode($response, true);

            if (! isset($responseContent['keys'])) {
                throw new RuntimeException('Jwks retrieval failed');
            }

            return $responseContent;
    }

    private function getJwksUrl(): string
    {
        return "$this->keycloakBaseUrl/realms/$this->realm/protocol/openid-connect/certs";
    }
}
