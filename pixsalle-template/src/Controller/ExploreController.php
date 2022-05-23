<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Salle\PixSalle\Repository\UserRepository;

final class ExploreController
{
    private Twig $twig;
    private Messages $flash;
    private UserRepository $userRepository;

    public function __construct(Twig $twig, Messages $flash, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->flash = $flash;
        $this->userRepository = $userRepository;
    }

    public function explore(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];            
            
            $pictures = $this->userRepository->getPictures();

            return $this->twig->render($response, 'explore.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'pictures' => $pictures,
            ]);
        }else{
            $this->flash->addMessage('notifications', 'You need to login to acces this feature.');
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            return $response->withHeader('Location','/sign-in', 
            [
              'notifications' => $notifications,
            ])
            ->withStatus(302);
        }
    }
}