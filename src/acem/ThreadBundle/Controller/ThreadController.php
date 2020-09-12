<?php


namespace acem\ThreadBundle\Controller;
use acem\ThreadBundle\Entity\Channels;
use acem\ThreadBundle\Entity\Reply;
use acem\ThreadBundle\Entity\Thread;
use acem\ThreadBundle\Entity\User;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
class ThreadController extends Controller
{

    public function indexAction()
    {
        $threads=$this->getDoctrine()->getRepository('acemThreadBundle:Thread')->findAll();

        $channels=$this->getDoctrine()->getRepository('acemThreadBundle:Channels')->findAll();

        return $this->render('@acemThread/thread/index.html.twig',[
            'threads'=>$threads,
            'channels'=>$channels
        ]);
    }
    public function showAction($channel,Thread $thread,Request $request)
    {
        $channels=$this->getDoctrine()->getRepository('acemThreadBundle:Channels')->findAll();

        $channelId = $this->getDoctrine()
            ->getRepository(Channels::class)
            ->findOneBy(['name'=>$channel]);

        $entityManager = $this->getDoctrine()->getManager();
        $threads = $entityManager->getRepository(Thread::class)->findOneBy([
            'id'=>$thread->getId(),
            'channel'=>$channelId->getId()
        ]);
        if (!$threads) {
            throw $this->createNotFoundException(
                'No thread found for id '.$threads
            );
        }
        // ADD a reply to a thread
        $reply =new Reply();
        //get the reply form
        $form = $this->getReplyForm($reply);
        // check if a post request to create a reply
        if ($request->isMethod('POST')) {
            $user=$this->auth();
            $reply->setOwner($user);
            $reply->setThread($thread);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($reply);
                $entityManager->flush();
            }
        }
        return $this->render('@acemThread/thread/show.html.twig',[
            'rep'=> $form->createView(),
            'threads'=>$threads,
            'channels'=> $channels
        ]);
    }

    public function createAction(){

        $channels=$this->getDoctrine()->getRepository('acemThreadBundle:Channels')->findAll();

        return $this->render('@acemThread/thread/create.html.twig',[
            'channels'=>$channels
        ]);
    }

    public function storeAction(Request $request){

        $channels=$this->getDoctrine()->getRepository('acemThreadBundle:Channels')->findAll();

        $thread = new Thread();
        // retrive the authenticated user and redirect login page  if not auth
        $user=$this->auth();

        $thread->setOwner($user);

        $form = $this->getForm($thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             $entityManager = $this->getDoctrine()->getManager();
             $entityManager->persist($thread);
             $entityManager->flush();
            return $this->redirectToRoute('acem_Thread_homepage');
        }
        return $this->render('@acemThread/thread/create.html.twig', [
            'form' => $form->createView(),
            'channels'=>$channels
        ]);
    }

    public function listAction($channel)
    {
        $channels=$this->getDoctrine()->getRepository('acemThreadBundle:Channels')->findAll();

        $channelId = $this->getDoctrine()
            ->getRepository(Channels::class)
            ->findOneBy(['slug'=>$channel]);

        $entityManager = $this->getDoctrine()->getManager();
        //find  all thread filtred by channel name
        $threads = $entityManager->getRepository(Thread::class)->findBy([
            'channel'=>$channelId->getId()
        ],[
            'createdAt'=> 'DESC'
        ]);
        return $this->render('@acemThread/thread/index.html.twig',[
            'threads'=>$threads,
            'channels'=>$channels
        ]);
    }
    public function deleteAction($channel,Thread $thread){

        $channels=$this->getDoctrine()->getRepository('acemThreadBundle:Channels')->findAll();

        $channelId = $this->getDoctrine()
            ->getRepository(Channels::class)
            ->findOneBy(['name'=>$channel]);

        $entityManager = $this->getDoctrine()->getManager();
        $threads = $entityManager->getRepository(Thread::class)->findOneBy([
            'id'=>$thread->getId(),
            'channel'=>$channelId->getId()
        ]);
        $user=$this->auth()->getId();
        if($user == $thread->getOwner()->getId() ){
            $entityManager->remove($threads);
            $entityManager->flush();
        }
        else{
            // redirect if the user not auth to delete the thread
            return new Response('<h1 class="alert-danger">access denied</h1>', 403);
        }
        return $this->redirectToRoute('acem_Thread_homepage');
    }

    /**
     * @return object|UserInterface|null
     */
    protected function auth()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        return $user;
    }

    /**
     * @param Thread $thread
     * @return \Symfony\Component\Form\FormInterface
     */
    //Get the Thread form
    protected function getForm(Thread $thread)
    {

        $form = $this->createFormBuilder($thread)
            ->add('channel', EntityType ::class, [
                'label' => 'choose a channel',
                'class' => Channels::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose one..',
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ]
            ])
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ]
            ])
            ->add('body', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'clos' => 30,
                    'rows' => "10",
                    'required' => true,
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Publish',
                'attr' => [
                    'class' => 'btn btn-primary',
                    'required' => true,
                ]

            ])
            ->getForm();
        return $form;
    }

    /**
     * @param Reply $reply
     * @return \Symfony\Component\Form\FormInterface
     */
    // get the reply form
    protected function getReplyForm(Reply $reply)
    {
        $form = $this->createFormBuilder($reply)
            ->add('body', TextareaType::class, [
                'label' => '  ',
                'attr' => [
                    'placeholder' => 'have  something to say !!',
                    'class' => 'form-control',
                    'clos' => 30,
                    'rows' => "5",
                    'required' => true,
                ]
            ])
            ->add('Post', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                    'required' => true,
                ]

            ])->getForm();
        return $form;
    }
}