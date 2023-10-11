<?php

namespace Engines\NecTM\Auth;

use Firebase\JWT\JWT;
use UnexpectedValueException;

class JwtToken
{
    /**
     * Decode a JWT token
     */
    public static function decode(string $token, $jwkOrJwks, int $leeway = 0): object
    {
        JWT::$leeway = $leeway;
        return JWT::decode($token, $jwkOrJwks, array_keys(JWT::$supported_algs));
    }

    public static function getHeader(string $jwt): object
    {
        $tks = explode('.', $jwt);
        if (count($tks) !== 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        $headerBase64encoded = $tks[0];
        $headerRaw = JWT::urlsafeB64Decode($headerBase64encoded);
        if (null === ($header = JWT::jsonDecode($headerRaw))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }

        return $header;
    }
}