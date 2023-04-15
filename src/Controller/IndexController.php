<?php

namespace App\Controller;

use App\Entity\Links;
use App\Form\FormLinksType;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function new(): Response
    {
        $task = new Links();

        $form = $this->createForm(FormLinksType::class, $task, [
            'action' => $this->generateUrl('create_link'),
            'method' => 'POST',
        ]);
        
        return $this->render("index.html.twig", [
            'form' => $form
        ]);
    }
    #[Route('/link', name: 'create_link')]
    public function createLink(EntityManagerInterface $entityManager, Request $request): Response
    {
        $form = $this->createForm(FormLinksType::class);
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
            return $this->render("createLink.html.twig", [
                'link' => "https://".$request->getHost().":".$request->getPort()."/".$link->getOurUrl(),
                'link_clicks' => "https://".$request->getHost().":".$request->getPort()."/views/".$link->getOurUrl(),
            ]);
        }
        return new Response('Brak danych z formularza');
    }

    #[Route('/views/{ourUrl}', name: 'app_views')]
    public function views($ourUrl, EntityManagerInterface $entityManager): Response
    {
        $repository = $entityManager->getRepository(Links::class);
        $product = $repository->findOneBy(['ourUrl' => $ourUrl]);

        if (!$product) {
            throw $this->createNotFoundException(
                'No data found for this link'
            );
        }else{
            return $this->render("views.html.twig", [
                'clicks' => $product->getClicks(),
            ]);
        }
    }

    #[Route('/{ourUrl}', name: 'app_redirection')]
    public function redirection($ourUrl, EntityManagerInterface $entityManager): Response
    {
        $repository = $entityManager->getRepository(Links::class);
        $link = $repository->findOneBy(['ourUrl' => $ourUrl]);

        if (!$link) {
            throw $this->createNotFoundException(
                'No data found for this link'
            );
        }else{
            $link->setClicks($link->getClicks()+1);
            $entityManager->flush();
            header("Location: ".$link->getSourceUrl());
            exit();
        }
    }
}
