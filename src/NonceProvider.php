<?php
/**
 * @file
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

use CultuurNet\SymfonySecurityOAuth\Model\ConsumerInterface;
use CultuurNet\SymfonySecurityOAuth\Model\Provider\NonceProviderInterface;
use Predis\ClientInterface;

class NonceProvider implements NonceProviderInterface
{
    const DEFAULT_TTL = 1200;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param ClientInterface $client
     *   A Redis client implementation.
     * @param int $ttl
     *   Time to live for stored values, in seconds. By default 20 minutes.
     */
    public function __construct(
        ClientInterface $client,
        $ttl = NonceProvider::DEFAULT_TTL
    ) {
        $this->client = $client;

        if (!is_int($ttl)) {
            throw new \InvalidArgumentException(
                '$ttl should be an integer, got type ' . gettype($ttl)
            );
        }

        if ($ttl < 1) {
            throw new \InvalidArgumentException(
                '$ttl should be a positive number bigger than 0, got ' . $ttl
            );
        }

        $this->ttl = $ttl;
    }

    /**
     * @param string $nonce
     * @param ConsumerInterface $consumer
     * @return string
     */
    private function redisKey($nonce, ConsumerInterface $consumer)
    {
        return $consumer->getConsumerKey() . '-' . $nonce;
    }

    /**
     * @inheritdoc
     */
    public function checkNonceAndTimestampUnicity($nonce, $timestamp, ConsumerInterface $consumer)
    {
        $exists = $this->client->exists($this->redisKey($nonce, $consumer));

        return !$exists;
    }

    /**
     * @inheritdoc
     */
    public function registerNonceAndTimestamp($nonce, $timestamp, ConsumerInterface $consumer)
    {
        $this->client->set(
            $this->redisKey($nonce, $consumer),
            $timestamp,
            'ex',
            $this->ttl
        );

        return true;
    }
}
