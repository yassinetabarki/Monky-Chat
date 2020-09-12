<?php

namespace acem\ThreadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ODM\CouchDB\Mapping\Annotations as CouchDB;
/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Thread", mappedBy="owner")
     */
    private $threads;

    /**
     * @ORM\OneToMany(targetEntity="Reply", mappedBy="owner")
     */
    private $replies;


    public function __construct()
    {
        parent::__construct();
        $this->threads = new ArrayCollection();
        $this->replies = new ArrayCollection();
    }
}

