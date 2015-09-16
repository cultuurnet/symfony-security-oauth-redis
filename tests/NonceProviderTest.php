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
            'Timestamp should be an integer, got abcdef'
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
            'Timestamp should be a positive number bigger than 0, got -123456'
        );

        $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            -123456,
            $this->consumer
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_a_decimal_timestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Timestamp should be an integer, got 1234.56'
        );

        $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            1234.56,
            $this->consumer
        );
    }
}
