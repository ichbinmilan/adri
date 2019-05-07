<?php

namespace App\Controller;


use App\Entity\Project;
use App\Gallery;
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
    public function backend()
    {
        $projects = $this->getDoctrine()->getRepository(Project::class)->findBy([], ['weight' => 'DESC', 'date' => 'DESC']);
        $viewProject = null;

        if (!empty($projects)) {
            foreach ($projects as $project) {
                $status = '';
                if (!$project->getStatus()) {
                    $status = 'hidden-class';
                }
                $viewProject[] = [
                    'id' => $project->getId(),
                    'title' => $project->getTitle(),
                    'image' => $project->getPicture(),
                    'year' => $project->getYear(),
                    'status' => $status,
                ];
            }
        }

        return $this->render('backend/index.html.twig', [
            'projects' => $viewProject,
            'projectDir' => $this->getParameter('projectDir'),
        ]);
    }


    /**
     * @Route("/backend/project/delete/{projectId}", name="project_delete")
     */
    public function delete(Request $request, $projectId)
    {
        $projectDir = $this->getParameter('projectDir');

        if (!empty($projectId)) {
            $project = $this->getDoctrine()->getRepository(Project::class)->find($projectId);
            if (!empty($project)) {

                $form = $this->createFormBuilder()
//                    ->add('id', HiddenType::class, ['data' => $projectId])
                    ->add('delete', SubmitType::class, ['attr' => ['class' => 'btn btn-danger']])
                    ->getForm();

                $viewProject = [
                    'title' => $project->getTitle(),
                    'picture' => '/' . $projectDir . '/' . $project->getPicture(),
                    'year' => $project->getYear(),
                    'body' => $project->getBody(),
                    'imgDir' => $projectDir . '/' . $projectId,
                    'images' => (new Gallery($projectDir . '/' . $projectId))->images,
                    'deleteForm' => $form->createView(),
                ];

                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
//                    $id = $form->getData()['id'];
                    $em = $this->getDoctrine()->getManager();
                    $em->remove($project);
                    $em->flush();

                    unlink($projectDir . DIRECTORY_SEPARATOR . $project->getPicture());
                    $dir = $projectDir . DIRECTORY_SEPARATOR . $projectId;
                    $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
                    $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($files as $file) {
                        if ($file->isDir()) {
                            rmdir($file->getRealPath());
                        } else {
                            unlink($file->getRealPath());
                        }
                    }
                    rmdir($dir);

                    $this->addFlash('success', 'Пожънахме успех с изтриването!');

                    return $this->redirectToRoute('backend');


                }
                return $this->render('project.html.twig', $viewProject);


            }
        }
    }


    /**
     * @Route("/backend/project/{projectId}", defaults={"projectId" = null}, name="project_upload")
     */
    public function upload(Request $request, $projectId)
    {
        $projectsDir = $this->getParameter('projectDir');
        $this->makeDir($projectsDir);
        $projectForm = null;

        if (!empty($projectId)) {
            $project = $this->getDoctrine()->getRepository(Project::class)->find($projectId);
            if (!empty($project)) {
                $projectForm = [
                    'title' => $project->getTitle(),
                    'year' => $project->getYear(),
                    'weight' => $project->getWeight(),
                    'body' => $project->getBody(),
                    'status' => $project->getStatus(),
                ];
            }
        } else {
            $project = new Project();
        }

        $form = $this->createFormBuilder($projectForm)
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
            ->add('headPic', FileType::class, ['required' => false,])
            ->add('images', FileType::class, [
                'required' => false,
                'label' => 'More images',
                'multiple' => true,
            ])
            ->add('upload', SubmitType::class)
            ->getForm();

        /* Handle Form */
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageManager = new ImageManager();
            $data = $form->getData();
            if (empty($project->getDate())) {
                $project->setDate(new \DateTime('now'));
            }

            $project
                ->setTitle($data['title'])
                ->setYear($data['year'])
                ->setBody($data['body'])
                ->setStatus($data['status'])
                ->setWeight($data['weight']);

            /** @var UploadedFile $file */
            $file = $data['headPic'];
            if (!empty($file)) {
                $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();
                $imageManager
                    ->make($file->getPathname())
                    ->resize(600, null,
                        function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })
                    ->save($projectsDir . '/' . $fileName);


                $project->setPicture($fileName);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();
            $projectId = $project->getId();

            $images = $data['images'];
            if (!empty($images)) {
                $projectDir = $projectsDir . '/' . $projectId;
                $dir = $this->makeDir($projectDir);
                $this->removeAll($dir);
                $dir = $this->makeDir($projectDir . '/' . 'thumb');
                $this->removeAll($dir);


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
            $this->addFlash('success', 'Честит новокачен проект! Продължавай в същия дух!');
            return $this->redirectToRoute('backend');
        }

        return $this->render('backend/new-project.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function makeDir($dir)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            mkdir($dir);
        }
        return realpath($dir) . '/';
    }

    private function removeAll($dir)
    {
        if (file_exists($dir)) {
            $files = glob($dir);
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private
    function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

}
