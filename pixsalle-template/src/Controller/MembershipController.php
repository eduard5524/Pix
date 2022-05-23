<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Salle\PixSalle\Repository\UserRepository;

final class MembershipController
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

    public function showMembership(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];

            $user = $this->userRepository->getUserByEmail($_SESSION['email']);


            return $this->twig->render($response, 'memberships.twig', 
            [
              'notifications' => $notifications,
              'session'   => $_SESSION,
              'current_membership' => $user->membership,
            ]);
          }else{
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            
            $this->flash->addMessage('notifications', 'You need to login to acces this feature.');

            return $response->withHeader('Location','/sign-in', 
            [
              'notifications' => $notifications,
            ])
            ->withStatus(302);
          }  
    }

    public function membership(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];

            $data = $request->getParsedBody();
            $current_membership = $data['Plan'];
            
            $this->userRepository->updatePlan($data['Plan'], $_SESSION['user_id']);
            
            return $this->twig->render($response, 'memberships.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'current_membership' => $current_membership,
            ]);
        }else{
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];

            $this->flash->addMessage('notifications', 'You need to login to acces this feature.');

            return $response->withHeader('Location','/sign-in', 
            [
              'notifications' => $notifications,
            ])
            ->withStatus(302);
        }
    }
}