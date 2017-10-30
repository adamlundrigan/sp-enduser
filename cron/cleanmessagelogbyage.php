<?php

/*
 * Don't invoke directly. Run as:
 * php cron.php.txt cleanmessagelogbyage
 */

if (!isset($_SERVER['argc']))
	die('this file can only be run from command line');

define('BASE', dirname(__FILE__).'/..');
require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$dbh = $settings->getDatabase();

if ( 'age' !== $settings->getDatabaseLogCleanType() ) {
	die('this message log cleaning method is not active');
}

$retention = (int)$settings->getDatabaseLogCleanThreshold();

foreach ($settings->getMessagelogTables() as $table)
{
	$statement = $dbh->prepare('DELETE FROM ' . $table . ' WHERE msgts < (unix_timestamp() - :retention);');
	$statement->execute([':retention' => $retention]);
	$deleted = $statement->rowCount();
	echo "$table: deleted $deleted rows\n";
}
