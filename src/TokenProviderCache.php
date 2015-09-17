<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 16/09/15
 * Time: 16:48
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

use CultuurNet\SymfonySecurityOAuth\Model\Provider\TokenProviderInterface;
use Predis\ClientInterface;

class TokenProviderCache implements TokenProviderInterface
{
    const DEFAULT_TTL = 1200;

    /** @var TokenProviderInterface */
    protected $tokenProvider;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param TokenProviderInterface $tokenProvider
     * @param ClientInterface $client
     * @param int $ttl
     */
    public function __construct(
        TokenProviderInterface $tokenProvider,
        ClientInterface $client,
        $ttl = TokenProviderCache::DEFAULT_TTL
    ) {
        $this->tokenProvider = $tokenProvider;
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
     * Returns the key to be used for a token.
     *
     * @param $oauth_token
     * @return string
     */
    protected function tokenKey($oauth_token)
    {
        return $this->tokenKeyPrefix() . $oauth_token;
    }

    /**
     * Returns the prefix of the key.
     *
     * @return string
     */
    protected function tokenKeyPrefix()
    {
        return "tokenProvider/token/key:";
    }

    /**
     * Get the cached token for a key.
     *
     * @param $key
     * @return string
     */
    protected function cacheGet($key)
    {
        $cachedToken = $this->client->get($key);
        return $cachedToken;
    }

    /**
     * Set the cache for a token.
     *
     * @param $key
     * @param string $value
     * @param null $expiration
     */
    protected function cacheSet($key, $value, $expiration = null)
    {
        $this->client->set($key, $value);

        if (!empty($expiration)) {
            $this->ttl = $expiration;
        }

        $this->client->expire($key, $this->ttl);
    }

    /**
     * @param string $oauth_token
     * @return \CultuurNet\SymfonySecurityOAuth\Model\TokenInterface
     */
    public function getAccessTokenByToken($oauth_token)
    {
        // Get the redis key.
        $key = $this->tokenKey($oauth_token);

        // If data is found in the redis cache, return it.
        $cached_token = $this->cacheGet($key);
        if (!empty($cached_token)) {
            return unserialize($cached_token);
        }

        // Else use the tokenprovider to fetch the data.
        $token = $this->tokenProvider->getAccessTokenByToken($oauth_token);

        // Then set the cache.
        $this->cacheSet($key, serialize($token));

        return $token;
    }
}
