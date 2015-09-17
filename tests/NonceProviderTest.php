<?php
/**
 * @file
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

use CultuurNet\SymfonySecurityOAuth\Model\Consumer;
use M6Web\Component\RedisMock\RedisMockFactory;
use Predis\Client;
use Predis\ClientInterface;

class NonceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NonceProvider
     */
    private $nonceProvider;

    /**
     * @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var Consumer
     */
    private $consumer;

    public function setUp()
    {
        $factory = new RedisMockFactory();
        $this->client = $factory->getAdapter(Client::class, true);

        $this->nonceProvider = new NonceProvider($this->client);

        $this->consumer = new Consumer();
        $this->consumer->setConsumerKey('abc');
    }

    /**
     * @test
     */
    public function it_checks_for_already_used_nonces_per_consumer_and_timestamp()
    {
        $nonceIsAcceptable = $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            500,
            $this->consumer
        );

        $this->assertTrue($nonceIsAcceptable);

        $this->nonceProvider->registerNonceAndTimestamp(
            'foo',
            500,
            $this->consumer
        );

        $nonceIsAcceptable = $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            500,
            $this->consumer
        );

        $this->assertFalse($nonceIsAcceptable);

        $anotherNonceIsAcceptable = $this->nonceProvider->checkNonceAndTimestampUnicity(
            'bar',
            500,
            $this->consumer
        );

        $this->assertTrue($anotherNonceIsAcceptable);

        $anotherConsumer = new Consumer();
        $anotherConsumer->setConsumerKey('xyz');

        $nonceForAnotherConsumerIsAcceptable = $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            500,
            $anotherConsumer
        );

        $this->assertTrue($nonceForAnotherConsumerIsAcceptable);
    }

    /**
     * @test
     */
    public function it_sets_an_expiry_when_registering_used_nonces()
    {
        $this->nonceProvider->registerNonceAndTimestamp(
            'foo',
            500,
            $this->consumer
        );

        $remainingTtl = $this->client->ttl('nonces/key:abc/timestamp:500');

        $this->assertLessThanOrEqual(NonceProvider::DEFAULT_TTL, $remainingTtl);
        $this->assertGreaterThanOrEqual(NonceProvider::DEFAULT_TTL - 1, $remainingTtl);
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
    public function it_only_accepts_integers_bigger_than_zero_for_ttl($invalidTTLValue)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        new NonceProvider($this->client, $invalidTTLValue);
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_a_text_timestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Timestamp should be a positive integer, got abcdef'
        );

        $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            'abcdef',
            $this->consumer
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_a_negative_timestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'imestamp should be a positive integer, got -123456'
        );

        $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            -123456,
            $this->consumer
        );
    }

    public function it_does_not_throw_an_error_when_using_correct_timestamp()
    {
        $result = $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            '1442398946',
            $this->consumer
        );

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_a_decimal_timestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Timestamp should be a positive integer, got 1234.56'
        );

        $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            1234.56,
            $this->consumer
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_a_timestamp_smaller_than_last_registered()
    {
        $this->client->zadd("timestamps/key:{$this->consumer->getConsumerKey()}", 500, 500);

        $this->setExpectedException(
            'InvalidArgumentException',
            'Timestamp must be bigger than your last timestamp we have recorded'
        );

        $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            499,
            $this->consumer
        );
    }

    /**
     * @test
     */
    public function it_only_keeps_the_last_ten_timestamps_per_consumer()
    {
        $key = "timestamps/key:{$this->consumer->getConsumerKey()}";

        $this->nonceProvider->registerNonceAndTimestamp('wNcUhAXuMe', 500, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('dTAmpjPUbk', 600, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('blnDetYfmx', 700, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('SiakXrdbZv', 800, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('cduemCfTOQ', 900, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('MMoVLLMHOn', 1000, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('BGRvLDLmdz', 1100, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('jRQiFtutXj', 1200, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('RwwgRAOYlG', 1300, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('WTjPgqtpFM', 1400, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('EhnRTUPMaZ', 1500, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('RZiDsxDded', 1600, $this->consumer);
        $this->nonceProvider->registerNonceAndTimestamp('FiWVeIpbBm', 1700, $this->consumer);

        $count = count($this->client->zrange($key, 0, -1));

        $this->assertEquals(1, $count);
    }
}
