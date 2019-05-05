<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StaticController extends AbstractController
{
    private $message = [];

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
    public function contacts(Request $request)
    {
        if (!empty($request->getContent())) {
            $messageArr['name'] = strip_tags($request->get('name'));
            $messageArr['email'] = strip_tags($request->get('email'));
            $messageArr['subject'] = strip_tags($request->get('subject'));
            $messageArr['message'] = strip_tags($request->get('message'));

//            $this->message = $messageArr;
//            $this->sendmail(new \Swift_Mailer('gmail'));

//            dd($name, $email, $subject, $message);
            $this->addFlash('success', 'The message was NOT sent successfully. Please send me e-mail');
//            $this->addFlash('success', 'The message was sent successfully. Thank You!');

            return $this->redirectToRoute('home');
        }

        return $this->render('contacts.html.twig');
    }

    function sendmail(\Swift_Mailer $mailer)
    {
        $message = (new \Swift_Message())
            ->setSubject('Message from Adri\'s light site')
            ->setFrom($this->message['email'])
            ->setTo(getenv('mymail'))
            ->setBody($this->renderView('email.html.twig',
                ['message' => $this->message,]
            ), 'text/html');
        $mailer->send($message);
    }

}
