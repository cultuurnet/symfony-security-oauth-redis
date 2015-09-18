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
     * @param ConsumerInterface $consumer
     * @return string
     */
    private function timestampsKey(ConsumerInterface $consumer)
    {
        return "timestamps/key:{$consumer->getConsumerKey()}";
    }

    /**
     * Helper function to perform necessary checks on timestamp and nonce.
     *
     * @param $nonce
     * @param $timestamp
     * @param ConsumerInterface $consumer
     * @return bool
     */
    public function checkNonceAndTimestampUnicity($nonce, $timestamp, ConsumerInterface $consumer)
    {
        // Check timestamp: The timestamp value MUST be a positive integer
        // and MUST be equal or greater than the timestamp used in previous requests.
        // @see http://oauth.net/core/1.0/#nonce
        if (!ctype_digit($timestamp)) {
            throw new \InvalidArgumentException(
                'Timestamp should be a positive integer, got ' . $this->checkPlain($timestamp)
            );
        }

        $timestampsKey = $this->timestampsKey($consumer);
        $sortedSet = $this->client->zrevrange($timestampsKey, 0, -1);
        if (is_array($sortedSet) && !empty($sortedSet)) {
            $maxTimestamp = $sortedSet[0];

            if ($timestamp < $maxTimestamp) {
                throw new \InvalidArgumentException(
                    'Timestamp must be bigger than your last timestamp we have recorded'
                );
            }
        }

        $noncesRedisKey = $this->noncesRedisKey($consumer, $timestamp);
        $exists = $this->client->sismember($noncesRedisKey, $nonce);

        return !$exists;
    }

    /**
     * @inheritdoc
     */
    public function registerNonceAndTimestamp($nonce, $timestamp, ConsumerInterface $consumer)
    {
        if ($this->checkNonceAndTimestampUnicity($nonce, $timestamp, $consumer)) {
            $noncesRedisKey = $this->noncesRedisKey($consumer, $timestamp);
            $this->client->sadd($noncesRedisKey, [$nonce]);
            $this->client->expire($noncesRedisKey, $this->ttl);
            $timestampsKey = $this->timestampsKey($consumer);
            $this->client->zadd($timestampsKey, $timestamp, $timestamp);

            // While we're here, only keep the top 10 items.
            $sortedSet = $this->client->zrevrange($timestampsKey, 0, -1);
            if (is_array($sortedSet) && !empty($sortedSet)) {
                $lastTimestamp = $sortedSet[0];
                $this->client->zremrangebyscore($timestampsKey, 0, $lastTimestamp - 1);
            }

            return true;
        } else {
            return false;
        }
    }

    protected function checkPlain($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
