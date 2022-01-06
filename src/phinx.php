<?php
$zlsConfig = Z::config();
$origin = Z::realPath('.', true, false);
// Z::config()
//     ->addMasterPackage($origin . 'application/')
//     ->setAppDir($origin . 'application/')
//     ->setDatabaseConfig('database');
$confing = Z::db()->getConfig();
$master = Z::tap(Z::db()->getMasters(), function ($master) {
    return end($master);
});

function scan($dir)
{
    $paths = [];
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                if (in_array($file, ['.', '..'])) continue;
                $path = Z::realPath($dir, true) . $file;
                if ($file === 'Migrations') {
                    $paths [] = $path;
                } else if (is_dir($path)) {
                    $paths = array_merge($paths, scan($path));
                }
            }
        }
    }
    return $paths;
}

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

$pdo = Z::db()->pdoInstance();
$database = [
    'name' => $confing['database'],
    'connection' => $pdo,
    'table_prefix' => $confing['tablePrefix'],
    'table_suffix' => '',
];
$migrationPath = Z::realPath($origin . 'migration.ini');
$ini = is_file($migrationPath = $origin . 'migration.ini') ? @parse_ini_file($migrationPath, true) : [];
$ini = Z::arrayGet($ini, 'base');
$versionOrder = Z::arrayGet($ini, 'versionOrder', 'creation');
$migrationTable = Z::arrayGet($ini, 'migrationTable', 'migrations_log');
$fields = Z::arrayGet($ini, 'logFields', []);
// 表迁移目录
$migrationPathDefault = 'database/migrations';
$migrationPath = Z::arrayMap((array)Z::arrayGet($ini, 'migrationPath', $migrationPathDefault), function ($v) use ($origin, $migrationPathDefault) {
    $path = $v ?: $migrationPathDefault;

    return preg_match('/[\*\{\}\,]/', $v) ? Z::realPath($path, true, $origin) : Z::realPathMkdir($path, true, false, $origin);
});
// 数据填充目录
$seedPathDefault = 'database/seeds';
$seedPath = Z::arrayMap((array)Z::arrayGet($ini, 'seedPath', $seedPathDefault), function ($v) use ($origin, $seedPathDefault) {
    $path = $v ?: $seedPathDefault;

    return preg_match('/[\*\{\}\,]/', $v) ? Z::realPath($path, true, $origin) : Z::realPathMkdir($path, true, false, $origin);
});

$dir = Z::realPath('vendor/zls', true, false);
$vendor = scan($dir);
$zls = scan(ZLS_APP_PATH . $zlsConfig->getClassesDirName() . '/Zls');
return [
    'paths' => [
        'migrations' => array_merge($migrationPath, $vendor, $zls),
        'seeds' => $seedPath,
    ],
    'aliases' => [
        'fields' => $fields,
    ],
    'environments' => [
        'default_migration_table' => $confing['tablePrefix'] . $migrationTable,
        'default_database' => 'production',
        'production' => $database,
    ],
    'version_order' => $versionOrder,
];
