<?php

namespace Engines\NecTM\Auth;

use Firebase\JWT\Key;

interface RealmJwkRetrieverInterface
{
    /**
     * @param string|null $kid
     * @return Key|array
     */
    public function getJwkOrJwks(?string $kid = null);
}
