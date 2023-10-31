<?php

namespace MichielRoos\Doctor\Command;

use MichielRoos\Doctor\Service\CacheApiService;
use MichielRoos\Doctor\Service\ContentApiService;
use MichielRoos\Doctor\Service\DatabaseApiService;
use MichielRoos\Doctor\Service\FrontendUserService;
use MichielRoos\Doctor\Service\OverridesApiService;
use MichielRoos\Doctor\Service\SiteApiService;
use MichielRoos\Doctor\Service\TyposcriptApiService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class DoctorCommandController
 */
class BackendUserCommand extends BaseCommandController
{
    /**
     * @var \MichielRoos\Doctor\Service\BackendUserService
     */
    protected $backendUserService;

    /**
     * @var \MichielRoos\Doctor\Service\CacheApiService
     */
    protected $cacheApiService;

    /**
     * @var \MichielRoos\Doctor\Service\ContentApiService
     */
    protected $contentApiService;

    /**
     * @var \MichielRoos\Doctor\Service\DatabaseApiService
     */
    protected $databaseApiService;

    /**
     * @var \MichielRoos\Doctor\Service\FrontendUserService
     */
    protected $frontendUserService;

    /**
     * @var \MichielRoos\Doctor\Service\TyposcriptApiService
     */
    protected $typoscriptApiService;

    /**
     * @var \MichielRoos\Doctor\Service\SiteApiService
     */
    protected $siteApiService;

    /**
     * @var \MichielRoos\Doctor\Service\OverridesApiService
     */
    protected $overridesApiService;

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Backend user information')
            ->addOption(
                'uid',
                '',
                InputOption::VALUE_OPTIONAL,
                'The id of the backend user'
            )->addOption(
                'username',
                '',
                InputOption::VALUE_OPTIONAL,
                'The username of the backend user'
            )->addOption(
                'email',
                '',
                InputOption::VALUE_OPTIONAL,
                'The email of the backend user'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io            = new SymfonyStyle($input, $output);
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $uid           = $input->getOption('uid');
        $username      = $input->getOption('username');
        $email         = $input->getOption('email');

        $users = [];

        $backendUserRepository = $objectManager->get(BackendUserRepository::class);

        if ($uid !== 0) {
            $users = $backendUserRepository->findByUidList([$uid]);
        } elseif (trim($username)) {
            $users = $backendUserRepository->findByUsername($username);
        } elseif (trim($email)) {
            $users = $backendUserRepository->findByEmail($email);
        }


        if (!count($users)) {
            $io->title('Backend user information');
            $io->error('No user found');
            return 1;
        }

        /** @var BackendUser $user */
        foreach ($users as $user) {
            $io->title($user->getRealName() ?: $user->getUserName());

            $info = [
                'id'        => $user->getUid(),
                'real name' => $user->getRealName(),
                'username'  => $user->getUserName(),
            ];
            if (trim($user->getDescription())) {
                $info['description'] = $user->getDescription();
            }
            if (trim($user->getAllowedLanguages())) {
                $info['language'] = $user->getAllowedLanguages();
            }
            $info['email']            = $user->getEmail();
            $info['activated']        = $user->isActivated() ? 'yes' : 'no';
            $info['is administrator'] = $user->getIsAdministrator() ? 'yes' : 'no';

            $io->horizontalTable(array_keys($info), [$info]);

            $groups = $user->getBackendUserGroups();
            $tree   = $this->drawGroupTree($groups);
            $io->table(['groups'], $tree);

            $tablesView = [];
            $tables     = $this->getEffectiveTablesView($groups);
            $tables     = array_unique($tables);
            natsort($tables);
            foreach ($tables as $table) {
                if (!trim($table)) {
                    continue;
                }
                $tablesView[] = [$table];
            }
            $io->table(['tables view'], $tablesView);

            $tablesModify = [];
            $tables       = $this->getEffectiveTablesModify($groups);
            $tables       = array_unique($tables);
            natsort($tables);
            foreach ($tables as $table) {
                if (!trim($table)) {
                    continue;
                }
                $tablesModify[] = [$table];
            }
            $io->table(['tables modify'], $tablesModify);
        }

        return 0;
    }

    /**
     * @param $groups
     * @param mixed $depth
     * @param array $results
     * @return array
     */
    private function drawGroupTree($groups, $depth = 0, array $results = []): array
    {
        /** @var BackendUserGroup $group */
        foreach ($groups as $group) {
            $indent    = str_repeat('  ', $depth);
            $results[] = [$indent . $group->getTitle() . ($group->getDescription() ? ' - ' . $group->getDescription() : '')];
            $subGroups = $group->getSubGroups();
            if (count($subGroups)) {
                $results = $this->drawGroupTree($subGroups, $depth + 1, $results);
            }
        }
        return $results;
    }

    /**
     * @param $groups
     * @return array
     */
    private function getEffectiveTablesView($groups): array
    {
        $tables = [];
        /** @var BackendUserGroup $group */
        foreach ($groups as $group) {
            $tables    = array_merge($tables, $this->getTablesViewForGroup($group));
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
    private function getTablesViewForGroup($group): array
    {
        $tables          = '';
        $databaseHandler = $this->getDatabaseHandler();
        $result          = $databaseHandler->sql_query(sprintf(
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
    private function getEffectiveTablesModify($groups): array
    {
        $tables = [];
        /** @var BackendUserGroup $group */
        foreach ($groups as $group) {
            $tables    = array_merge($tables, $this->getTablesModifyForGroup($group));
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
    private function getTablesModifyForGroup($group): array
    {
        $tables          = '';
        $databaseHandler = $this->getDatabaseHandler();
        $result          = $databaseHandler->sql_query(sprintf(
            'SELECT tables_modify FROM be_groups WHERE uid = %s',
            $group->getUid()
        ));

        if ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $tables = $row['tables_modify'];
        }

        return explode(',', $tables);
    }

    /**
     * Frontend user information
     *
     * @param int $uid Show information about user with id
     * @param string $username Show information about user with username
     * @param string $email Show information about user with email
     * @param bool $ignoreEnableFields Ignore enable fields
     */
    public function frontendUserCommand($uid = 0, $username = '', $email = '', $ignoreEnableFields = false)
    {
        $this->frontendUserService = $this->objectManager->get(FrontendUserService::class);
        $results                   = $this->frontendUserService->getInfo($uid, $username, $email, $this->textToBool($ignoreEnableFields));
        $this->writeResults($results);
    }

    /**
     * Cache information
     */
    public function cacheCommand()
    {
        $this->cacheApiService = $this->objectManager->get(CacheApiService::class);
        $results               = $this->cacheApiService->getInfo();
        $this->writeResults($results);
    }

    /**
     * Overrides information
     */
    public function overridesCommand()
    {
        $this->overridesApiService = $this->objectManager->get(OverridesApiService::class);
        $results                   = $this->overridesApiService->getInfo();
        $this->writeResults($results);
    }

    /**
     * System information
     */
    public function siteCommand()
    {
        $this->siteApiService = $this->objectManager->get(SiteApiService::class);
        $results              = $this->siteApiService->getInfo();
        $this->writeResults($results);
    }

    /**
     * Typoscript information
     * @param string $key Typoscript object key used for object size report
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function typoscriptCommand($key = null)
    {
        $this->typoscriptApiService = $this->objectManager->get(TyposcriptApiService::class);
        $results                    = $this->typoscriptApiService->getInfo($key);
        $this->writeResults($results);
    }
}
