<?php namespace App\Controllers\Demo;

use App\Kernel\ControllerAbstract;
// use Monolog\Logger;


class HelloController extends ControllerAbstract
{

    /**
     * Index Action
     *
     * @param string $name
     * @return string
     */
    public function index($name)
    {
        $log = $this->getService('logger');
        // $log = new Logger();
        $log->info("helloController logueando....");
        return $this->render('Demo/Hello/index.twig', ['name' => $name]);
    }
}
