<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 17/09/15
 * Time: 11:27
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

use M6Web\Component\RedisMock\RedisMockFactory;
use Predis\Client;
use Predis\ClientInterface;

class TokenProviderCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var TokenProviderCache */
    private $tokenProviderCache;

    /** @var TokenProviderMock  */
    private $tokenProvider;

    /**
     * @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    public function setUp()
    {
        $factory = new RedisMockFactory();
        $this->client = $factory->getAdapter(Client::class, true);

        $this->tokenProvider = new TokenProviderMock();

        $this->tokenProviderCache = new TokenProviderCacheMock($this->tokenProvider, $this->client);
    }

    /**
     * Data provider with invalid values for the constructor $ttl argument.
     */
    public function invalidTTLProvider()
    {
        return [
            ['foo'],
            [0],
            [-1],
        ];
    }

    /**
     * @test
     * @dataProvider invalidTTLProvider
     * @param mixed $invalidTTLValue
     */
    public function testOnlyAcceptsIntegersBiggerThanZeroForTTL($invalidTTLValue)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $tokenProvider = new TokenProviderMock();
        new TokenProviderCache($tokenProvider, $this->client, $invalidTTLValue);
    }

    /**
     * @test
     */
    public function testTokenGetsCachedWhenQueriedForTheFirstTime()
    {
        $token = $this->tokenProviderCache->getAccessTokenByToken('nnch734d00sl2jdk');
        $serializedToken = serialize($token);
        $cachedToken = $this->client->get('tokenProvider/token/key:nnch734d00sl2jdk');

        $this->assertEquals($serializedToken, $cachedToken);
    }

    /**
     * @test
     */
    public function testGetPrefix()
    {
        $keyPrefix = $this->tokenProviderCache->tokenKeyPrefix();
        $expectedKeyPrefix = 'tokenProvider/token/key:';
        $this->assertEquals($expectedKeyPrefix, $keyPrefix);
    }

    /**
     * @test
     */
    public function testGetKey()
    {
        $key = $this->tokenProviderCache->tokenKey('nnch734d00sl2jdk');
        $expectedKey = 'tokenProvider/token/key:nnch734d00sl2jdk';
        $this->assertEquals($expectedKey, $key);
    }

    /**
     * @test
     */
    public function testCacheSet()
    {
        $token = $this->tokenProvider->getAccessTokenByToken('nnch734d00sl2jdk');
        $serializedToken = serialize($token);
        $key = 'tokenProvider/token/key:nnch734d00sl2jdk';

        $this->tokenProviderCache->cacheSet($key, $serializedToken);
        $cachedToken = $this->client->get($key);

        $this->assertEquals($serializedToken, $cachedToken);
    }

    /**
     * @test
     */
    public function testCacheGet()
    {
        $token = $this->tokenProvider->getAccessTokenByToken('nnch734d00sl2jdk');
        $serializedToken = serialize($token);
        $key = 'tokenProvider/token/key:nnch734d00sl2jdk';
        $this->client->set($key, $serializedToken);

        $cachedToken = $this->tokenProviderCache->cacheGet($key);

        $this->assertEquals($serializedToken, $cachedToken);
    }

    /**
     * @test
     */
    public function testSettingACustomExpiration()
    {
        $token = $this->tokenProvider->getAccessTokenByToken('nnch734d00sl2jdk');
        $serializedToken = serialize($token);
        $key = 'tokenProvider/token/key:nnch734d00sl2jdk';

        $this->tokenProviderCache->cacheSet($key, $serializedToken, 500);
        $ttl = $this->client->ttl($key);

        $this->assertEquals(500, $ttl);
    }

    /**
     * @test
     */
    public function testGettingcachedVersionOfToken()
    {
        // First time, we get token from API, it gets cached.
        $token = $this->tokenProviderCache->getAccessTokenByToken('nnch734d00sl2jdk');

        // Second time, this should be the cached token.
        $token2 = $this->tokenProviderCache->getAccessTokenByToken('nnch734d00sl2jdk');

        $this->assertEquals($token, $token2);
    }
}
