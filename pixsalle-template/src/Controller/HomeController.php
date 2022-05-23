<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;

final class HomeController
{
    private Twig $twig;
    private Messages $flash;

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    public function __construct(Twig $twig, Messages $flash)
    {
        $this->twig = $twig;
        $this->flash = $flash;
    }

    public function home(Request $request, Response $response)
    {
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];

        if(isset($_SESSION['user_id'])){
            $session_user_id = $_SESSION['user_id'];

            return $this->twig->render($response, 'home.twig',
            [
                'notifications' => $notifications,
                'session'   => $_SESSION,
            ],);
        }
        
        return $this->twig->render($response, 'home.twig',
        [
            'notifications' => $notifications,
            'session' => $_SESSION,
        ],);       
    }

    public function showVisits(Request $request, Response $response): Response
    {
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];

        if (empty($_SESSION['counter'])) {
            $_SESSION['counter'] = 1;
        } else {
            $_SESSION['counter']++;
        }

        return $this->twig->render($response, 'visits.twig',
            [
                'session' => $_SESSION,
                'visits' => $_SESSION['counter'],
                'notifications' => $notifications,
            ]
        );
    }
}