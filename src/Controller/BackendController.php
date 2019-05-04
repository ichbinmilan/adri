<?php

namespace App\Controller;


use App\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Intervention\Image\ImageManager;


class BackendController extends AbstractController
{
    /**
     * @Route("/backend", name="backend")
     */
    public function backend(){
        return $this->render('backend/index.html.twig');
    }


    /**
     * @Route("/backend/new-project", name="new_project")
     */
    public function upload(Request $request)
    {
        $projectsDir = $this->getParameter('projectDir');
        $this->makeDir($projectsDir);


        $form = $this->createFormBuilder()
            ->add('title', TextType::class)
            ->add('year', TextType::class)
            ->add('weight', NumberType::class, ['required' => false])
            ->add('body', TextareaType::class, ['required' => false])
            ->add('status', ChoiceType::class, [
                'multiple' => false,
                'expanded' => true,
                'choices' => [
                    'Show' => true,
                    'Hidden' => false,
                ]
            ])
            ->add('headPic', FileType::class)
            ->add('images', FileType::class, [
                'required' => false,
                'label' => 'More images',
                'multiple' => true,
            ])
            ->add('upload', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageManager = new ImageManager();
            $data = $form->getData();
            $weight = $data['weight'];
            if (empty($weight)) {
                $weight = 100000;
            }

            /** @var UploadedFile $file */
            $file = $data['headPic'];
            $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();
//            $file->move($projectsDir, $fileName);
            $imageManager
                ->make($file->getPathname())
                ->resize(600, null,
                    function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                ->save($projectsDir . '/' . $fileName);


            $project = new Project();
            $project
                ->setDate(new \DateTime('now'))
                ->setTitle($data['title'])
                ->setYear($data['year'])
                ->setBody($data['body'])
                ->setPicture($fileName)
                ->setStatus($data['status'])
                ->setWeight($weight);

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();
            $projectId = $project->getId();

            $images = $data['images'];
            if (!empty($images)) {
                $projectDir = $projectsDir . '/' . $projectId;
                $this->makeDir($projectDir);
                $this->makeDir($projectDir . '/' . 'thumb');

                foreach ($images as $image) {
                    /** @var UploadedFile $image */
                    $fileFullName = $this->generateUniqueFileName() . '.' . $image->guessExtension();
//                    $image->move($projectDir, $fileFullName);
                    $imageManager
                        ->make($image->getPathname())
                        ->resize(null, 1024,
                            function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })
                        ->save($projectDir . '/' . $fileFullName);
//                    dd($image);

                    $imageManager
                        ->make($projectDir . '/' . $fileFullName)
                        ->resize(null, 200,
                            function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })
                        ->save($projectDir . '/' . '/thumb/' . $fileFullName);
                }
            }
        }

        return $this->render('backend/new-project.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private
    function makeDir($dir)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            mkdir($dir);
        }
        return realpath($dir) . '/';

    }

    private
    function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

}
