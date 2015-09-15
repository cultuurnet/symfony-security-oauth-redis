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
    function __construct(ClientInterface $client, $ttl = 1200)
    {
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
        return sha1($consumer->getConsumerKey() . '-' . $nonce);
    }

    /**
     * @param $nonce
     * @param $timestamp
     * @param  \CultuurNet\SymfonySecurityOAuth\Model\ConsumerInterface $consumer
     * @return boolean
     */
    public function checkNonceAndTimestampUnicity($nonce, $timestamp, ConsumerInterface $consumer)
    {
        $exists = $this->client->exists($this->redisKey($nonce, $consumer));

        return !$exists;
    }

    /**
     * @param $nonce
     * @param $timestamp
     * @param  \CultuurNet\SymfonySecurityOAuth\Model\ConsumerInterface $consumer
     * @return boolean
     */
    public function registerNonceAndTimestamp($nonce, $timestamp, ConsumerInterface $consumer)
    {
        return $this->client->set(
            $this->redisKey($nonce, $consumer),
            json_encode(
                [
                    'nonce' => $nonce,
                    'timestamp' => $timestamp,
                    'consumer' => $consumer->getConsumerKey()
                ]
            ),
            'ex',
            $this->ttl
        );
    }
}
