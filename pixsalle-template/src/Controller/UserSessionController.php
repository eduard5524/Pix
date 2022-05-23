<?php
declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Salle\PixSalle\Service\ValidatorService;
use Salle\PixSalle\Repository\UserRepository;
use Salle\PixSalle\Model\User;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Slim\Psr7;
use Slim\Flash\Messages;

class UserSessionController
{
    private Twig $twig;
    private ValidatorService $validator;
    private UserRepository $userRepository;
    private Messages $flash;

    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash) {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->validator = new ValidatorService();
        $this->flash = $flash;
    }

    public function showSignInForm(Request $request, Response $response): Response {
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];

        return $this->twig->render($response, 'sign-in.twig', 
        [
            'notifications' => $notifications,
        ]);
    }


    public function signIn(Request $request, Response $response): Response
    {
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];

        $data = $request->getParsedBody();
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $errors = [];

        $errors['email'] = $this->validator->validateEmail($data['email']);
        $errors['password'] = $this->validator->validatePassword($data['password']);

        if ($errors['email'] == '') {
            unset($errors['email']);
        }
        if ($errors['password'] == '') {
            unset($errors['password']);
        }
        if (count($errors) == 0) {
            // Check if the credentials match the user information saved in the database
            $user = $this->userRepository->getUserByEmail($data['email']);
            if ($user == null) {
                $errors['email'] = 'User with this email address does not exist.';
            } else if ($user->password != md5($data['password'])) {
                $errors['password'] = 'Your email and/or password are incorrect.';
            } else {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['email'] = $user->email;
                $_SESSION['username'] = $user->username;
                $_SESSION['phone'] = $user->phone;
                
                return $response->withHeader('Location','/', 
                [
                    'notifications' => $notifications,
                ])->withStatus(302);
            }
        }
        return $this->twig->render($response, 'sign-in.twig',
            [
                'formErrors' => $errors,
                'formData' => $data,
                'formAction' => $routeParser->urlFor('signIn'),
                'notifications' => $notifications,
            ]
        );
    }

    public function updateSession(){
        $user = $this->userRepository->getUserByEmail($_SESSION['email']);
        $_SESSION['username'] = $user->username;
        $_SESSION['phone'] = $user->phone;
    }
}