<?php

namespace Engines\NecTM\Auth;

use Exception;
use INIT;
use RuntimeException;

class JwtTokenDecoder
{
    private string $keycloakBaseUrl;
    private string $realm;

    private int $leeway;

    private RealmJwkRetrieverInterface $jwkRetriever;

    public function __construct(RealmJwkRetrieverInterface $jwkRetriever)
    {
        $this->keycloakBaseUrl = INIT::$NECTM_KEYCLOAK_BASE_URL;
        $this->realm = INIT::$NECTM_KEYCLOAK_REALM;
        $this->leeway = INIT::$NECTM_KEYCLOAK_LEEWAY;
        $this->jwkRetriever = $jwkRetriever;
    }

    public function decode(string $token): ?object
    {
        try {
            $kid = JwtToken::getHeader($token)->kid ?? null;
            $token = JwtToken::decode(
                $token,
                $this->jwkRetriever->getJwkOrJwks($kid),
                $this->leeway
            );
        } catch (Exception $e) {
            throw new RuntimeException('JWT token is invalid', 0, $e);
        }

        if (empty($token)) {
            return null;
        }

        $this->validate($token);

        return $token;
    }

    /**
     * @throws RuntimeException
     */
    private function validate(object $token): void
    {
        if (!property_exists($token, 'iss')) {
            throw new RuntimeException("Token 'iss' is not defined");
        }

        if ($token->iss !== $this->getExpectedIssuer()) {
            throw new RuntimeException("Token 'iss' is invalid");
        }
    }

    private function getExpectedIssuer(): string
    {
        return "$this->keycloakBaseUrl/realms/$this->realm";
    }
}