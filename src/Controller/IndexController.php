<?php

namespace App\Controller;

use App\Entity\Links;
use App\Form\FormLinksType;
use App\Form\TrackLinkType;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function new(EntityManagerInterface $entityManager, Request $request): Response
    {
        $task = new Links();

        $form = $this->createForm(FormLinksType::class, $task, [
            'action' => $this->generateUrl('app_index'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            $newLink = $form->getData();
            function generateRandomString() {
                $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                $length = 4;
                $string = '';
                $charactersLength = strlen($characters);
                for ($i = 0; $i < $length; $i++) {
                    $string .= $characters[rand(0, $charactersLength - 1)];
                }
                return $string;
            }
            $product=True;
            while($product){
                $ourURL = generateRandomString();
                $repository = $entityManager->getRepository(Links::class);
                $product = $repository->findOneBy(['ourUrl' => $ourURL]);
            }
            $link = new Links();
            $link->setSourceUrl($newLink->getSourceUrl());
            $link->setOurUrl($ourURL);
            $link->setClicks(0);
            $link->setCreationDate(new DateTime());

            // tell Doctrine you want to (eventually) save the link (no queries yet)
            $entityManager->persist($link);

            // actually executes the queries (i.e. the INSERT query)
            $entityManager->flush();

            header("Location: /new-link/$ourURL");
            exit();
        }
        return $this->render("index.html.twig", [
            'form' => $form
        ]);
    }

    #[Route('/test', name: 'test')]
    public function test(): Response
    {
        return $this->render("test.html.twig");
    }

    #[Route('/new-link/{ourUrl}', name: 'new-link')]
    public function createLink($ourUrl, Request $request): Response
    {
        return $this->render("new-link.html.twig", [
            'link' => "https://".$request->getHost().":".$request->getPort()."/".$ourUrl
        ]);
    }

    #[Route('/track-link', name: 'track_link')]
    public function trackLink(EntityManagerInterface $entityManager, Request $request): Response
    {
        $task = new Links();

        $form = $this->createForm(TrackLinkType::class, $task, [
            'action' => $this->generateUrl('track_link'),
            'method' => 'POST',
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            $repository = $entityManager->getRepository(Links::class);
            $newLink = $form->getData();
            $link = $repository->findOneBy(['ourUrl' => substr($newLink->getOurUrl(), -4)]);

            if (!$link) {
                return $this->render("error.html.twig", [
                    'errors'=>["link expired or never existed"]
                ]);
            }else{
                return $this->render("link.html.twig", [
                    'clicks' => $link->getClicks(),
                    'link' => "https://".$request->getHost().":".$request->getPort()."/".$link->getOurUrl(),
                    'form' => $form,
                    'sourceUrl' => $link->getSourceUrl()
                ]);
            }
        }
        return $this->render("link.html.twig", [
            'form' => $form
        ]);
    }

    #[Route('/{ourUrl}', name: 'app_redirection')]
    public function redirection($ourUrl, EntityManagerInterface $entityManager): Response
    {
        $repository = $entityManager->getRepository(Links::class);
        $link = $repository->findOneBy(['ourUrl' => $ourUrl]);

        if (!$link) {
            return $this->render("error.html.twig", [
                'errors'=>["link expired or never existed xd"]
            ]
            );
        }else{
            $link->setClicks($link->getClicks()+1);
            $entityManager->flush();
            header("Location: ".$link->getSourceUrl());
            exit();
        }
    }
}
