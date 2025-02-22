<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\View;
use Cake\Core\Configure;
use Cake\View\Helper\HtmlHelper;
use App\Controller\Component\StringComponent;
use App\Model\Entity\Workshop;
use App\Model\Entity\Funding;

class MyHtmlHelper extends HtmlHelper {

    public array $selectedMain = [];
    public array $selectedSub1 = [];
    public array $selectedSub2 = [];
    public array $selectedSub3 = [];

    public bool $selectParentElements = true;


    public function replaceFundingCheckboxPlaceholders(string $html, Funding $funding): string
    {
        $html = str_replace('{RESTBETRAG}', (string) Configure::read('AppConfig.numberHelper')->formatAsDecimal($funding->receiptlist_difference), $html);
        $html = str_replace('{UID}', (string) $funding->uid, $html);
        $html = str_replace('{KONTONUMMER}', 'anstiftung, DE75430609671082980800, GENODEM1GLS', $html);
        return $html;
    }

    public function getHostWithoutProtocol($hostnameWithProtocol): false|string
    {
        $parsedHostnameWithProtocol = (parse_url($hostnameWithProtocol));
        if (!empty($parsedHostnameWithProtocol['host'])) {
            return $parsedHostnameWithProtocol['host'];
        }
        return false;
    }

    public function getWorkshopStatisticTypes(): array
    {
        return [
            Workshop::STATISTICS_DISABLED => 'Keine Statistik im Profil anzeigen',
            Workshop::STATISTICS_SHOW_ALL => 'Gesamte Statistik anzeigen',
            Workshop::STATISTICS_SHOW_ONLY_CHART => 'Nur Zahlenstatistik im Profil anzeigen',
        ];
    }

    public function getMenuTypes(): array
    {
        return [
            'no-menu' => 'In keinem Menü verlinken',
            'header' => 'Header (oben)',
            'footer' => 'Footer (unten)'
        ];
    }

    public function getAdditionalBlogCategoryUrl(): string
    {
        return StringComponent::slugify(Configure::read('AppConfig.additionalBlogCategoryName'));
    }

    public function getHostName(): string
    {
        $serverName = Configure::read('AppConfig.serverName');
        $parsedServerName = parse_url($serverName)['host'];
        $parsedServerName = str_replace('www.', '', $parsedServerName);
        return $parsedServerName;
    }

    function getCarbonFootprintAsString($carbonFootprintSum): string
    {

        $co2AeqPerFlightKilometer = 0.238;
        $carbonFootprintSumInFlightKilometers = $carbonFootprintSum / $co2AeqPerFlightKilometer;

        $unit = 'kg';
        $carbonFootprintSumForView = $carbonFootprintSum;
        if ($carbonFootprintSum >= 1000) {
            $carbonFootprintSumForView /= 1000;
            $unit = 't';
        }

        $distanceToSun  =   149600000;
        $distanceToMars =    55650000;
        $distanceToMoon =      380000;
        $circumferenceOfEarth = 40075;

        $humanUnderstandableComparisonFactor = 0;
        if ($carbonFootprintSumInFlightKilometers >= $circumferenceOfEarth) {
            if ($carbonFootprintSumInFlightKilometers > $distanceToSun) {
                $humanUnderstandableComparisonString = 'der mittleren Entfernung zur Sonne';
                $humanUnderstandableComparisonFactor = $carbonFootprintSumInFlightKilometers / $distanceToSun;
            }
            if ($carbonFootprintSumInFlightKilometers <= $distanceToSun) {
                $humanUnderstandableComparisonString = 'der mittleren Entfernung zum Mars';
                $humanUnderstandableComparisonFactor = $carbonFootprintSumInFlightKilometers / $distanceToMars;
            }
            if ($carbonFootprintSumInFlightKilometers <= $distanceToMars) {
                $humanUnderstandableComparisonString = 'der Entfernung zum Mond';
                $humanUnderstandableComparisonFactor = $carbonFootprintSumInFlightKilometers / $distanceToMoon;
            }
            if ($carbonFootprintSumInFlightKilometers <= 1000000) {
                $humanUnderstandableComparisonString = 'um den Äquator';
                $humanUnderstandableComparisonFactor = $carbonFootprintSumInFlightKilometers / $circumferenceOfEarth;
            }
        }

        $result = '';
        $result .= Configure::read('AppConfig.numberHelper')->precision($carbonFootprintSumForView, 0) . ' ' . $unit;
        $result .= ' CO2 bzw. ';
        $result .= $this->Number->precision($carbonFootprintSumInFlightKilometers, 0);
        $result .= ' km mit dem Flugzeug';

        if (isset($humanUnderstandableComparisonString)) {
            $result .= ', entspricht ca. ' . $this->Number->format(
                $this->roundUpToAny($humanUnderstandableComparisonFactor, 2)
            ) . 'x ' . $humanUnderstandableComparisonString;
        }

        return $result;
    }

    public function getMaterialFootprintAsString($materialFootprintSum): string
    {
        $annualConsumptionPerCapitaInKg = 25800;
        $amountPersonsPerYear = round($materialFootprintSum / $annualConsumptionPerCapitaInKg, 1);

        $unit = 'kg';
        $materialFootprintSumForView = $materialFootprintSum;
        if ($materialFootprintSum >= 1000) {
            $materialFootprintSumForView /= 1000;
            $unit = 't';
        }

        $digits = 0;
        if ($amountPersonsPerYear < 10) {
            $digits = 1;
        }

        $result = '';
        $roundedAmountPersonsPerYear = round($amountPersonsPerYear, $digits);
        $result .= $this->Number->precision($materialFootprintSumForView, 0);
        $result .= ' ' . $unit . '  Boden und Gestein mussten nicht gefördert werden, das entspricht dem Jahresrohstoffverbrauch';
        $result .= ' von ca. <span class="amount-persons-per-year">'.$roundedAmountPersonsPerYear.'</span>';
        $result .= $this->Number->precision($roundedAmountPersonsPerYear, $digits) . ' Personen';
        return $result;
    }

    public function getWorkshopsCountForGlobalStatisticsString($workshopCount): string
    {
        return Configure::read('AppConfig.numberHelper')->precision($workshopCount, 0) . ' ' . 'Initiativen haben mit ihren Reparaturdaten zu dieser Statistik beigetragen.';
    }

    function roundUpToAny($n,$x): float
    {
        return round($n*$x) / $x;
    }

    function getGenders(): array
    {
        return [
            'f' => 'weiblich',
            'm' => 'männlich'
        ];
    }

    function generateGenericRadioButton($form, $formField): string
    {
        $result = '<div class="required form-fields-checkbox-wrapper dependent-form-field '.$formField->identifier.'">'.
            '<label>'.$formField->name.'</label>'.
            $form->control('InfoSheets.' . $formField->identifier, [
                'type' => 'radio',
                'options' => $formField->preparedOptions,
                'label' => false
            ]).
            '</div>';
            return $result;

    }

    function addClassToFormInputContainer($class): string
    {
        return '<div class="'.$class.' input {{type}}">{{content}}</div>';
    }

    function addClassToFormInputContainerError($class): string
    {
        return '<div class="'.$class.' input {{type}}{{required}} error">{{content}}{{error}}</div>';
    }

    function getFacebookHint(): string
    {
        $html = '<strong>Facebook</strong>
         <p>Hier können nur Facebook-Seiten eingetragen werden, keine Personenprofile.
         Dazu ruft ihr eure Facebook-Seite im Browser auf und tragt alles, was <b>nach</b>  http://www.facebook.com/ steht in das Feld ein.
        Beispiel: NICHT https://www.facebook.com/netzwerk-reparatur-initiatven, sondern lediglich netzwerk-reparatur-initiativen eintragen.</p>';
        return $html;
    }

    function getRoleHint($repairhelperInfotext, $orgaInfotext): string
    {
        $html = '<strong>Wichtige Informationen über die verschiedenen Benutzerrollen</strong><br />
                <a class="open-with-featherlight" href="#repairhelperHelp" id="urlHelpLink">Was ist ein(e) <strong>Reparaturhelfer*in</strong> ?</a>
                <div class="hide">
                    <div id="repairhelperHelp" class="help-layer">
                    '.$repairhelperInfotext.'
                    </div>
                </div>
                &nbsp; &#x2016; &nbsp;
                <a class="open-with-featherlight" href="#orgaHelp" id="urlHelpLink">Was ist ein(e) <strong>Organisator*in</strong> ?</a>
                <div class="hide">
                    <div id="orgaHelp" class="help-layer">
                        '.$orgaInfotext.'
                    </div>
                </div>';
        return $html;
    }

    function urlEventDetail($workshopUrl, $eventUid, $eventDatumstart): string
    {
        return $this->urlWorkshopDetail($workshopUrl) .'?event='.$eventUid.','.$eventDatumstart->i18nFormat(Configure::read('DateFormat.Database')).'#datum';
    }


    function urlUsers($categoryName = null, $zip=-1): string
    {
        $url = '/aktive';
        if (!is_null($categoryName)) {
            $url .= '/' . StringComponent::slugify($categoryName);
        }
        if ($zip >= 0) {
            $url .= '?zip=' . $zip;
        }
        return $url;
    }

    function urlUserProfile($userUid): string
    {
        return '/users/profile/'.$userUid;
    }

    function urlUserHome(): string
    {
        return '/users/welcome';
    }

    function urlFundings(): string
    {
        return '/mein-foerderantrag';
    }

    function urlFundingsEdit($workshopUid): string
    {
        return $this->urlFundings() . '/edit/' . $workshopUid;
    }

    function urlFundingsUsageproof($fundingUid): string
    {
        return $this->urlFundings() . '/verwendungsnachweis/' . $fundingUid;
    }

    function urlFundingsUploadZuwendungsbestaetigung($fundingUid): string
    {
        return $this->urlFundings() . '/uploadZuwendungsbestaetigung/' . $fundingUid;
    }

    function urlFundingsAdminEdit($fundingUid): string
    {
        return '/admin/fundings/edit/' . $fundingUid;
    }

    function urlFundinguploadDetail($fundinguploadId): string
    {
        return $this->urlFundings() . '/uploadDetail/' . $fundinguploadId;
    }

    function urlFundingFoerderantragDownload($fundingUid): string
    {
        return $this->urlFundings() . '/download/foerderantrag/' . $fundingUid;
    }

    function urlFundingFoerderbewilligungDownload($fundingUid): string
    {
        return $this->urlFundings() . '/download/foerderbewilligung/' . $fundingUid;
    }

    function urlFundingVerwendungsnachweisDownload($fundingUid): string
    {
        return $this->urlFundings() . '/download/verwendungsnachweis/' . $fundingUid;
    }

    function urlFundingsDelete($fundinguploadId): string
    {
        return $this->urlFundings() . '/delete/' . $fundinguploadId;
    }

    function getUserBackendNaviLinks($userUid, $isMyProfile, $isOrga): array
    {
        $result = [];
        $result[] = ['url' => $this->urlUserHome(), 'name' => 'INFO'];
        $result[] = ['url' => $this->urlUserEdit($userUid, $isMyProfile), 'name' => 'MEIN PROFIL'];
        if ($isOrga) {
            $result[] = ['url' => $this->urlUserWorkshopAdmin(), 'name' => 'MEINE INITIATIVEN'];
        }
        $result[] = ['url' => $this->urlMyEvents(), 'name' => 'MEINE TERMINE'];
        $result[] = ['url' => '/initiativen/mitmachen', 'name' => 'MITMACHEN'];
        if (Configure::read('AppConfig.fundingsEnabled') && $isOrga) {
            $result[] = ['url' => $this->urlFundings(), 'name' => 'FÖRDERANTRAG'];
        }
        return $result;
    }

    function urlMyEvents(): string
    {
        return '/meine-termine';
    }

    /**
     * for google geocoder
     * eg: Mayrhofstr. 4 => Mayrhofstraße 4 / Test Str. => Test Straße
     */
    function replaceAddressAbbreviations(string $string): string
    {
        $string = preg_replace('/(s)tr\./i', '$1traße', $string);
        return $string;
    }

    function getFacebookUrl($username): string
    {
        return 'https://www.facebook.com/' . $username . '/';
    }

    public function trimAndRemoveEmptyTags($html): string
    {
        $pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/";
        $html = preg_replace($pattern, '', $html);
        return trim($html);
    }

    public function __construct(View $View, array $config = [])
    {
        $this->helpers[] = 'Number';
        $this->_defaultConfig['templates']['javascriptblock'] = "{{content}}";
        parent::__construct($View, $config);
    }

    function wrapJavascriptBlock($content): string
    {
        return "<script>
            //<![CDATA[
                $(document).ready(function() {
                    ".$content."
                });
            //]]>
        </script>";
    }

    function urlPageDetail($url, $preview=false): string
    {
        $previewSuffix = '';
        if ($preview == true) {
            $previewSuffix = '/vorschau';
        }
        return '/seite/' . $url . $previewSuffix;
    }

    /**
     * for admin edit
     */
    function isUrlEditable($object): bool
    {
        return $object->status == APP_OFF;
    }

    function urlRegister(): string
    {
        return '/registrierung';
    }

    function urlRegisterRepairhelper(): string
    {
        return '/registrierung/reparaturhelferin';
    }

    function urlRegisterOrga(): string
    {
        return '/registrierung/organisatorin';
    }

    function urlLogin($redirect=''): string
    {
        $url = '/users/login';
        if ($redirect != '') {
            $url .= '?redirect='.$redirect;
        }
        return $url;
    }
    function urlLogout(): string
    {
        return '/users/logout';
    }

    function urlPasswortAendern(): string
    {
        return '/users/passwortAendern';
    }

    function urlUserWorkshopAdmin(): string
    {
        return '/initiativen/verwalten';
    }

    function urlUserWorkshopApplicationUser(): string
    {
        return '/initiativen/mitmachen';
    }

    function urlUserWorkshopApprove($type, $userUid, $workshopUid): string
    {
        return '/initiativen/user/approve/' . $type . '/' . $userUid . '/' . $workshopUid;
    }

    function urlUserWorkshopResign($type, $userUid, $workshopUid): string
    {
        return '/initiativen/user/resign/' . $type . '/' . $userUid . '/' . $workshopUid;
    }

    function urlUserWorkshopRefuse($type, $userUid, $workshopUid): string
    {
        return '/initiativen/user/refuse/' . $type . '/' . $userUid . '/' . $workshopUid;
    }

    function urlNeuesPasswortAnfordern(): string
    {
        return '/users/neuesPasswortAnfordern';
    }

    function urlCategoryNew(): string
    {
        return '/admin/categories/insert/';
    }

    function urlCategoryEdit($id): string
    {
        return '/admin/categories/edit/' . $id;
    }
    function urlOrdsCategoryNew(): string
    {
        return '/admin/ordsCategories/insert/';
    }
    function urlOrdsCategoryEdit($id): string
    {
        return '/admin/ordsCategories/edit/' . $id;
    }
    function urlSkillNew(): string
    {
        return '/admin/skills/insert/';
    }
    function urlSkillEdit($id): string
    {
        return '/admin/skills/edit/' . $id;
    }
    function urlBrandNew(): string
    {
        return '/admin/brands/insert/';
    }
    function urlBrandEdit($id): string
    {
        return '/admin/brands/edit/' . $id;
    }
    function urlEventIcal($uid): string
    {
        return '/events/ical/' . $uid . '.ics';
    }
    function urlEventIcalAll(): string
    {
        return '/events.ics';
    }
    function urlEventDelete($uid): string
    {
        return '/termine/delete/' . $uid;
    }
    function urlEventEdit($uid): string
    {
        return '/termine/edit/' . $uid;
    }
    function urlEventDuplicate($uid): string
    {
        return '/termine/duplicate/' . $uid;
    }
    function urlEventNew($workshopUid = null): string
    {
        return '/termine/add' . (!is_null($workshopUid) ? '/'.$workshopUid : '');
    }
    function urlInfoSheetNew($eventUid): string
    {
        return '/laufzettel/add/' . $eventUid;
    }
    function urlInfoSheetEdit($infoSheetUid): string
    {
        return '/laufzettel/edit/' . $infoSheetUid;
    }
    function urlInfoSheetDelete($infoSheetUid): string
    {
        return '/laufzettel/delete/' . $infoSheetUid;
    }

    function urlFeed(): string
    {
        return '/feed.rss';
    }

    function getThumbs50Image($image, $objectType): string
    {
        return FILES_DIR . 'uploadify/' . $objectType . '/thumbs-50/' . $image;
    }

    function getThumbs100Image($image, $objectType): string
    {
        return FILES_DIR . 'uploadify/' . $objectType . '/thumbs-100/' . $image;
    }

    function getThumbs150Image($image, $objectType): string
    {
        return FILES_DIR . 'uploadify/' . $objectType . '/thumbs-150/' . $image;
    }

    function getThumbs300Image($image, $objectType): string
    {
        return FILES_DIR . 'uploadify/' . $objectType . '/thumbs-300/' . $image;
    }

    function getOriginalImage($image, $objectType): string
    {
        return FILES_DIR . 'uploadify/' . $objectType . '/' . $image;
    }

    function getThumbs280ImageMultiple($image): string
    {
        return FILES_DIR . 'multiple/thumbs-280/' . $image;
    }

    function getThumbs800ImageMultiple($image): string
    {
        return FILES_DIR . 'multiple/thumbs-800/' . $image;
    }
    function urlSkills(): string
    {
        return '/kenntnisse';
    }
    function urlWorkshops($keyword = ''): string
    {
        $url = '/orte';
        if ($keyword != '') {
            $url .= '?keyword=' . $keyword;
        }
        return $url;
    }
    function urlEvents($keyword = ''): string
    {
        $url = '/termine';
        if ($keyword != '') {
            $url .= '?keyword=' . $keyword;
        }
        return $url;
    }
    function urlSkillDetail($id, $name, $zip=-1): string
    {
        $url = '/aktive/' . $id . '-' . StringComponent::slugify($name);
        if ($zip >= 0) {
            $url .= '?zip=' . $zip;
        }
        return $url;
    }
    function urlBlogDetail($url): string
    {
        return '/' . $url;
    }
    function urlWorkshopNew(): string
    {
        return '/initiativen/anlegen';
    }
    function urlWorkshopEdit($uid): string
    {
        return '/initiativen/bearbeiten/'.$uid;
    }
    function urlWorkshopDelete($uid): string
    {
        return '/initiativen/loeschen/'.$uid;
    }

    function urlWorkshopDetail($url): string
    {
        return '/' . $url;
    }
    function urlPageEdit($uid): string
    {
        return '/admin/pages/edit/'.$uid;
    }
    function urlPageNew(): string
    {
        return '/admin/pages/insert';
    }
    function urlPostEdit($uid): string
    {
        return '/admin/posts/edit/'.$uid;
    }
    function urlPostNew($type=''): string
    {
        return '/admin/posts/insert/'.$type;
    }
    function urlKnowledgeNew(): string
    {
        return '/admin/knowledges/insert';
    }
    function urlKnowledgeEdit($uid): string
    {
        return '/admin/knowledges/edit/'.$uid;
    }
    function urlKnowledgeDetail($uid): string
    {
        return $this->urlKnowledges() . '/#' . $uid;
    }
    function urlKnowledges(): string
    {
        return '/reparaturwissen';
    }
    public function getPostTypesWithPreview(): array
    {
        return [
            'neuigkeiten',
            Configure::read('AppConfig.htmlHelper')->getAdditionalBlogCategoryUrl()
        ];
    }
    function urlPostDetail($url, $preview=false): string
    {
        $previewSuffix = '';
        if ($preview == true) {
            $previewSuffix = '/vorschau';
        }
        return '/post/' . $url . $previewSuffix;
    }
    function urlUserEdit($uid, $isMyProfile): string
    {
        $url = '/users/profil';
        if (!$isMyProfile) {
            $url .= '/'.$uid;
        }
        return $url;
    }

    public function getUserProfileImageSrc($user, $userImage): string
    {
        $userImageSrc = '/files/uploadify/users/thumbs-150/' . $userImage;
        if(empty($userImage)) {
            if (!empty($user->categories)) {
                $categoryIdForUserProfileImage = $user->categories[rand(0, count($user->categories) - 1)]->id;
            } else {
                $path = WWW_ROOT . '/img/user-profile';
                $dir = new \DirectoryIterator($path);
                $files = [];
                foreach ($dir as $fileinfo) {
                    if ($fileinfo->isFile() && $fileinfo->getExtension() == 'png') {
                        $files[] = $fileinfo->getFilename();
                    }
                }
                $categoryIdForUserProfileImage = preg_replace('/[^0-9]/', '', $files[rand(0, count($files) - 1)]);
            }
            $userImageSrc = '/img/user-profile/user-profile-image-'.$categoryIdForUserProfileImage.'.png';
        }
        return $userImageSrc;
    }

    function getUserProfileImage($user): string
    {
        $userAltText = isset($user->image_alt_text) ? $user->image_alt_text : $user['image_alt_text'];
        $userImage = isset($user->image) ? $user->image : $user['image'];
        $userImageSrc = $this->getUserProfileImageSrc($user, $userImage);
        $imageHtml = '<img alt="'.$userAltText.'"  class="rounded" src="'.$userImageSrc.'" >';
        return $imageHtml;
    }

    function urlUserNew(): string
    {
        return '/users/add';
    }

    function getUserGroupsForRegistration(): array
    {
        $userGroups = [
            GROUPS_ORGA         => 'Organisator*in',
            GROUPS_REPAIRHELPER => Configure::read('AppConfig.repairHelperName'),
        ];
        return $userGroups;
    }

    function getUserGroupsForWorkshopDetail(): array
    {
        $userGroups = [
            GROUPS_ADMIN        => 'Admin',
            GROUPS_ORGA         => 'Organisator*in',
            GROUPS_REPAIRHELPER => Configure::read('AppConfig.repairHelperName'),
        ];
        return $userGroups;
    }

    function getUserGroups(): array
    {
        $userGroups = [
            GROUPS_ADMIN       => 'Admin'
        ];
        return $userGroups;
    }

    function getUserGroupsForUserEdit($isAdmin = false): array
    {
        $userGroups = [];
        if ($isAdmin) {
            $userGroups[GROUPS_ADMIN] = 'Admin';
        }
        $userGroups[GROUPS_ORGA] = 'Organisator*in';
        $userGroups[GROUPS_REPAIRHELPER] = Configure::read('AppConfig.repairHelperName');
        return $userGroups;
    }

    function formatBytes($bytes, $precision = 2) :string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return number_format($bytes, $precision, ',', '.') . ' ' . $units[$pow];
    }

    function getPhotoDimensions($dimension): array|false
    {
        if (!preg_match('/x/', $dimension)) return false;
        return explode('x', $dimension);
    }

    /**
     * creates navigation with up to 2 sublevels
     */
    function createMenuEntry($menuElement, $order = null, $mainMenuElement = null): string
    {

        $element = '';

        if ($menuElement['level'] == 'main') {
            $element .= '';
        }

        $class = '';
        $element .= '<li ' . $class . '>';

        $htmlAttributes = [];
        if (isset($menuElement['htmlAttributes'])) {
            $htmlAttributes = array_merge($htmlAttributes, $menuElement['htmlAttributes']);
        }
        if ('/' . $this->here == $menuElement['url']) {

            if (isset($htmlAttributes['class'])) {
                // $htmlAttributes['class'] .= ' selected';
            } else {
                // $htmlAttributes['class'] = ' selected';
            }

            if ($menuElement['level'] == 'main') {
                $this->selectedMain = $menuElement;
            }

            if ($menuElement['level'] == 'sub1') {
                $this->selectedMain = $mainMenuElement;
                $this->selectedSub1 = $menuElement;
            }
        }

        if (isset($menuElement['sub'])) {

            foreach ($menuElement['sub'] as $sub1MenuElement) {

                if ('/' . $this->here == $sub1MenuElement['url']) {

                    if ($this->selectParentElements && in_array($menuElement['level'], ['main', 'sub'])) {
                        // $htmlAttributes['class'] = 'selected';
                    }

                    if ($menuElement['level'] == 'sub1') {
                        $this->selectedMain = $mainMenuElement;
                        $this->selectedSub1 = $menuElement;
                        $this->selectedSub2 = $sub1MenuElement;
                    }

                    continue;
                }

                if (isset($sub1MenuElement['sub'])) {
                    foreach ($sub1MenuElement['sub'] as $sub2MenuElement) {
                        if ('/' . $this->here == $sub2MenuElement['url']) {
                            // $htmlAttributes['class'] = 'selected';
                            continue;
                        }
                    }
                }
            }
        }

        $element .= self::link($menuElement['name'], $menuElement['url'], $htmlAttributes);

        if (isset($menuElement['sub'])) {
            $i = 0;
            $element .= '<ul class="submenu">';
            foreach ($menuElement['sub'] as $subMenuElement) {
                $element .= $this->createMenuEntry($subMenuElement, $i, $menuElement);
                $i++;
            }
            $element .= '</ul>';
        }

        $element .= '</li>';

        if ($menuElement['level'] == 'main') {
            $element .= '';
        }

        return $element;

    }

}