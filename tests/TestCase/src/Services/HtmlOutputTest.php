<?php
declare(strict_types=1);

namespace App\Test\TestCase\Services;

use Cake\Core\Configure;
use App\Test\TestCase\AppTestCase;
use App\Test\TestCase\Traits\LoginTrait;
use Cake\TestSuite\IntegrationTestTrait;
use App\Test\TestCase\Traits\LogFileAssertionsTrait;
use App\Test\TestCase\Traits\HtmlOutputAssertionsTrait;
use Cake\I18n\Date;

class HtmlOutputTest extends AppTestCase
{
    use IntegrationTestTrait;
    use HtmlOutputAssertionsTrait;
    use LogFileAssertionsTrait;
    use LoginTrait;

    public function testHome(): void
    {
        $this->get('/');
        $this->doAssertHtmlOutput();
    }

    public function testWorkshopDetail(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlWorkshopDetail('test-workshop'));
        $this->doAssertHtmlOutput();
    }

    public function testPostDetail(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlPostDetail('test-post'));
        $this->doAssertHtmlOutput();
    }

    public function testBlogDetail(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlBlogDetail('neuigkeiten'));
        $this->doAssertHtmlOutput();
    }

    public function testPageDetail(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlPageDetail('test-page'));
        $this->doAssertHtmlOutput();
    }

    public function testUsers(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUsers());
        $this->doAssertHtmlOutput();
    }

    public function testUserProfile(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUserProfile(1));
        $this->doAssertHtmlOutput();
    }

    public function testWorkshops(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlWorkshops());
        $this->doAssertHtmlOutput();
    }

    public function testStatisticsGlobal(): void
    {
        $this->get('/widgets/statisticsGlobal');
        $this->doAssertHtmlOutput();
    }

    public function testSkills(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlSkills());
        $this->doAssertHtmlOutput();
    }

    public function testKnowledges(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlKnowledges());
        $this->doAssertHtmlOutput();
    }

    public function testUserBackendLoggedOut(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUserHome());
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login?redirect=%2Fusers%2Fwelcome');
        $this->assertResponseCode(302);
    }

    public function testUserBackendLoggedIn(): void
    {
        $this->loginAsRepairhelper();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUserHome());
        $this->doAssertHtmlOutput();
    }

    public function testSitemap(): void
    {
        $this->get('/sitemap.xml');
        $this->assertResponseCode(200);
    }

    public function testEventsWithCategoryFilterFound(): void
    {
        $this->changeEventDate();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlEvents() . '?categories=87');
        $this->doAssertHtmlOutput();
        $this->assertResponseContains('<div class="numbers">1 von insgesamt 2 Terminen gefunden</div>');
    }

    public function testEventsWithCategoryFilterNotFound(): void
    {
        $this->changeEventDate();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlEvents() . '?categories=88');
        $this->doAssertHtmlOutput();
        $this->assertResponseContains('<div class="numbers">0 von insgesamt 2 Terminen gefunden</div>');
    }

    public function testAdminPages(): void
    {
        $this->loginAsAdmin();
        $adminPages = [
            '/admin/brands',
            '/admin/brands/edit/1',
            '/admin/categories',
            '/admin/categories/edit/87',
            '/admin/events',
            Configure::read('AppConfig.htmlHelper')->urlEventEdit(6),
            '/admin/info-sheets',
            Configure::read('AppConfig.htmlHelper')->urlInfoSheetEdit(7),
            '/admin/pages',
            '/admin/pages/edit/5',
            '/admin/posts',
            '/admin/posts/edit/4',
            '/admin/skills',
            '/admin/skills/edit/1',
            '/admin/users',
            Configure::read('AppConfig.htmlHelper')->urlUserProfile(1),
            '/admin/worknews',
            '/admin/workshops',
        ];
        foreach($adminPages as $adminPage) {
            $this->get($adminPage);
            $this->assertResponseOk();
            $this->doAssertHtmlOutput();
        }
    }

    private function changeEventDate(): void
    {
        $eventsTable = $this->getTableLocator()->get('Events');
        $event = $eventsTable->get(6);
        $event->datumstart = Date::now()->addDays(20);
        $eventsTable->save($event);
    }

}
?>