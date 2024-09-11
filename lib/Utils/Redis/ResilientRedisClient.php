<?php

namespace Redis;

use Predis\Client;
use Predis\CommunicationException;

class ResilientRedisClient extends Client
{
    const MAX_REDIS_RECONNECTION_ATTEMPTS = 5;

    /**
     * {@inheritdoc}
     * @throws CommunicationException
     */
    public function __call($commandID, $arguments)
    {
        $command = $this->createCommand($commandID, $arguments);

        try {
            return $this->executeCommand($command);
        } catch (CommunicationException $e) {
            $this->reconnect();
        }

        return $this->executeCommand($command);
    }

    /**
     * @throws CommunicationException
     */
    private function reconnect()
    {
        $attempt = 0;
        do {
            $attempt++;
            try {
                $this->disconnect();
                $this->connect();
                return;
            } catch (CommunicationException $e) {
                sleep(1);
            }
        } while ($attempt <= self::MAX_REDIS_RECONNECTION_ATTEMPTS);

        throw $e;
    }
}