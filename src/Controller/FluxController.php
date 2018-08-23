<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FluxController extends Controller
{
    /**
     * @Route("/flux", name="flux")
     */
    public function index()
    {
        return $this->render('flux/index.html.twig', [
            'controller_name' => 'FluxController',
        ]);
    }
}
