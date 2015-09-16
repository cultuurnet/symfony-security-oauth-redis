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
        // Check timestamp: The timestamp value MUST be a positive integer
        // and MUST be equal or greater than the timestamp used in previous requests.
        // @see http://oauth.net/core/1.0/#nonce
        if (!is_integer($timestamp)) {
            throw new \InvalidArgumentException(
                'Timestamp should be an integer, got ' . $this->checkPlain($timestamp)
            );
        }

        if ($timestamp < 0) {
            throw new \InvalidArgumentException(
                'Timestamp should be a positive number bigger than 0, got ' . $this->checkPlain($timestamp)
            );
        }

        //$maxTimestamp = $this->client->
        /*if ($timestamp < $maxTimestamp) {
            throw new \InvalidArgumentException(
                'Timestamp must be bigger than the last timestamp we have recorded'
            );
        }*/

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

    protected function checkPlain($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
