<?php

namespace acem\ThreadBundle\Controller;

use acem\ThreadBundle\Entity\Thread;
use acem\ThreadBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ProfileController extends Controller
{
    public function showAction($name){
        $channels=$this->getDoctrine()->getRepository('acemThreadBundle:Channels')->findAll();
        $entityManager=$this->getDoctrine()->getManager();
        $owner=$entityManager->getRepository(User::class)->findOneBy([
            'username'=>$name
        ]);
        $threads=$entityManager->getRepository(Thread::class)->findBy([
            'owner'=>$owner->getId()
        ],[
                'createdAt'=> 'DESC',
            ]);
        return $this->render('@acemThread/profile/show.html.twig',[
            'threads'=>$threads,
            'channels'=> $channels,
            'owner'=> $owner
        ]);
    }
    protected function auth()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        return $user;
    }
}
