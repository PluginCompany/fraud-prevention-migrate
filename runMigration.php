<?php
/**
 * Created by:  Milan Simek
 * Company:     Plugin Company
 *
 * LICENSE: http://plugin.company/docs/magento-extensions/magento-extension-license-agreement
 *
 * YOU WILL ALSO FIND A PDF COPY OF THE LICENSE IN THE DOWNLOADED ZIP FILE
 *
 * FOR QUESTIONS AND SUPPORT
 * PLEASE DON'T HESITATE TO CONTACT US AT:
 *
 * SUPPORT@PLUGIN.COMPANY
 */

use DataMigrator\MigrateCommand;

ini_set('display_errors', 1);

$autoload = __DIR__ . '/../../autoload.php';
if(!file_exists($autoload)) {
    $autoload = __DIR__ . '/vendor/autoload.php';
}
require $autoload;



// Init App with name and version
$app = new Ahc\Cli\Application('Fraud Prevention Data Migration', 'v1.0.0');

// Add commands with optional aliases`
$app->add(new MigrateCommand(), '', true);
$app->handle($_SERVER['argv']);