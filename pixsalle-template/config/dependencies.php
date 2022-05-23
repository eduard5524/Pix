<?php

declare(strict_types=1);

use DI\Container;
use Psr\Container\ContainerInterface;
use Salle\PixSalle\Controller\SignUpController;
use Salle\PixSalle\Controller\UserSessionController;
use Salle\PixSalle\Repository\MySQLUserRepository;
use Salle\PixSalle\Repository\PDOConnectionBuilder;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use Salle\PixSalle\Middleware\StartSessionMiddleware;
use Salle\PixSalle\Controller\HomeController;
use Salle\PixSalle\Controller\FlashController;
use Salle\PixSalle\Controller\UserProfileController;
use Salle\PixSalle\Controller\WalletController;
use Salle\PixSalle\Controller\MembershipController;
use Salle\PixSalle\Controller\ExploreController;
use Salle\PixSalle\Controller\PortfolioController;
use Salle\PixSalle\Controller\BlogController;


function addDependencies(ContainerInterface $container): void
{
    $container->set('view', function () {
            return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
        }
    );
    $container->set('db', function () {
        $connectionBuilder = new PDOConnectionBuilder();
        return $connectionBuilder->build(
            $_ENV['MYSQL_ROOT_USER'],
            $_ENV['MYSQL_ROOT_PASSWORD'],
            $_ENV['MYSQL_HOST'],
            $_ENV['MYSQL_PORT'],
            $_ENV['MYSQL_DATABASE']
        );
    });
    $container->set('user_repository', function (ContainerInterface $container) {
        return new MySQLUserRepository($container->get('db'));
    });
    $container->set('flash', function () {
            return new Messages();
        }
    );
    $container->set(
        HomeController::class, 
        function (ContainerInterface $c) {
            $controller = new HomeController($c->get("view"), $c->get("flash"));
            return $controller;
        }
    );
    $container->set(
        StartSessionMiddleware::class, 
        function (ContainerInterface $c) {
            return new StartSessionMiddleware([
                'name' => 'dummy_session',
                'autorefresh' => true,
                'lifetime' => '1 hour',
              ]);
        }
    );
    $container->set(
        UserSessionController::class, 
        function (ContainerInterface $c) {
            return new UserSessionController($c->get('view'), $c->get('user_repository'), $c->get('flash'));
        }
    );
    $container->set(
        StartSessionController::class, 
        function (ContainerInterface $c) {
            return new StartSessionController($c->get('view'), $c->get('user_repository'));
        }
    );
    $container->set(
        SignUpController::class, 
        function (ContainerInterface $c) {
            return new SignUpController($c->get('view'), $c->get('user_repository'));
        }
    );
    $container->set(
        UserProfileController::class, 
        function (ContainerInterface $c) {
            return new UserProfileController($c->get('view'), $c->get("flash"), $c->get('user_repository'));
        }
    );
    $container->set(
        WalletController::class, 
        function (ContainerInterface $c) {
            return new WalletController($c->get('view'), $c->get('flash'), $c->get('user_repository'));
        }
    );
    $container->set(
        MembershipController::class,
        function (ContainerInterface $c) {
            return new MembershipController($c->get('view'), $c->get('flash'), $c->get('user_repository'));
        }
    );
    $container->set(
        ExploreController::class,
        function (ContainerInterface $c) {
            return new ExploreController($c->get('view'), $c->get('flash'), $c->get('user_repository'));
        }
    );
    $container->set(
        PortfolioController::class,
        function (ContainerInterface $c) {
            return new PortfolioController($c->get('view'), $c->get('flash'), $c->get('user_repository'));
        }
    );
    $container->set(
        BlogController::class,
        function (ContainerInterface $c) {
            return new BlogController($c->get('view'), $c->get('flash'), $c->get('user_repository'));
        }
    );
}