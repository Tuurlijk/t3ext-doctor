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
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class BackendUserService
 */
class BackendUserService extends BaseApiService
{
    /**
     * Get information on a backend user.
     *
     * @param int $uid Show information about user id
     * @param string $username Show information about user with username
     * @param string $email Show information about user with email
     * @return array
     */
    public function getInfo($uid = 0, $username = '', $email = '')
    {
        $this->describe($uid, $username, $email);

        return $this->results;
    }

    /**
     * describe
     *
     * @param int $uid
     * @param string $username Show information about user with username
     * @param string $email Show information about user with email
     */
    public function describe($uid = 0, $username = '', $email = '')
    {
        $uid = (int)$uid;
        $users = [];

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $backendUserRepository = $objectManager->get(BackendUserRepository::class);

        if ($uid !== 0) {
            $users = $backendUserRepository->findByUidList([$uid]);
        } elseif (trim($username)) {
            $users = $backendUserRepository->findByUsername($username);
        } elseif (trim($email)) {
            $users = $backendUserRepository->findByEmail($email);
        }

        if (!count($users)) {
            $this->results[] = new Header('Backend user information');
            $this->results[] = new ListItem('No user found');
            return;
        }

        /** @var BackendUser $user */
        foreach ($users as $user) {
            $this->results[] = new Header($user->getRealName() ?: $user->getUserName());
            $this->results[] = new KeyValuePair('Id', $user->getUid());
            $this->results[] = new KeyValuePair('Real name', $user->getRealName());
            $this->results[] = new KeyValuePair('Username', $user->getUserName());
            if (trim($user->getDescription())) {
                $this->results[] = new KeyValuePair('Description', $user->getDescription());
            }
            if (trim($user->getAllowedLanguages())) {
                $this->results[] = new KeyValuePair('Language', $user->getAllowedLanguages());
            }
            $this->results[] = new KeyValuePair('Email', $user->getEmail());
            $this->results[] = new KeyValuePair('Activated', $user->isActivated() ? 'yes' : 'no');
            $this->results[] = new KeyValuePair('Admin', $user->getIsAdministrator() ? 'yes' : 'no');

            $this->results[] = new KeyValueHeader('Groups');
            $groups = $user->getBackendUserGroups();
            $this->drawGroupTree($groups);

            $this->results[] = new KeyValueHeader('Tables view');
            $tables = $this->getEffectiveTablesView($groups);
            $tables = array_unique($tables);
            natsort($tables);
            foreach ($tables as $table) {
                if (!trim($table)) {
                    continue;
                }
                $this->results[] = new ListItem($table);
            }

            $this->results[] = new KeyValueHeader('Tables modify');
            $tables = $this->getEffectiveTablesModify($groups);
            $tables = array_unique($tables);
            natsort($tables);
            foreach ($tables as $table) {
                if (!trim($table)) {
                    continue;
                }
                $this->results[] = new ListItem($table);
            }

        }
    }

    /**
     * @param $groups
     */
    private function drawGroupTree($groups, $depth = 0)
    {
        /** @var BackendUserGroup $group */
        foreach ($groups as $group) {
            $indent = str_repeat('  ', $depth);
            $this->results[] = new KeyValuePair($indent . $group->getTitle(), $group->getDescription());
            $subGroups = $group->getSubGroups();
            if (count($subGroups)) {
                $this->drawGroupTree($subGroups, $depth + 1);
            }
        }
    }

    /**
     * @param $groups
     * @return array
     */
    private function getEffectiveTablesView($groups)
    {
        $tables = [];
        /** @var BackendUserGroup $group */
        foreach ($groups as $group) {
            $tables = array_merge($tables, $this->getTablesViewForGroup($group));
            $subGroups = $group->getSubGroups();
            if (count($subGroups)) {
                $tables = array_merge($tables, $this->getEffectiveTablesView($subGroups));
            }
        }
        return $tables;
    }

    /**
     * @param $group
     * @return array
     */
    private function getTablesViewForGroup($group)
    {
        $tables = '';
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(sprintf(
            'SELECT tables_select FROM be_groups WHERE uid = %s',
            $group->getUid()
        ));

        if ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $tables = $row['tables_select'];
        }
        return explode(',', $tables);
    }

    /**
     * @param $groups
     * @return array
     */
    private function getEffectiveTablesModify($groups)
    {
        $tables = [];
        /** @var BackendUserGroup $group */
        foreach ($groups as $group) {
            $tables = array_merge($tables, $this->getTablesModifyForGroup($group));
            $subGroups = $group->getSubGroups();
            if (count($subGroups)) {
                $tables = array_merge($tables, $this->getEffectiveTablesModify($subGroups));
            }
        }
        return $tables;
    }

    /**
     * @param $group
     * @return array
     */
    private function getTablesModifyForGroup($group)
    {
        $tables = '';
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(sprintf(
            'SELECT tables_modify FROM be_groups WHERE uid = %s',
            $group->getUid()
        ));

        if ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $tables = $row['tables_modify'];
        }
        return explode(',', $tables);
    }
}
