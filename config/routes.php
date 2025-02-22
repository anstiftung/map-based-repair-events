<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;


return function (RouteBuilder $routes) {

    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $routes) {

        /* START custom redirects */
        $request = Router::getRequest();
        if (!is_null($request) && preg_match('/' . preg_quote('reparatur-initiativen.de') . '/', $request->domain())) {
            $routes->redirect('/ax', 'http://anstiftung.pageflow.io/reparieren#17589');
            $routes->redirect('/dutz', 'http://anstiftung.pageflow.io/reparieren#17725');
            $routes->redirect('/eisenriegler', 'http://anstiftung.pageflow.io/reparieren#17594');
            $routes->redirect('/heckl', 'http://anstiftung.pageflow.io/reparieren#16303');
            $routes->redirect('/huisken', 'http://anstiftung.pageflow.io/reparieren#17593');
            $routes->redirect('/kreiss', 'http://anstiftung.pageflow.io/reparieren#16309');
            $routes->redirect('/paech', 'http://anstiftung.pageflow.io/reparieren#17725');
            $routes->redirect('/schridde', 'http://anstiftung.pageflow.io/reparieren#17595');
            $routes->redirect('/vangerow', 'http://anstiftung.pageflow.io/reparieren#17590');
            $routes->redirect('/jaeger-erben', 'https://www.transcript-verlag.de/978-3-8376-5698-5/verhaeltnisse-reparieren/?number=978-3-8394-5698-9');
            $routes->redirect('/jung', 'https://www1.wdr.de/fernsehen/die-story/sendungen/reparieren-statt-wegwerfen-100.html');
            $routes->redirect('/opsomer', 'https://repair.eu');
            $routes->redirect('/weber', 'https://www.transcript-verlag.de/978-3-8376-3860-8/kulturen-des-reparierens/?number=978-3-8394-3860-2');
            $routes->redirect('/splitter', '/seite/splitter');
            $routes->redirect('/online-reparaturcafe', '/seite/online-reparaturcafe');
            $routes->redirect('/versicherung', '/seite/sicherheit-haftung#Haftpflichtversicherung');
            $routes->redirect('/klimaherbst', '/seite/klimaherbst');
            $routes->redirect('/foerderung', '/seite/foerderung');
            $routes->redirect('/repair-cafe-ulm', '/reparatur-cafe-ulm');
        }
        $routes->redirect('/initiativen', '/orte');
        $routes->redirect('/seite/reparaturwissen', '/reparaturwissen');
        $routes->redirect('/newsletter', Configure::read('AppConfig.externNewsletterUrl'));
        $routes->redirect('/reparatur-termine', '/termine');
        /* END custom redirects */

        $routes->setExtensions(['html', 'rss', 'xml', 'ics']);

        $routes->connect('/sitemap', ['controller' => 'Sitemaps', 'action' => 'index']);

        $routes->connect('/', ['controller'=>'workshops', 'action'=>'home']);

        $routes->connect('/feed', ['controller' => 'blogs', 'action' => 'feed']); // url for "neuigkeiten"
        $routes->connect('/feed/{blogUrl}', ['controller' => 'blogs', 'action' => 'feed'], ['blogUrl' => Configure::read('AppConfig.htmlHelper')->getAdditionalBlogCategoryUrl()]); // sic! no "neuigkeiten"

        $routes->connect('/initiativen/newsact/*', ['controller'=>'worknews', 'action'=>'worknewsActivate']);
        $routes->connect('/initiativen/newsunsub/*', ['controller'=>'worknews', 'action'=>'worknewsUnsubscribe']);

        $routes->connect('/initiativen/verwalten', ['controller'=>'workshops', 'action'=>'verwalten']);
        $routes->connect('/initiativen/mitmachen', ['controller'=>'workshops', 'action'=>'applyAsUser']);
        $routes->connect('/meine-termine', ['controller'=>'events', 'action'=>'myEvents']);
        $routes->connect('/initiativen/anlegen', ['controller'=>'workshops', 'action'=>'add']);
        $routes->connect('/initiativen/bearbeiten/*', ['controller'=>'workshops', 'action'=>'edit']);
        $routes->connect('/initiativen/loeschen/*', ['controller'=>'workshops', 'action'=>'delete']);

        $routes->connect('/orte/*', ['controller'=>'workshops', 'action'=>'all']);
        $routes->connect('/orte', ['controller'=>'workshops', 'action'=>'all']);

        $routes->connect('/Widgets/integration', ['controller'=>'widgets', 'action'=>'integration']);

        $routes->connect('/initiativen/user/refuse/*', ['controller'=>'workshops', 'action'=>'userRefuse']);
        $routes->connect('/initiativen/user/resign/*', ['controller'=>'workshops', 'action'=>'userResign']);
        $routes->connect('/initiativen/user/approve/*', ['controller'=>'workshops', 'action'=>'userApprove']);

        $routes->connect('/registrierung/reparaturhelferin', ['controller'=>'users', 'action'=>'registerRepairhelper']);
        $routes->connect('/registrierung/organisatorin', ['controller'=>'users', 'action'=>'registerOrga']);
        $routes->connect('/registrierung', ['controller'=>'users', 'action'=>'register']);

        $routes->connect('/users/login', ['controller'=>'users', 'action'=>'login']);
        $routes->connect('/users/logout', ['controller'=>'users', 'action'=>'logout']);
        $routes->connect('/users/welcome', ['controller'=>'users', 'action'=>'welcome']);
        $routes->connect('/users/passwortAendern', ['controller'=>'users', 'action'=>'passwortAendern']);
        $routes->connect('/users/activate/*', ['controller'=>'users', 'action'=>'activate']);

        $routes->connect('/reparaturwissen', ['controller'=>'knowledges', 'action'=>'all']);

        $routes->connect('/users/profile/{id}', ['controller'=>'users', 'action'=>'publicProfile'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $routes->connect('/users/profil/*', ['controller'=>'users', 'action'=>'profil']);
        $routes->connect('/registrierung', ['controller'=>'users', 'action'=>'intro']);

        $routes->connect('/events', ['controller'=>'events', 'action'=>'ical']);
        $routes->connect('/events/{uid}', ['controller'=>'events', 'action'=>'ical'])
            ->setPatterns(['uid' => '\d+'])
            ->setPass(['uid']);
        $routes->connect('/termine/edit/*', ['controller'=>'events', 'action'=>'edit']);
        $routes->connect('/termine/duplicate/*', ['controller'=>'events', 'action'=>'duplicate']);
        $routes->connect('/termine/add/*', ['controller'=>'events', 'action'=>'add']);
        $routes->connect('/termine/delete/*', ['controller'=>'events', 'action'=>'delete']);
        $routes->connect('/termine/*', ['controller'=>'events', 'action'=>'all']);

        $routes->connect('/rss-termine', ['controller' => 'events', 'action' => 'feed']);

        $routes->connect('/aktive', ['controller' => 'users', 'action' => 'all']);
        $routes->connect('/aktive/*', ['controller' => 'users', 'action' => 'all']);
        $routes->connect('/kenntnisse', ['controller' => 'skills', 'action' => 'all']);

        $routes->connect('/laufzettel/add/*', ['controller'=>'infoSheets', 'action'=>'add']);
        $routes->connect('/laufzettel/edit/*', ['controller'=>'infoSheets', 'action'=>'edit']);
        $routes->connect('/laufzettel/delete/*', ['controller'=>'infoSheets', 'action'=>'delete']);
        $routes->connect('/laufzettel/download/*', ['controller'=>'infoSheets', 'action'=>'download']);
        $routes->connect('/laufzettel/full-download/*', ['controller'=>'infoSheets', 'action'=>'fullDownload']);

        $routes->connect('/seite/*', ['controller' => 'pages', 'action' => 'detail']);

        $routes->connect('/post/*', ['controller'=>'posts', 'action'=>'detail']);
        $routes->connect('/{blogUrl}/*', ['controller'=>'blogs', 'action'=>'detail'], ['blogUrl' => 'neuigkeiten|'.Configure::read('AppConfig.htmlHelper')->getAdditionalBlogCategoryUrl()]);

        if (Configure::read('isApiEnabled')) {
            $routes->connect('/api/splitter', ['controller' => 'posts', 'action' => 'getSplitter']);
            $routes->connect('/api/workshops', ['controller' => 'workshops', 'action' => 'getWorkshopsForHyperModeWebsite']);
            $routes->connect('/api/v1/workshops', [
                'controller' => 'workshops',
                'action' => 'getWorkshopsWithCityFilter',
            ])->setMethods(['GET']);
        }

        if (Configure::read('AppConfig.fundingsEnabled')) {
            $routes->connect('/mein-foerderantrag', ['controller'=>'fundings', 'action'=>'index']);
            $routes->connect('/mein-foerderantrag/download/{type}/{uid}', [
                'controller'=>'fundings',
                'action'=>'download'
            ])->setPatterns([
                'type' => 'foerderantrag|foerderbewilligung|verwendungsnachweis',
                'uid' => '[0-9]+'
            ]);
            $routes->connect('/mein-foerderantrag/{action}/{uid}', [
                'controller' => 'fundings',
                'action'=> '{action}'
            ])->setPatterns([
                'action' => 'uploadDetail|uploadZuwendungsbestaetigung|edit|delete|verwendungsnachweis',
                'uid' => '[0-9]+|(' . UUID_REGEX . ')',
            ]);
        }

        // für normale cake routings (users controller)
        $routes->connect('/{controller}/{action}/*');

        // short url for initiativen detail
        $routes->connect('/*', ['controller'=>'workshops', 'action'=>'detail']);

        $routes->fallbacks();
    });

};
