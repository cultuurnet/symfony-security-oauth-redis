<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 10/09/15
 * Time: 12:57
 */

namespace CultuurNet\SymfonySecurityOAuthRedis;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;

class UserMock implements UserInterface
{
    /**
     * @var string
     */
    protected $uid;

    /**
     * @var string
     */
    protected $nick;

    /**
     * @var string
     */
    protected $email;

    /**
     * @param string $uid
     * @param string $nick
     * @param string $email
     */
    public function __construct($uid, $nick, $email)
    {
        $this->uid = $uid;
        $this->nick = $nick;
        $this->email = $email;
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return '';
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->nick;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }
}
