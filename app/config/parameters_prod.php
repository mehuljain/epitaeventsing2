$secrets = json_decode(file_get_contents($_SERVER['APP_SECRETS']), true);

$container->setParameter('database_driver', 'pdo_mysql');
$container->setParameter('database_host', 'epitaeventsing2.mysql.eu2.frbit.com');
$container->setParameter('database_name', 'epitaeventsing2');
$container->setParameter('database_user', 'epitaeventsing2');
$container->setParameter('database_password', 'Le.fPnkIXtTOt3zeZ-TA+GwT');
