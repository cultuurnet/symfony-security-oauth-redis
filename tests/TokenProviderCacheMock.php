<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 17/09/15
 * Time: 11:51
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

class TokenProviderCacheMock extends TokenProviderCache
{
    public function tokenKey($oauth_token)
    {
        return parent::tokenKey($oauth_token);
    }

    public function tokenKeyPrefix()
    {
        return parent::tokenKeyPrefix();
    }

    public function cacheGet($key)
    {
        return parent::cacheGet($key);
    }

    public function cacheSet($key, $value, $expiration = null)
    {
        return parent::cacheSet($key, $value, $expiration);
    }

    public function cacheFlush()
    {
        return parent::cacheFlush();
    }
}
