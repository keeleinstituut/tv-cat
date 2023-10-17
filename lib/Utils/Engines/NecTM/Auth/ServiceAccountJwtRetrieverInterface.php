<?php

namespace Engines\NecTM\Auth;

interface ServiceAccountJwtRetrieverInterface
{
    public function getJwt(): string;
}