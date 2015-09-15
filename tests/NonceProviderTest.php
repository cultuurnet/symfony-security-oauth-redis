<?php
/**
 * @file
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

use CultuurNet\SymfonySecurityOAuth\Model\Consumer;
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
        $this->client = $this->getMock(ClientInterface::class);

        $this->nonceProvider = new NonceProvider(
            $this->client
        );

        $this->consumer = new Consumer();
        $this->consumer->setConsumerKey('abc');
    }

    /**
     * @test
     */
    public function it_checks_for_already_used_nonces_per_consumer()
    {
        $anotherConsumer = new Consumer();
        $anotherConsumer->setConsumerKey('xyz');

        $this->client->expects($this->exactly(2))
            ->method('__call')
            ->withConsecutive(
                ['exists', ['abc-foo']],
                ['exists', ['xyz-foo']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $nonceIsAcceptable = $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            500,
            $this->consumer
        );

        $this->assertFalse($nonceIsAcceptable);

        $nonceIsAcceptable = $this->nonceProvider->checkNonceAndTimestampUnicity(
            'foo',
            500,
            $anotherConsumer
        );

        $this->assertTrue($nonceIsAcceptable);
    }

    /**
     * @test
     */
    public function it_sets_an_expiry_when_registering_used_nonces()
    {
        $this->client->expects($this->once())
            ->method('__call')
            ->with('set', ['abc-foo', 500, 'ex', NonceProvider::DEFAULT_TTL]);

        $succeeded = $this->nonceProvider->registerNonceAndTimestamp(
            'foo',
            500,
            $this->consumer
        );

        $this->assertTrue($succeeded);
    }
}
