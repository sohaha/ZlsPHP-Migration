<?php
$origin = getcwd() . '/';
z::config()
 ->addMasterPackage($origin . 'application/')
 ->setApplicationDir($origin . 'application/')
 ->setDatabaseConfig('database');
$confing = z::db()->getConfig();
$migrationTable = z::arrayGet($confing, 'tablePrefix') . 'phinxlog';
$master = $master = z::tap(z::db()->getMasters(), function ($master) {
    return end($master);
});
//$database = [
//    'adapter' => $confing['driverType'],
//    'host'    => $master['hostname'],
//    'name'    => $confing['database'],
//    'user'    => $master['username'],
//    'pass'    => $master['password'],
//    'port'    => $master['port'],
//    'charset' => $confing['charset'],
//];
//if ($confing['driverType'] === 'sqlsrv') {
//    unset($database['charset']);
//}
$pdo = z::db()->pdoInstance();
$database = [
    'name'       => $confing['database'],
    'connection' => $pdo,
];
$migrationPath = z::realPath($origin . 'migration.ini');
$ini = is_file($migrationPath = $origin . 'migration.ini') ? @parse_ini_file($migrationPath, true) : [];
$ini = z::arrayGet($ini, 'base');
$versionOrder = z::arrayGet($ini, 'versionOrder', 'creation');
$migrationTable = z::arrayGet($ini, 'migrationTable', 'migrations_log');
// 表迁移目录
$migrationPathDefault = 'database/migrations';
$migrationPath = z::arrayMap((array)z::arrayGet($ini, 'migrationPath', $migrationPathDefault), function ($v) use ($origin, $migrationPathDefault) {
    $path = $v ?: $migrationPathDefault;

    return preg_match('/[\*\{\}\,]/', $v) ? z::realPath($path, true, $origin) : z::realPathMkdir($path, true, false, $origin);
});
// 数据填充目录
$seedPathDefault = 'database/seeds';
$seedPath = z::arrayMap((array)z::arrayGet($ini, 'seedPath', $seedPathDefault), function ($v) use ($origin, $seedPathDefault) {
    $path = $v ?: $seedPathDefault;

    return preg_match('/[\*\{\}\,]/', $v) ? z::realPath($path, true, $origin) : z::realPathMkdir($path, true, false, $origin);
});

return [
    'paths'         => [
        'migrations' => $migrationPath,
        'seeds'      => $seedPath,
    ],
    'environments'  => [
        'default_migration_table' => $confing['tablePrefix'] . $migrationTable,
        'default_database'        => 'production',
        'production'              => $database,
    ],
    'version_order' => $versionOrder,
];
