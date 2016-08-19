<?php

namespace Fixture\BundlerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FixtureBuilderBundle:Default:index.html.twig');
    }
}
