<?php

namespace App\Controller;


use App\Entity\Project;
use App\Gallery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    /**
     * @Route("/projects", name="projects")
     */
    public function projects()
    {
        $projects = $this->getDoctrine()->getRepository(Project::class)->findBy(['status' => true], ['weight' => 'DESC', 'date' => 'ASC']);
        if (!empty($projects)) {
            foreach ($projects as $project) {
                $viewProject[] = [
                    'id' => $project->getId(),
                    'title' => $project->getTitle(),
                    'image' => $project->getPicture(),
                    'year' => $project->getYear(),
                ];
            }
        }
        return $this->render('projects.html.twig', [
            'projects' => $viewProject,
            'projectDir' => $this->getParameter('projectDir'),
        ]);
    }

    /**
     * @Route("/project/{id}", name="projectName")
     */
    public function project1(Request $request, $id)
    {
        $project = $this->getDoctrine()->getRepository(Project::class)->find($id);
        if ($project === null) {
            return $this->redirectToRoute('home');
        }
        $projectDir = $this->getParameter('projectDir');

        $viewProject = [
            'title' => $project->getTitle(),
            'picture' => '/' . $projectDir . '/' . $project->getPicture(),
            'year' => $project->getYear(),
            'body' => str_replace("\r\n", '<br>', $project->getBody()),
            'imgDir' => $projectDir . '/' . $id,
            'images' => (new Gallery($projectDir . '/' . $id))->images,
        ];


        return $this->render('project.html.twig', $viewProject);

    }

}