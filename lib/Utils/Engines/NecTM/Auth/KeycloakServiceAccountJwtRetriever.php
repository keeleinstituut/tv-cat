<?php

namespace Engines\NecTM\Auth;

use INIT;
use RuntimeException;

class KeycloakServiceAccountJwtRetriever implements ServiceAccountJwtRetrieverInterface
{
    private string $baseUrl;
    private string $realm;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = INIT::$NECTM_KEYCLOAK_BASE_URL;
        $this->realm = INIT::$NECTM_KEYCLOAK_REALM;
        $this->clientId = INIT::$NECTM_KEYCLOAK_CLIENT_ID;
        $this->clientSecret = INIT::$NECTM_KEYCLOAK_CLIENT_SECRET;
    }

    public function getJwt(): string
    {
        return $this->sendClientCredentialsGrantRequest()['access_token'];
    }

    public function sendClientCredentialsGrantRequest(): array
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_URL, $this->getJwtRetrieveUrl());
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]));


        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            throw new RuntimeException("Retrieving of service account JWT failed");
        }

        curl_close($curl);
        return json_decode($response, true);
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    private function getJwtRetrieveUrl(): string
    {
        return "$this->baseUrl/realms/$this->realm/protocol/openid-connect/token";
    }
}
