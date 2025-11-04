<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BrandStoryController extends AbstractController
{
    #[Route('/brandstory', name: 'app_brandstory_index')]
        public function index(): Response
    {
        return $this->render('brandstory/index.html.twig');
    }
}


