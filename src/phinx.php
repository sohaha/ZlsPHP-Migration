<?php
z::config()
 ->addMasterPackage(__DIR__ . '/../../../../application/')
 ->setApplicationDir(__DIR__ . '/../../../../application/')
 ->setDatabaseConfig('database');
$confing = z::db()->getConfig();
$migrationTable = z::arrayGet($confing, 'tablePrefix') . 'phinxlog';
$master = $master = z::tap(z::db()->getMasters(), function ($master) {
    return end($master);
});
$database = [
    'adapter' => $confing['driverType'],
    'host'    => $master['hostname'],
    'name'    => $confing['database'],
    'user'    => $master['username'],
    'pass'    => $master['password'],
    'port'    => $master['port'],
    'charset' => $confing['charset'],
];
if ($confing['driverType'] === 'sqlsrv') {
    unset($database['charset']);
}
$pdo = z::db()->pdoInstance();
$database = [
    'name'       => $confing['database'],
    'connection' => $pdo,
];
$path = z::realPath(z::config()->getApplicationDir() . '../database', true, false);

return [
    'paths'         => [
        'migrations' => $path . '/migrations',
        'seeds'      => $path . '/seeds',
    ],
    'environments'  => [
        'default_migration_table' => 'phinxlog',
        'default_database'        => 'production',
        'production'              => $database,
    ],
    'version_order' => 'creation',
];
