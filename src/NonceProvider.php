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
     * @param integer $timestamp
     * @param ConsumerInterface $consumer
     * @return string
     */
    private function noncesRedisKey(ConsumerInterface $consumer, $timestamp)
    {
        return "nonces/key:{$consumer->getConsumerKey()}/timestamp:{$timestamp}";
    }

    /**
     * @inheritdoc
     */
    public function checkNonceAndTimestampUnicity($nonce, $timestamp, ConsumerInterface $consumer)
    {
        $noncesRedisKey = $this->noncesRedisKey($consumer, $timestamp);
        $exists = $this->client->sismember($noncesRedisKey, $nonce);

        return !$exists;
    }

    /**
     * @inheritdoc
     */
    public function registerNonceAndTimestamp($nonce, $timestamp, ConsumerInterface $consumer)
    {
        $noncesRedisKey = $this->noncesRedisKey($consumer, $timestamp);
        $this->client->sadd($noncesRedisKey, [$nonce]);
        $this->client->expire($noncesRedisKey, $this->ttl);

        return true;
    }
}
