<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class FlashController
{

    public function __construct(private Twig $twig, private Messages $flash)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function addMessage(Request $request, Response $response): Response
    {
        $this->flash->addMessage('notifications','Flash messages in action!');

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $response->withHeader('Location', $routeParser->urlFor("home"))->withStatus(302);
    }
}

$app->addDefinitions(
    [
        'flash' => function () {
            $storage = [];
            return new Messages($storage);
        }
    ]
);