<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StaticController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        return $this->render('index.html.twig');
    }

    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        return $this->render('about.html.twig');
    }

    /**
     * @Route("/contacts", name="contacts")
     */
    public function contacts(Request $request, \Swift_Mailer $mailer)
    {
        $form = $this->createFormBuilder([])
            ->add('name', TextType::class, ['required' => false, 'label' => false, 'attr' => ['placeholder' => 'Name']])
            ->add('email', EmailType::class, ['required' => true, 'label' => false, 'attr' => ['placeholder' => 'e-mail']])
            ->add('subject', TextType::class, ['required' => false, 'label' => false, 'attr' => ['placeholder' => 'Subject']])
            ->add('message', TextareaType::class, ['required' => false, 'label' => false, 'attr' => ['placeholder' => 'Message', 'rows' => '3']])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();
//            $message['message'] = str_replace("\r\n", '<br>',  $message['message']);

            $this->sendmail($message, $mailer);

            $this->addFlash('success', 'The message was sent successfully. Thank You!');

            return $this->redirectToRoute('contacts');
        }

        return $this->render('contacts.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    function sendmail($mailMessage, \Swift_Mailer $mailer)
    {
        $message = (new \Swift_Message())
            ->setSubject('Message from Adri\'s light site')
            ->setFrom($mailMessage['email'])
            ->setTo(getenv('mymail'))
            ->setBody($this->renderView('email.html.twig',
                ['message' => $mailMessage,]
            ), 'text/html');
        $mailer->send($message);
    }

}
