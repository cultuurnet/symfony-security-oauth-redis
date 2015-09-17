<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 10/09/15
 * Time: 11:06
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

use CultuurNet\SymfonySecurityOAuth\Model\Consumer;
use CultuurNet\SymfonySecurityOAuth\Model\Provider\TokenProviderInterface;
use CultuurNet\SymfonySecurityOAuth\Model\Token;

class TokenProviderMock implements TokenProviderInterface
{

    /**
     * @param string $oauth_token
     * @return \CultuurNet\SymfonySecurityOAuth\Model\TokenInterface
     */
    public function getAccessTokenByToken($oauth_token)
    {
        $token = new Token();
        $token->setToken('nnch734d00sl2jdk');
        $token->setSecret('pfkkdhi9sl3r4s00');

        $consumer = new Consumer();
        $consumer->setConsumerKey('dpf43f3p2l4k3l03');
        $consumer->setConsumerSecret('kd94hf93k423kf44');
        $consumer->setName('testConsumer');

        $token->setConsumer($consumer);

        $user = new UserMock('123456789', 'testUser', 'email@email.email');

        $token->setUser($user);

        return $token;
    }
}
