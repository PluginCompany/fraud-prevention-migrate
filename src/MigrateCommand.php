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
namespace DataMigrator;

require __DIR__ . '/db.class.php';

use Ahc\Cli\Input\Command;
use Ahc\Cli\IO\Interactor;


class MigrateCommand extends Command
{

    /**
     * @var FieldNormalizer
     */
    private $fieldNormalizer;

    public function __construct()
    {
        $this->fieldNormalizer = new FieldNormalizer();
        parent::__construct('migrate', 'Migrate Fraud Suspicion Records and Rules from Magento 1 to Magento 2');
    }

    // This method is auto called before `self::execute()` and receives `Interactor $io` instance
    public function interact(Interactor $io)
    {
        $this
            ->askM1DatabaseInfo()
            ->askM2DatabaseInfo()
            ->askM2DatabaseQuerySettings()
            ->promptForSettingsConfirmation();
        ;
    }

    private function askM1DatabaseInfo()
    {

        $io = $this->io();
        $io->boldGreen("Magento 1 (source) Database Information", true);
        $this->set('m1dbHost', $io->prompt('Database Host', 'localhost'));
        $this->set('m1dbPort', $io->prompt('Database Port', '3306'));
        $this->set('m1dbUser', $io->prompt('Database User'));
        $this->set('m1dbPass', $io->prompt('Database Password'));
        $this->set('m1dbName', $io->prompt('Database Name'));

        if($io->confirm('Database uses table prefix', 'n')) {
            $this->set('m1dbPrefix', $io->prompt('Database Prefix'));
        }else{
            $this->set('m1dbPrefix', '');
        }
        return $this;
    }

    private function askM2DatabaseInfo()
    {
        $io = $this->io();
        $io->boldGreen("Magento 2 (target) Database Information", true);
        $this->set('m2dbHost', $io->prompt('Database Host', 'localhost'));
        $this->set('m2dbPort', $io->prompt('Database Port', '3306'));
        $this->set('m2dbUser', $io->prompt('Database User'));
        $this->set('m2dbPass', $io->prompt('Database Password'));
        $this->set('m2dbName', $io->prompt('Database Name'));

        if($io->confirm('Database uses table prefix', 'n')) {
            $this->set('m2dbPrefix', $io->prompt('Database Prefix'));
        }else{
            $this->set('m2dbPrefix', '');
        }
        return $this;
    }

    private function askM2DatabaseQuerySettings()
    {
        $io = $this->io();
        $migrateFraudRecords = $io->confirm('Migrate Fraud Suspcion Records', 'y');
        $this->set('migrateFraudRecords', $migrateFraudRecords);
        if($migrateFraudRecords) {
            $method = $io->choice('Fraud Record table insertion method', ['a' => 'Append rows to existing table', 't' => 'Truncate table and insert rows'], 'a');
            $this->set('recordInsertionMethod', $method);
            $this->set('keepCustomerIdRelation', $io->confirm('Keep customer entity_id relation from fraud record table', 'n'));
            $this->set('keepOrderIdRelation', $io->confirm('Keep order entity_id relation from fraud record table', 'n'));
        }

        $migrateRules = $io->confirm('Migrate Blacklisting Rules', 'y');
        $this->set('migrateBlacklistingRules', $migrateRules);
        if($migrateRules) {
            $method = $io->choice('Blacklisting Rules table insertion method', ['a' => 'Append rows to existing table', 't' => 'Truncate table and insert rows'], 'a');
            $this->set('ruleInsertionMethod', $method);
        }
        return $this;
    }

    private function promptForSettingsConfirmation()
    {
        $io = $this->io();
        $io->boldGreen("Confirm Settings", true);
        $this->renderValuesTable();
        $confirmed = $io->confirm('Are these settings correct?', 'y');
        if(!$confirmed) {
            $io->boldRed("Settings incorrect, exiting..", true);
            exit;
        }
        return $this;
    }

    private function renderValuesTable()
    {
        $values = [];
        foreach($this->values(false) as $key => $value) {
            $values[] = [
                'option' => $key,
                'value' => $value
            ];
        }
        $this->io()->table($values);
        return $this;
    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    // with correct $ball and $apple values
    public function execute()
    {
        if($this->migrateFraudRecords) {
            $this->doFraudRecordsMigration();
        }
        if($this->migrateBlacklistingRules) {
            $this->doRuleMigration();
        }
    }

    private function doFraudRecordsMigration()
    {
        $io = $this->io();
        $io->boldGreen('Migrating Fraud Suspicion Records', true);

        $m1DbConnection = $this->getM1DbConnection();
        $m1Table = $this->m1dbPrefix . 'plugincompany_blacklist_item';

        $m2DbConnection = $this->getM2DbConnection();
        $m2Table = $this->m2dbPrefix . 'plugincompany_fraudprevention_suspicion';

        if($this->recordInsertionMethod === 't') {
            $io->white("Truncating table...", true);
            $m2DbConnection->query("DELETE FROM $m2Table");
        }

        $io->white("Starting record migration...", true);

        $rowsToMigrate = $m1DbConnection->query("SELECT * FROM $m1Table");

        foreach($rowsToMigrate as $row) {
            unset($row['entity_id']);
            if(!$this->keepCustomerIdRelation) {
                unset($row['customer_id']);
            }
            if(!$this->keepOrderIdRelation) {
                unset($row['order_id']);
            }
            $row = $this->normalizeFields($row);
            $m2DbConnection->insert($m2Table, $row);
            $io->white('#', false);
        }
        $io->white('', true);
        $successCount = count($rowsToMigrate);
        $io->white("{$successCount} records migrated successfully", true);
        $io->white('', true);
        return $this;
    }

    private function doRuleMigration()
    {
        $io = $this->io();
        $io->boldGreen('Migrating Blacklisting Rules', true);

        $m1DbConnection = $this->getM1DbConnection();
        $m1Table = $this->m1dbPrefix . 'plugincompany_blacklist_rule';

        $m2DbConnection = $this->getM2DbConnection();
        $m2Table = $this->m2dbPrefix . 'plugincompany_fraudprevention_rule';

        if($this->ruleInsertionMethod === 't') {
            $io->white("Truncating table...", true);
            $m2DbConnection->query("DELETE FROM $m2Table");
        }

        $io->white("Starting rule migration...", true);

        $rowsToMigrate = $m1DbConnection->query("SELECT * FROM $m1Table");

        foreach($rowsToMigrate as $row) {
            unset($row['entity_id']);
            if($row['customer_groups'] == 'all') {
                $row['customer_groups'] = '3200';
            }
            $m2DbConnection->insert($m2Table, $row);
            $io->white('#', false);
        }
        $io->white('', true);
        $successCount = count($rowsToMigrate);
        $io->white("{$successCount} rules migrated successfully", true);
        $io->white('', true);
        return $this;
    }

    private function getM1DbConnection()
    {
        return $this->createConnection($this->m1dbHost, $this->m1dbUser, $this->m1dbPass, $this->m1dbName, $this->m1dbPort);
    }

    private function getM2DbConnection()
    {
        return $this->createConnection($this->m2dbHost, $this->m2dbUser, $this->m2dbPass, $this->m2dbName, $this->m2dbPort);
    }

    private function createConnection($server, $user, $password, $dbname, $port)
    {
        return new \MeekroDB($server, $user, $password, $dbname, $port);
    }

    private function normalizeFields($row)
    {
        foreach($row as $key => $value) {
            $normalizedValue = $this->fieldNormalizer->normalizeField($key, $value);
            if(!$normalizedValue) continue;
            $row[$key . '_normalized'] = $normalizedValue;
        }
        return $row;
    }



}
