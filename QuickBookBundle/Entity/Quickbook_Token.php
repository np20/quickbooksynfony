<?php

namespace vTechSolution\Bundle\QuickBookBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Quickbook_Token
 *
 * @ORM\Table(name="quickbook__token")
 * @ORM\Entity(repositoryClass="vTechSolution\Bundle\QuickBookBundle\Repository\Quickbook_TokenRepository")
 */
class Quickbook_Token
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="access_token", type="text")
     */
    private $access_token;

    /**
     * @var string
     *
     * @ORM\Column(name="refresh_token", type="text")
     */
    private $refreshToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="token_start", type="datetime")
     */
    private $tokenStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="token_expire", type="datetime")
     */
    private $tokenExpire;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Quickbook_Token
     */
    public function setAccess_token($access_token)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getAccess_token()
    {
        return $this->code;
    }

    /**
     * Set refreshToken
     *
     * @param string $refreshToken
     *
     * @return Quickbook_Token
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * Get refreshToken
     *
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set tokenStart
     *
     * @param \DateTime $tokenStart
     *
     * @return Quickbook_Token
     */
    public function setTokenStart($tokenStart)
    {
        $this->tokenStart = $tokenStart;

        return $this;
    }

    /**
     * Get tokenStart
     *
     * @return \DateTime
     */
    public function getTokenStart()
    {
        return $this->tokenStart;
    }

    /**
     * Set tokenExpire
     *
     * @param \DateTime $tokenExpire
     *
     * @return Quickbook_Token
     */
    public function setTokenExpire($tokenExpire)
    {
        $this->tokenExpire = $tokenExpire;

        return $this;
    }

    /**
     * Get tokenExpire
     *
     * @return \DateTime
     */
    public function getTokenExpire()
    {
        return $this->tokenExpire;
    }
}

