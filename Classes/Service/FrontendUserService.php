<?php
namespace MichielRoos\Doctor\Service;

/**
 * ⓒ 2018 Michiel Roos <michiel@michielroos.com>
 * All rights reserved
 *
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html
 *
 * The TYPO3 project - inspiring people to share!
 */
use MichielRoos\Doctor\Domain\Model\Header;
use MichielRoos\Doctor\Domain\Model\KeyValueHeader;
use MichielRoos\Doctor\Domain\Model\KeyValuePair;
use MichielRoos\Doctor\Domain\Model\ListItem;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Class FrontendUserService
 */
class FrontendUserService extends BaseApiService
{
    /**
     * Get information on a frontend user.
     *
     * @param int $uid Show information about user id
     * @param string $username Show information about user with username
     * @param string $email Show information about user with email
     * @param bool $ignoreEnableFields Ignore enable fields
     * @return array
     */
    public function getInfo($uid = 0, $username = '', $email = '', $ignoreEnableFields = false)
    {
        $this->describe($uid, $username, $email, $ignoreEnableFields);

        return $this->results;
    }

    /**
     * describe
     *
     * @param int $uid
     * @param string $username Show information about user with username
     * @param string $email Show information about user with email
     * @param bool $ignoreEnableFields Ignore enable fields
     */
    public function describe($uid = 0, $username = '', $email = '', $ignoreEnableFields = false)
    {
        $uid = (int)$uid;
        $users = [];

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $frontendUserRepository = $objectManager->get(FrontendUserRepository::class);
        $querySettings = $objectManager->get(QuerySettingsInterface::class);
        $querySettings->setRespectStoragePage(false);
        if ($ignoreEnableFields) {
            $querySettings->setIgnoreEnableFields(true);
        }
        $frontendUserRepository->setDefaultQuerySettings($querySettings);
        if ($uid !== 0) {
            $users = $frontendUserRepository->findByUid($uid);
        } elseif (trim($username)) {
            $users = $frontendUserRepository->findByUsername($username);
        } elseif (trim($email)) {
            $users = $frontendUserRepository->findByEmail($email);
        }

        if ($users instanceof FrontendUser) {
            $users = [$users];
        }

        if (!count($users)) {
            $this->results[] = new Header('Frontend user information');
            $this->results[] = new ListItem('No user found');

            return;
        }

        /** @var FrontendUser $user */
        foreach ($users as $user) {
            $this->results[] = new Header($user->getName() ?: $user->getUserName());
            $this->results[] = new KeyValuePair('Id', $user->getUid());
            $this->results[] = new KeyValuePair('Real name', $user->getName());
            $this->results[] = new KeyValuePair('Username', $user->getUserName());
            $this->results[] = new KeyValuePair('Email', $user->getEmail());
            $this->results[] = new KeyValuePair('Last Login', $user->getLastlogin()->format($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']));
//            $this->results[] = new KeyValuePair('Activated', $user->get() ? 'yes' : 'no');

            $this->results[] = new KeyValueHeader('Groups');
            $groups = $user->getUsergroup();
            $this->drawGroupTree($groups);
        }
    }

    /**
     * @param $groups
     * @param mixed $depth
     */
    private function drawGroupTree($groups, $depth = 0)
    {
        /** @var FrontendUserGroup $group */
        foreach ($groups as $group) {
            $indent = str_repeat('  ', $depth);
            $this->results[] = new KeyValuePair($indent . $group->getTitle(), str_replace([PHP_EOL, "\n"], '', $group->getDescription()));
            $subGroups = $group->getSubgroup();
            if (count($subGroups)) {
                $this->drawGroupTree($subGroups, $depth + 1);
            }
        }
    }
}
