<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\AppTestCase;
use App\Test\TestCase\Traits\LogFileAssertionsTrait;
use App\Test\TestCase\Traits\UserAssertionsTrait;
use Cake\Core\Configure;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use App\Test\TestCase\Traits\LoginTrait;
use App\Model\Entity\User;
use App\Test\TestCase\Traits\QueueTrait;
use Cake\Event\EventInterface;
use Cake\Controller\Controller;
use App\Test\Mock\GeoServiceMock;

class UsersControllerTest extends AppTestCase
{
    use IntegrationTestTrait;
    use UserAssertionsTrait;
    use EmailTrait;
    use LogFileAssertionsTrait;
    use LoginTrait;
    use QueueTrait;

    private array $validUserData = [
        'nick' => 'JohnDoeA<img onerror="alert();" />',
        'firstname' => 'John<img onerror="alert();" />',
        'lastname' => 'DoeA',
        'zip' => '12345',
        'email' => 'johndoeA@mailinator.com',
        'privacy_policy_accepted' => 1,
        'categories' => [
            '_ids' => [87],
        ],
        'skills' => [
            '_ids' => [
                0 => 1,
                1 => 'new skill',
            ],
        ],
    ];

	public function controllerSpy(EventInterface $event, ?Controller $controller = null): void
    {
		parent::controllerSpy($event, $controller);
		$this->_controller->geoService = new GeoServiceMock();
	}

    public function testAll(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUsers());
        $this->doUserPrivacyAssertions();
        $users = $this->viewVariable('users');
        $this->assertEquals(3, count($users));
    }

    public function testPublicProfileFieldsPrivate(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUserProfile(1));
        $this->doUserPrivacyAssertions();
        $this->assertResponseContains('<h1>JohnDoe</h1>');
        $this->assertResponseNotContains('Weitere Kontaktmöglichkeiten');
        $this->assertResponseNotContains('my-additional@email.com');
        $this->assertResponseNotRegExp('`'.preg_quote('[javascript protected email address]</span>').'`');
    }

    public function testPublicProfileFieldsNotPrivate(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUserProfile(3));
        $this->assertResponseNotContains('<h1>MaxMuster</h1>');
        $this->assertResponseContains('<span class="public-name-wrapper">Max Muster</span>');
        $this->assertResponseRegExp('`'.preg_quote('[javascript protected email address]</span>').'`');
        $this->assertResponseContains('<span class="address-wrapper">Test Street 4, 66546</span>');
        $this->assertResponseContains('>Weitere Kontaktmöglichkeiten</b><ul><li>my-additional@email.com</li>');
    }

    public function testProfileEdit(): void
    {
        $this->loginAsOrga();
        $this->post(Configure::read('AppConfig.htmlHelper')->urlUserEdit(1, true), [
            'referer' => '/',
            'Users' => [
                'nick' => 'JohnDoeB',
                'firstname' => 'JohnX',
                'lastname' => 'DoeB',
                'zip' => '12345',
                'email' => 'johndowx@mailinator.com',
            ],
        ]);

        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);
        $this->assertEquals('JohnDoeB', $user->nick);
        $this->assertEquals('JohnX', $user->firstname);
        $this->assertEquals('DoeB', $user->lastname);
        $this->assertEquals('12345', $user->zip);
        $this->assertEquals('johndowx@mailinator.com', $user->email);

    }

    public function testRegisterOrga(): void
    {
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlRegisterOrga(),
            [
                'Users' => $this->validUserData,
            ]
        );
        $this->assertRedirectContains('/');

        $expectedNewUserUid = 12;
        $user = $this->getRegisteredUser();
        $this->assertEquals($user->uid, $expectedNewUserUid);
        $this->assertEquals($user->nick, 'JohnDoeA');
        $this->assertEquals($user->email, $this->validUserData['email']);
        $this->assertEquals($user->firstname, 'John');
        $this->assertEquals(count($user->groups), 1);
        $this->assertEquals($user->password, null);
        $this->assertEquals($user->groups[0]->id, GROUPS_ORGA);
        $this->assertNotEquals($user->groups[0]->id, GROUPS_REPAIRHELPER);
        $this->assertEquals($user->categories[0]->id, 87);
        $this->assertEquals($user->skills[0]->id, 1);

        $skillsTable = $this->getTableLocator()->get('Skills');
        $skills = $skillsTable->find('all')->toArray();
        $this->assertCount(2, $skills);
        $this->assertEquals($expectedNewUserUid, $skills[1]->owner);

        $this->get('/users/activate/' . $user->confirm);

        $user = $this->getRegisteredUser();
        $this->runAndAssertQueue();

        $this->assertMailCount(2);
        $this->assertMailContainsHtmlAt(1, 'Passwort');

        $this->assertEquals($user->confirm, User::STATUS_OK);
        $this->assertNotEquals($user->password, null);
        $this->assertRedirectContains(Configure::read('AppConfig.htmlHelper')->urlUserHome());

    }

    public function testRegisterValidationsEmail(): void
    {
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlRegisterOrga(),
            [
                'Users' => [
                    'email' => 'johndoe@mailinator.com',
                ]
            ]
        );
        $this->assertResponseContains('Diese E-Mail-Adresse wird bereits verwendet.');
        $this->assertNoRedirect();
    }

    public function testRegisterValidationsMxRecord(): void
    {
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlRegisterOrga(),
            [
                'Users' => [
                    'email' => 'johndoe@gadsfadsewcadfaees.com',
                ]
            ]
        );
        $this->assertResponseContains('Bitte trage eine gültige E-Mail-Adresse ein.');
        $this->assertNoRedirect();
    }

    public function testDeleteRepairhelperUserWithNonDeletedWorkshop(): void
    {
        $userUid = 1;
        $usersTable = $this->getTableLocator()->get('Users');
        $workshopsTable = $this->getTableLocator()->get('Workshops');
        $groupsTable = $this->getTableLocator()->get('Groups');
        $user = $usersTable->get($userUid, contain: [
            'Groups',
            'OwnerWorkshops',
        ]);
        // change user to repairhelper
        $user->groups = [
            $groupsTable->get(GROUPS_REPAIRHELPER)
        ];
        $usersTable->save($user);

        // 1. try to delete user with workshop relation
        $usersTable->delete($user);
        $this->assertFalse($user->hasErrors());

        // 3. check changed owner of workshops
        $workshop = $workshopsTable->get($user->owner_workshops[0]->uid);
        $this->assertEquals(8, $workshop->owner);

        // 4. check successfully deleted user
        $user = $usersTable->find('all',
            conditions: [
                'Users.uid' => $userUid,
            ],
        )->first();
        $this->assertEmpty($user);
    }

    public function testDeleteLastOrgaUserWithNonDeletedWorkshop(): void
    {

        $userUid = 1;
        $usersTable = $this->getTableLocator()->get('Users');
        $workshopsTable = $this->getTableLocator()->get('Workshops');
        $user = $usersTable->get($userUid);

        // 1. try to delete user with workshop relation
        $usersTable->delete($user);
        $this->assertArrayHasKey('workshops', $user->getErrors());
        $this->assertEquals('Der User ist bei folgenden Initiativen als letzte(r) Organisator*in zugeordnet: Test Workshop', $user->getErrors()['workshops'][0]);

        // 2. manually remove workshop relation
        $ownerAndAssociatedWorkshopId = 2;
        $this->loginAsAdmin();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlUserWorkshopResign('user', $userUid, $ownerAndAssociatedWorkshopId));

        // 3. successfully delete user
        $user = $usersTable->get($userUid,
            contain: [
                'OwnerWorkshops',
            ]
        );
        $usersTable->delete($user);

        // 4. check changed owner of workshops
        $workshop = $workshopsTable->get($user->owner_workshops[0]->uid);
        $this->assertEquals(8, $workshop->owner);

        $user = $usersTable->find('all',
            conditions: [
                'Users.uid' => $userUid,
            ],
        )->first();
        $this->assertEmpty($user);

    }

    public function testDeleteLastOrgaUserWithDeletedWorkshop(): void
    {

        $userUid = 1;
        $workshopUid = 2;
        $usersTable = $this->getTableLocator()->get('Users');
        $workshopsTable = $this->getTableLocator()->get('Workshops');

        $workshop = $workshopsTable->get($workshopUid);
        $workshop->status = APP_DELETED;
        $workshopsTable->save($workshop);

        $user = $usersTable->get($userUid);

        // delete user with workshop (status deleted) relation
        $usersTable->delete($user);
        $this->assertFalse($user->hasErrors());

        // check if workshop owner of deleted workshops (-1) were set to 0 automatically
        $workshop = $workshopsTable->get($workshopUid);
        $this->assertEquals(0, $workshop->owner);

        // check if all workshop associations the user were removed automatically
        $userWorkshopsTable = $this->getTableLocator()->get('UsersWorkshops');
        $usersWorkshops = $userWorkshopsTable->find('all', conditions: [
            'UsersWorkshops.user_uid' => $userUid,
        ])->toArray();
        $this->assertEmpty($usersWorkshops);

        // approve if user is deleted
        $user = $usersTable->find('all',
            conditions: [
                'Users.uid' => $userUid,
            ],
        )->first();
        $this->assertEmpty($user);

    }

    private function getRegisteredUser(): User
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->find('all',
        conditions: [
            'Users.email' => $this->validUserData['email']
        ],
        contain: [
            'Groups',
            'Categories',
            'Skills',
        ])->first();
        $user->revertPrivatizeData();
        return $user;
    }

}
?>