<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use Cake\Core\Configure;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Authentication\AuthenticationService;
use Cake\Routing\Middleware\AssetMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Authentication\AuthenticationServiceInterface;
use Authentication\Identifier\IdentifierInterface;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Authentication\Middleware\AuthenticationMiddleware;
use Authentication\AuthenticationServiceProviderInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{

    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        $this->addPlugin('Authentication');

        if (Configure::read('debug')) {
            $this->addPlugin('DebugKit', ['bootstrap' => true]);
        }

        $this->addPlugin('Migrations');
        $this->addPlugin('AssetCompress', ['bootstrap' => true]);

        $this->addPlugin('Admin', [
            'bootstrap' => false,
            'routes' => true,
            'autoload' => true
        ]);

        $this->addPlugin('Feed', ['bootstrap' => true]);

    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
        // Catch any exceptions in the lower layers,
        // and make an error page/response
        ->add(new ErrorHandlerMiddleware(Configure::read('Error')))

        // Handle plugin/theme assets like CakePHP normally does.
        ->add(new AssetMiddleware([
            'cacheTime' => Configure::read('Asset.cacheTime'),
        ]))

        ->add(new CsrfProtectionMiddleware ())

        // Add routing middleware.
        // If you have a large number of routes connected, turning on routes
        // caching in production could improve performance. For that when
        // creating the middleware instance specify the cache config name by
        // using it's second constructor argument:
        // `new RoutingMiddleware($this, '_cake_routes_')`
        ->add(new RoutingMiddleware($this))
    
        ->add(new AuthenticationMiddleware($this));
    

        return $middlewareQueue;
    }

    /**
     * Bootrapping for CLI application.
     *
     * That is when running commands.
     *
     * @return void
     */
    protected function bootstrapCli(): void
    {
        try {
            $this->addPlugin('Bake');
        } catch (MissingPluginException $e) {
            // Do not halt if the plugin is missing
        }

        $this->addPlugin('Migrations');

    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();

        // Define where users should be redirected to when they are not authenticated
        $service->setConfig([
            'unauthenticatedRedirect' => false,
            'queryParam' => 'redirect',
            'logoutRedirect' => '/',
            'authError' => 'Zugriff verweigert, bitte melde dich an.',
            'authorize' => [
                'Controller',
            ],
            'loginError' => 'Sorry, der Login ist fehlgeschlagen.',
        ]);

        $service->loadIdentifier('Authentication.Password', [
            'fields' => [
                'username' => 'email',
                'password' => 'password',
            ]
        ]);
        
        // Load the authenticators
        $service->loadAuthenticator('Authentication.Session');
        $service->loadAuthenticator('Authentication.Form', [
            'userModel' => 'Users',
            'fields' => [
                'username' => 'email'
            ],
            'finder' => 'auth' // UserTable::findAuth
        ]);

        return $service;
    }    
}