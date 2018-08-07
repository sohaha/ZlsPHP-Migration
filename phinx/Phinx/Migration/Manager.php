<?php

namespace Phinx\Migration;

use Phinx\Config\Config;
use Phinx\Config\ConfigInterface;
use Phinx\Config\NamespaceAwareInterface;
use Phinx\Console\Command\OutputInterface;
use Phinx\Migration\Manager\Environment;
use Phinx\Seed\AbstractSeed;
use Phinx\Seed\SeedInterface;
use Phinx\Util\Util;
use Z;
use Zls\Migration\Argv as InputInterface;

class Manager
{
    /**
     * @var integer
     */
    const EXIT_STATUS_DOWN = 3;
    /**
     * @var integer
     */
    const EXIT_STATUS_MISSING = 2;
    /**
     * @var \Phinx\Config\ConfigInterface
     */
    protected $config;
    /**
     * @var \InputInterface
     */
    protected $input;
    /**
     * @var \Phinx\Console\Command\OutputInterface
     */
    protected $output;
    /**
     * @var array
     */
    protected $environments;
    /**
     * @var array
     */
    protected $migrations;
    /**
     * @var array
     */
    protected $seeds;

    /**
     * Class Constructor.
     * @param \Phinx\Config\ConfigInterface $config Configuration Object
     * @param InputInterface                $input  Console Input
     * @param OutputInterface               $output Console Output
     */
    public function __construct(ConfigInterface $config, InputInterface $input, OutputInterface $output)
    {
        $this->setConfig($config);
        $this->setInput($input);
        $this->setOutput($output);
    }

    /**
     * Prints the specified environment's migration status.
     * @param string $environment
     * @param null   $format
     * @return int 0 if all migrations are up, or an error code
     */
    public function printStatus($environment, $format = null)
    {
        $Fields = $this->getLogFields();
        $FstartTime = z::arrayGet($Fields, 'start_time');
        $Fversion = z::arrayGet($Fields, 'version');
        $FmigrationName = z::arrayGet($Fields, 'migration_name');
        $FendTime = z::arrayGet($Fields, 'end_time');
        $Fbreakpoint = z::arrayGet($Fields, 'breakpoint');
        $output = $this->getOutput();
        $hasDownMigration = false;
        $hasMissingMigration = false;
        $migrations = $this->getMigrations($environment);
        $migrationCount = 0;
        $missingCount = 0;
        $pendingMigrationCount = 0;
        if (count($migrations)) {
            // TODO - rewrite using Symfony Table Helper as we already have this library
            // included and it will fix formatting issues (e.g drawing the lines)
            $output->writeln('');
            switch ($this->getConfig()->getVersionOrder()) {
                case Config::VERSION_ORDER_CREATION_TIME:
                    $migrationIdAndStartedHeader = $this->getOutput()->infoText("[Migration ID]") . "  Started                ";
                    break;
                case Config::VERSION_ORDER_EXECUTION_TIME:
                    $migrationIdAndStartedHeader = "Migration ID    " . $this->getOutput()->infoText("[Started              ]");
                    break;
                default:
                    throw new \RuntimeException('Invalid version_order configuration option');
            }
            $output->writeln(" Status  $migrationIdAndStartedHeader  Finished                 Migration Name ");
            $output->writeln('------------------------------------------------------------------------------------------');
            $env = $this->getEnvironment($environment);
            $versions = $env->getVersionLog();
            $maxNameLength = $versions ? max(array_map(function ($version) use($FmigrationName){
                return strlen($version[$FmigrationName]);
            }, $versions)) : 0;
            $missingVersions = array_diff_key($versions, $migrations);
            $missingCount = count($missingVersions);
            $hasMissingMigration = !empty($missingVersions);
            // get the migrations sorted in the same way as the versions
            $sortedMigrations = [];
            foreach ($versions as $versionCreationTime => $version) {
                if (isset($migrations[$versionCreationTime])) {
                    array_push($sortedMigrations, $migrations[$versionCreationTime]);
                    unset($migrations[$versionCreationTime]);
                }
            }
            if (empty($sortedMigrations) && !empty($missingVersions)) {
                // this means we have no up migrations, so we write all the missing versions already so they show up
                // before any possible down migration
                foreach ($missingVersions as $missingVersionCreationTime => $missingVersion) {
                    $this->printMissingVersion($missingVersion, $maxNameLength);
                    unset($missingVersions[$missingVersionCreationTime]);
                }
            }
            // any migration left in the migrations (ie. not unset when sorting the migrations by the version order) is
            // a migration that is down, so we add them to the end of the sorted migrations list
            if (!empty($migrations)) {
                $sortedMigrations = array_merge($sortedMigrations, $migrations);
            }
            $migrationCount = count($sortedMigrations);
            foreach ($sortedMigrations as $migration) {
                $version = array_key_exists($migration->getVersion(), $versions) ? $versions[$migration->getVersion()] : false;
                if ($version) {
                    // check if there are missing versions before this version
                    foreach ($missingVersions as $missingVersionCreationTime => $missingVersion) {
                        if ($this->getConfig()->isVersionOrderCreationTime()) {
                            if ($missingVersion[$Fversion] > $version[$Fversion]) {
                                break;
                            }
                        } else {
                            if ($missingVersion[$FstartTime] > $version[$FstartTime]) {
                                break;
                            } elseif ($missingVersion[$FstartTime] == $version[$FstartTime] &&
                                $missingVersion[$Fversion] > $version[$Fversion]) {
                                break;
                            }
                        }
                        $this->printMissingVersion($missingVersion, $maxNameLength);
                        unset($missingVersions[$missingVersionCreationTime]);
                    }
                    $status = $this->getOutput()->infoText('     up ');
                } else {
                    $pendingMigrationCount++;
                    $hasDownMigration = true;
                    $status = $this->getOutput()->errorText('   down ');
                }
                $maxNameLength = max($maxNameLength, strlen($migration->getName()));
                $output->writeln(sprintf(
                    '%s %14.0f  %23s  %23s  ' . $this->getOutput()->tipText('%s'),
                    $status,
                    $migration->getVersion(),
                    $version[$FstartTime],
                    $version[$FendTime],
                    $migration->getName()
                ));
                if ($version && $version[$Fbreakpoint]) {
                    $output->writeln($output->errorText('         BREAKPOINT SET'));
                }
                $migrations[] = ['migration_status' => trim(strip_tags($status)), 'migration_id' => sprintf('%14.0f', $migration->getVersion()), 'migration_name' => $migration->getName()];
                unset($versions[$migration->getVersion()]);
            }
            // and finally add any possibly-remaining missing migrations
            foreach ($missingVersions as $missingVersionCreationTime => $missingVersion) {
                $this->printMissingVersion($missingVersion, $maxNameLength);
                unset($missingVersions[$missingVersionCreationTime]);
            }
        } else {
            // there are no migrations
            $output->writeln('');
            $output->writeln('There are no available migrations. Try creating one using the ' . $output->infoText('create') . ' command.');
        }
        // write an empty line
        $output->writeln('');
        if ($format !== null) {
            switch ($format) {
                case 'json':
                    $output->writeln(json_encode(
                        [
                            'pending_count' => $pendingMigrationCount,
                            'missing_count' => $missingCount,
                            'total_count'   => $migrationCount + $missingCount,
                            'migrations'    => $migrations,
                        ]
                    ));
                    break;
                default:
                    $output->writeln($output->infoText('Unsupported format: ' . $format));
            }
        }
        if ($hasMissingMigration) {
            return self::EXIT_STATUS_MISSING;
        } elseif ($hasDownMigration) {
            return self::EXIT_STATUS_DOWN;
        } else {
            return 0;
        }
    }

    /**
     * Gets the console output.
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Sets the console output.
     * @param \Phinx\Console\Command\OutputInterface $output Output
     * @return \Phinx\Migration\Manager
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Gets an array of the database migrations, indexed by migration name (aka creation time) and sorted in ascending
     * order
     * @param string $environment Environment
     * @throws \InvalidArgumentException
     * @return \Phinx\Migration\AbstractMigration[]
     */
    public function getMigrations($environment)
    {
        if ($this->migrations === null) {
            $phpFiles = $this->getMigrationFiles();
            if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $this->getOutput()->writeln('Migration file');
                $this->getOutput()->writeln(
                    array_map(
                        function ($phpFile) {
                            return '    ' . $phpFile;
                        },
                        $phpFiles
                    )
                );
            }
            // filter the files to only get the ones that match our naming scheme
            $fileNames = [];
            /** @var \Phinx\Migration\AbstractMigration[] $versions */
            $versions = [];
            foreach ($phpFiles as $filePath) {
                if (Util::isValidMigrationFileName(basename($filePath))) {
                    if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                        $this->getOutput()->writeln('Valid migration file');
                        $this->getOutput()->writeln(
                            array_map(
                                function ($phpFile) {
                                    return '    ' . $phpFile;
                                },
                                $phpFiles
                            )
                        );
                    }
                    $version = Util::getVersionFromFileName(basename($filePath));
                    if (isset($versions[$version])) {
                        throw new \InvalidArgumentException(sprintf('Duplicate migration - "%s" has the same version as "%s"', $filePath, $versions[$version]->getVersion()));
                    }
                    $config = $this->getConfig();
                    $namespace = $config instanceof NamespaceAwareInterface ? $config->getMigrationNamespaceByPath(dirname($filePath)) : null;
                    // convert the filename to a class name
                    $class = ($namespace === null ? '' : $namespace . '\\') . Util::mapFileNameToClassName(basename($filePath));
                    if (isset($fileNames[$class])) {
                        throw new \InvalidArgumentException(sprintf(
                            'Migration "%s" has the same name as "%s"',
                            basename($filePath),
                            $fileNames[$class]
                        ));
                    }
                    $fileNames[$class] = basename($filePath);
                    if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                        $this->getOutput()->writeln("Loading class $class from $filePath");
                    }
                    // load the migration file
                    $orig_display_errors_setting = ini_get('display_errors');
                    ini_set('display_errors', 'On');
                    /** @noinspection PhpIncludeInspection */
                    require_once $filePath;
                    ini_set('display_errors', $orig_display_errors_setting);
                    if (!class_exists($class)) {
                        throw new \InvalidArgumentException(sprintf(
                            'Could not find class "%s" in file "%s"',
                            $class,
                            $filePath
                        ));
                    }
                    if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                        $this->getOutput()->writeln("Running $class");
                    }
                    // instantiate it
                    $migration = new $class($environment, $version, $this->getInput(), $this->getOutput());
                    if (!($migration instanceof AbstractMigration)) {
                        throw new \InvalidArgumentException(sprintf(
                            'The class "%s" in file "%s" must extend \Phinx\Migration\AbstractMigration',
                            $class,
                            $filePath
                        ));
                    }
                    $versions[$version] = $migration;
                } else {
                    if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                        $this->getOutput()->writeln('Invalid migration file');
                        $this->getOutput()->writeln(
                            array_map(
                                function ($phpFile) {
                                    return '  ' . $phpFile;
                                },
                                $phpFiles
                            )
                        );
                    }
                }
            }
            ksort($versions);
            $this->setMigrations($versions);
        }

        return $this->migrations;
    }

    /**
     * Sets the database migrations.
     * @param array $migrations Migrations
     * @return \Phinx\Migration\Manager
     */
    public function setMigrations(array $migrations)
    {
        $this->migrations = $migrations;

        return $this;
    }

    /**
     * Returns a list of migration files found in the provided migration paths.
     * @return string[]
     */
    protected function getMigrationFiles()
    {
        $config = $this->getConfig();
        $paths = $config->getMigrationPaths();
        $files = [];
        foreach ($paths as $path) {
            $files = array_merge(
                $files,
                Util::glob($path . DIRECTORY_SEPARATOR . '*.php')
            );
        }
        // glob() can return the same file multiple times
        // This will cause the migration to fail with a
        // false assumption of duplicate migrations
        // http://php.net/manual/en/function.glob.php#110340
        $files = array_unique($files);

        return $files;
    }

    /**
     * Gets the config.
     * @return \Phinx\Config\ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Sets the config.
     * @param  \Phinx\Config\ConfigInterface $config Configuration Object
     * @return \Phinx\Migration\Manager
     */
    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Gets the console input.
     * @return \InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Sets the console input.
     * @param InputInterface $input Input
     * @return \Phinx\Migration\Manager
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Gets the manager class for the given environment.
     * @param string $name Environment Name
     * @throws \InvalidArgumentException
     * @return \Phinx\Migration\Manager\Environment
     */
    public function getEnvironment($name)
    {
        $config = $this->getConfig();
        if (isset($this->environments[$name])) {
            return $this->environments[$name];
        }
        // check the environment exists
        if (!$config->hasEnvironment($name)) {
            throw new \InvalidArgumentException(sprintf(
                'The environment "%s" does not exist',
                $name
            ));
        }
        // create an environment instance and cache it
        $envOptions = $config->getEnvironment($name);
        $envOptions['version_order'] = $this->getConfig()->getVersionOrder();
        $envOptions['log_fields'] = $config->getAlias('fields');
        $environment = new Environment($name, $envOptions);
        $this->environments[$name] = $environment;
        $environment->setInput($this->getInput());
        $environment->setOutput($this->getOutput());

        return $environment;
    }

    /**
     * Print Missing Version
     * @param array $version       The missing version to print (in the format returned by Environment.getVersionLog).
     * @param int   $maxNameLength The maximum migration name length.
     */
    private function printMissingVersion($version, $maxNameLength)
    {
        $Fields = $this->getLogFields();
        $FstartTime = z::arrayGet($Fields, 'start_time');
        $Fversion = z::arrayGet($Fields, 'version');
        $FmigrationName = z::arrayGet($Fields, 'migration_name');
        $FendTime = z::arrayGet($Fields, 'end_time');
        $Fbreakpoint = z::arrayGet($Fields, 'breakpoint');
        $this->getOutput()->writeln(sprintf(
            $this->getOutput()->errorText('     up') . '  %14.0f  %19s  %19s  ' . $this->getOutput()->warningText('%s') . $this->getOutput()->errorText('** MISSING **'),
            $version[$Fversion],
            $version[$FstartTime],
            $version[$FendTime],
            str_pad($version[$FmigrationName], $maxNameLength, ' ')
        ));
        if ($version && $version[$Fbreakpoint]) {
            $this->getOutput()->writeln($this->getOutput()->errorText('         BREAKPOINT SET'));
        }
    }

    /**
     * Migrate to the version of the database on a given date.
     * @param string    $environment Environment
     * @param \DateTime $dateTime    Date to migrate to
     * @param bool      $fake        flag that if true, we just record running the migration, but not actually do the
     *                               migration
     * @return void
     */
    public function migrateToDateTime($environment, \DateTime $dateTime, $fake = false)
    {
        $versions = array_keys($this->getMigrations($environment));
        $dateString = $dateTime->format('YmdHis');
        $outstandingMigrations = array_filter($versions, function ($version) use ($dateString) {
            return $version <= $dateString;
        });
        if (count($outstandingMigrations) > 0) {
            $migration = max($outstandingMigrations);
            $this->getOutput()->writeln('Migrating to version ' . $migration);
            $this->migrate($environment, $migration, $fake);
        }
    }

    /**
     * Migrate an environment to the specified version.
     * @param string $environment Environment
     * @param int    $version     version to migrate to
     * @param bool   $fake        flag that if true, we just record running the migration, but not actually do the migration
     * @return void
     */
    public function migrate($environment, $version = null, $fake = false)
    {
        $migrations = $this->getMigrations($environment);
        $env = $this->getEnvironment($environment);
        $versions = $env->getVersions();
        $current = $env->getCurrentVersion();
        if (empty($versions) && empty($migrations)) {
            return;
        }
        if ($version === null) {
            $version = max(array_merge($versions, array_keys($migrations)));
        } else {
            if (0 != $version && !isset($migrations[$version])) {
                $this->output->writeln(sprintf(
                    $this->output->warningText('warning') . ' %s is not a valid version',
                    $version
                ));

                return;
            }
        }
        // are we migrating up or down?
        $direction = $version > $current ? MigrationInterface::UP : MigrationInterface::DOWN;
        if ($direction === MigrationInterface::DOWN) {
            // run downs first
            krsort($migrations);
            foreach ($migrations as $migration) {
                if ($migration->getVersion() <= $version) {
                    break;
                }
                if (in_array($migration->getVersion(), $versions)) {
                    $this->executeMigration($environment, $migration, MigrationInterface::DOWN, $fake);
                }
            }
        }
        ksort($migrations);
        foreach ($migrations as $migration) {
            if ($migration->getVersion() > $version) {
                break;
            }
            if (!in_array($migration->getVersion(), $versions)) {
                $this->executeMigration($environment, $migration, MigrationInterface::UP, $fake);
            }
        }
    }

    /**
     * Execute a migration against the specified environment.
     * @param string                              $name      Environment Name
     * @param \Phinx\Migration\MigrationInterface $migration Migration
     * @param string                              $direction Direction
     * @param bool                                $fake      flag that if true, we just record running the migration, but not actually do the migration
     * @return void
     */
    public function executeMigration($name, MigrationInterface $migration, $direction = MigrationInterface::UP, $fake = false)
    {
        $this->getOutput()->writeln('');
        $this->getOutput()->writeln(
            ' ==' .
            ' ' . $migration->getVersion() . ' ' . $this->getOutput()->colorText($migration->getName(), 'green') . ':' .
            ' ' . ($direction === MigrationInterface::UP ? 'migrating' : 'reverting')
            , 'white');
        $migration->preFlightCheck($direction);
        // Execute the migration and log the time elapsed.
        $start = microtime(true);
        $this->getEnvironment($name)->executeMigration($migration, $direction, $fake);
        $end = microtime(true);
        $this->getOutput()->writeln(
            ' ==' .
            ' ' . $migration->getVersion() . ' ' . $this->getOutput()->colorText($migration->getName(), 'green') . ':' .
            ' ' . ($direction === MigrationInterface::UP ? 'migrated' : 'reverted') .
            ' ' . sprintf('%.4fs', $end - $start)
            , 'light_gray');
    }

    /**
     * Rollback an environment to the specified version.
     * @param string     $environment Environment
     * @param int|string $target
     * @param bool       $force
     * @param bool       $targetMustMatchVersion
     * @param bool       $fake        flag that if true, we just record running the migration, but not actually do the migration
     * @return void
     */
    public function rollback($environment, $target = null, $force = false, $targetMustMatchVersion = true, $fake = false)
    {
        $Fields = $this->getLogFields();
        $FstartTime = z::arrayGet($Fields, 'start_time');
        $Fversion = z::arrayGet($Fields, 'version');
        $FmigrationName = z::arrayGet($Fields, 'migration_name');
        $FendTime = z::arrayGet($Fields, 'end_time');
        $Fbreakpoint = z::arrayGet($Fields, 'breakpoint');
        $migrations = $this->getMigrations($environment);
        $executedVersions = $this->getEnvironment($environment)->getVersionLog();
        $sortedMigrations = [];
        foreach ($executedVersions as $versionCreationTime => &$executedVersion) {
            // if we have a date (ie. the target must not match a version) and we are sorting by execution time, we
            // convert the version start time so we can compare directly with the target date
            if (!$this->getConfig()->isVersionOrderCreationTime() && !$targetMustMatchVersion) {
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $executedVersion[$FstartTime]);
                $executedVersion[$FstartTime] = $dateTime->format('YmdHis');
            }
            if (isset($migrations[$versionCreationTime])) {
                array_unshift($sortedMigrations, $migrations[$versionCreationTime]);
            } else {
                // this means the version is missing so we unset it so that we don't consider it when rolling back
                // migrations (or choosing the last up version as target)
                unset($executedVersions[$versionCreationTime]);
            }
        }
        if ($target === 'all' || $target === '0') {
            $target = 0;
        } elseif (!is_numeric($target) && !is_null($target)) { // try to find a target version based on name
            // search through the migrations using the name
            $migrationNames = array_map(function ($item) use ($FmigrationName) {
                return $item[$FmigrationName];
            }, $executedVersions);
            $found = array_search($target, $migrationNames);
            // check on was found
            if ($found !== false) {
                $target = (string)$found;
            } else {
                $this->getOutput()->writeln($this->getOutput()->errorText("No migration found with name ($target)"));

                return;
            }
        }
        // Check we have at least 1 migration to revert
        $executedVersionCreationTimes = array_keys($executedVersions);
        if (empty($executedVersionCreationTimes) || $target == end($executedVersionCreationTimes)) {
            $this->getOutput()->writeln(PHP_EOL . $this->getOutput()->warningText('No migrations to rollback'));

            return;
        }
        // If no target was supplied, revert the last migration
        if ($target === null) {
            // Get the migration before the last run migration
            $prev = count($executedVersionCreationTimes) - 2;
            $target = $prev >= 0 ? $executedVersionCreationTimes[$prev] : 0;
        }
        // If the target must match a version, check the target version exists
        if ($targetMustMatchVersion && 0 !== $target && !isset($migrations[$target])) {
            $this->getOutput()->writeln(($this->getOutput()->errorText("Target version ($target) not found")));

            return;
        }
        // Rollback all versions until we find the wanted rollback target
        $rollbacked = false;
        foreach ($sortedMigrations as $migration) {
            if ($targetMustMatchVersion && $migration->getVersion() == $target) {
                break;
            }
            if (in_array($migration->getVersion(), $executedVersionCreationTimes)) {
                $executedVersion = $executedVersions[$migration->getVersion()];
                if (!$targetMustMatchVersion) {
                    if (($this->getConfig()->isVersionOrderCreationTime() && $executedVersion[$Fversion] <= $target) ||
                        (!$this->getConfig()->isVersionOrderCreationTime() && $executedVersion[$FstartTime] <= $target)) {
                        break;
                    }
                }
                if (0 != $executedVersion[$Fbreakpoint] && !$force) {
                    $this->getOutput()->writeln(($this->getOutput()->errorText('Breakpoint reached. Further rollbacks inhibited.')));
                    break;
                }
                $this->executeMigration($environment, $migration, MigrationInterface::DOWN, $fake);
                $rollbacked = true;
            }
        }
        if (!$rollbacked) {
            $this->getOutput()->writeln(PHP_EOL . $this->getOutput()->warningText('No migrations to rollback'));
        }
    }

    public function getLogFields()
    {
        $logFields = $this->getConfig()->getAlias('fields');

        return [
            'version'        => z::arrayGet($logFields, 'version', 'version'),
            'migration_name' => z::arrayGet($logFields, 'migration_name', 'migration_name'),
            'start_time'     => z::arrayGet($logFields, 'start_time', 'start_time'),
            'end_time'       => z::arrayGet($logFields, 'end_time', 'end_time'),
            'breakpoint'     => z::arrayGet($logFields, 'breakpoint', 'breakpoint'),
        ];
    }

    /**
     * Run database seeders against an environment.
     * @param string $environment Environment
     * @param string $seed        Seeder
     * @return void
     */
    public function seed($environment, $seed = null)
    {
        $seeds = $this->getSeeds();
        if ($seed === null) {
            // run all seeders
            foreach ($seeds as $seeder) {
                if (array_key_exists($seeder->getName(), $seeds)) {
                    $this->executeSeed($environment, $seeder);
                }
            }
        } else {
            // run only one seeder
            if (array_key_exists($seed, $seeds)) {
                $this->executeSeed($environment, $seeds[$seed]);
            } else {
                throw new \InvalidArgumentException(sprintf('The seed class "%s" does not exist', $seed));
            }
        }
    }

    /**
     * Gets an array of database seeders.
     * @throws \InvalidArgumentException
     * @return \Phinx\Seed\AbstractSeed[]
     */
    public function getSeeds()
    {
        if ($this->seeds === null) {
            $phpFiles = $this->getSeedFiles();
            // filter the files to only get the ones that match our naming scheme
            $fileNames = [];
            /** @var \Phinx\Seed\AbstractSeed[] $seeds */
            $seeds = [];
            foreach ($phpFiles as $filePath) {
                if (Util::isValidSeedFileName(basename($filePath))) {
                    $config = $this->getConfig();
                    $namespace = $config instanceof NamespaceAwareInterface ? $config->getSeedNamespaceByPath(dirname($filePath)) : null;
                    // convert the filename to a class name
                    $class = ($namespace === null ? '' : $namespace . '\\') . pathinfo($filePath, PATHINFO_FILENAME);
                    $fileNames[$class] = basename($filePath);
                    // load the seed file
                    /** @noinspection PhpIncludeInspection */
                    require_once $filePath;
                    if (!class_exists($class)) {
                        throw new \InvalidArgumentException(sprintf(
                            'Could not find class "%s" in file "%s"',
                            $class,
                            $filePath
                        ));
                    }
                    // instantiate it
                    $seed = new $class($this->getInput(), $this->getOutput());
                    if (!($seed instanceof AbstractSeed)) {
                        throw new \InvalidArgumentException(sprintf(
                            'The class "%s" in file "%s" must extend \Phinx\Seed\AbstractSeed',
                            $class,
                            $filePath
                        ));
                    }
                    $seeds[$class] = $seed;
                }
            }
            ksort($seeds);
            $this->setSeeds($seeds);
        }
        $this->seeds = $this->orderSeedsByDependencies($this->seeds);

        return $this->seeds;
    }

    /**
     * Sets the database seeders.
     * @param array $seeds Seeders
     * @return \Phinx\Migration\Manager
     */
    public function setSeeds(array $seeds)
    {
        $this->seeds = $seeds;

        return $this;
    }

    /**
     * Returns a list of seed files found in the provided seed paths.
     * @return string[]
     */
    protected function getSeedFiles()
    {
        $config = $this->getConfig();
        $paths = $config->getSeedPaths();
        $files = [];
        foreach ($paths as $path) {
            $files = array_merge(
                $files,
                Util::glob($path . DIRECTORY_SEPARATOR . '*.php')
            );
        }
        // glob() can return the same file multiple times
        // This will cause the migration to fail with a
        // false assumption of duplicate migrations
        // http://php.net/manual/en/function.glob.php#110340
        $files = array_unique($files);

        return $files;
    }

    /**
     * Order seeds by dependencies
     * @param AbstractSeed[] $seeds Seeds
     * @return AbstractSeed[]
     */
    private function orderSeedsByDependencies(array $seeds)
    {
        $orderedSeeds = [];
        foreach ($seeds as $seed) {
            $key = get_class($seed);
            $dependencies = $this->getSeedDependenciesInstances($seed);
            if (!empty($dependencies)) {
                $orderedSeeds[$key] = $seed;
                $orderedSeeds = array_merge($this->orderSeedsByDependencies($dependencies), $orderedSeeds);
            } else {
                $orderedSeeds[$key] = $seed;
            }
        }

        return $orderedSeeds;
    }

    /**
     * Get seed dependencies instances from seed dependency array
     * @param AbstractSeed $seed Seed
     * @return AbstractSeed[]
     */
    private function getSeedDependenciesInstances(AbstractSeed $seed)
    {
        $dependenciesInstances = [];
        $dependencies = $seed->getDependencies();
        if (!empty($dependencies)) {
            foreach ($dependencies as $dependency) {
                foreach ($this->seeds as $seed) {
                    if (get_class($seed) === $dependency) {
                        $dependenciesInstances[get_class($seed)] = $seed;
                    }
                }
            }
        }

        return $dependenciesInstances;
    }

    /**
     * Execute a seeder against the specified environment.
     * @param string                    $name Environment Name
     * @param \Phinx\Seed\SeedInterface $seed Seed
     * @return void
     */
    public function executeSeed($name, SeedInterface $seed)
    {
        $this->getOutput()->writeln('');
        $this->getOutput()->writeln(
            ' ==' .
            ' ' . $this->getOutput()->infoText($seed->getName() . ':') .
            $this->getOutput()->warningText(' seeding')
        );
        // Execute the seeder and log the time elapsed.
        $start = microtime(true);
        $this->getEnvironment($name)->executeSeed($seed);
        $end = microtime(true);
        $this->getOutput()->writeln(
            ' ==' .
            $this->getOutput()->infoText(' ' . $seed->getName() . ':') .
            $this->getOutput()->warningText(' seeded' .
                ' ' . sprintf('%.4fs', $end - $start) . '')
        );
    }

    /**
     * Sets the environments.
     * @param array $environments Environments
     * @return \Phinx\Migration\Manager
     */
    public function setEnvironments($environments = [])
    {
        $this->environments = $environments;

        return $this;
    }

    /**
     * Toggles the breakpoint for a specific version.
     * @param string   $environment
     * @param int|null $version
     * @return void
     */
    public function toggleBreakpoint($environment, $version)
    {

        $migrations = $this->getMigrations($environment);
        $this->getMigrations($environment);
        $env = $this->getEnvironment($environment);
        $versions = $env->getVersionLog();
        $Fields = $this->getLogFields();
        $FstartTime = z::arrayGet($Fields, 'start_time');
        $Fversion = z::arrayGet($Fields, 'version');
        $FmigrationName = z::arrayGet($Fields, 'migration_name');
        $FendTime = z::arrayGet($Fields, 'end_time');
        $Fbreakpoint = z::arrayGet($Fields, 'breakpoint');
        if (empty($versions) || empty($migrations)) {
            return;
        }
        if ($version === null) {
            $lastVersion = end($versions);
            $version = $lastVersion[$Fversion];
        }
        if (0 != $version && !isset($migrations[$version])) {
            $this->output->writeln(sprintf(
                $this->output->warningText('warning').' %s is not a valid version',
                $version
            ));

            return;
        }
        $env->getAdapter()->toggleBreakpoint($migrations[$version]);
        $versions = $env->getVersionLog();
        $this->getOutput()->writeln(
            ' Breakpoint ' . ($versions[$version][$Fbreakpoint] ? 'set' : 'cleared') .
            ' for ' . $this->getOutput()->infoText($version) .
            $this->getOutput()->warningText(' ' . $migrations[$version]->getName())
        );
    }

    /**
     * Remove all breakpoints
     * @param string $environment
     * @return void
     */
    public function removeBreakpoints($environment)
    {
        $this->getOutput()->writeln(sprintf(
            ' %d breakpoints cleared.',
            $this->getEnvironment($environment)->getAdapter()->resetAllBreakpoints()
        ));
    }
}
