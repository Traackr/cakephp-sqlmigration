<?php

/*
 * Based on:
 * http://bakery.cakephp.org/articles/erma/2010/01/05/cakephp-sql-shell-simple-and-powerful
 */

App::uses('Folder', 'Utility');

App::uses('ConnectionManager', 'Model');
App::uses('SchemaVersion', 'SqlMigration.Model');

class SqlMigrationShell extends Shell {

   /**
     * Connection used
     *
     * @var string
     */
   private $connection = 'default';

   /**
     * This plugin's name
     *
     * @var string
     */
   private $myName = 'SqlMigration';

   /**
     * Table holding schema version
     *
     * @var string
     */
   private $tableName = 'schema_versions';


   /**
     * Sucess status
     *
     */
   private $successStatus = 'SUCCESS';

   /**
     * Skipped status
     *
     */
   private $skippedStatus = 'SKIPPED';

   /**
     * Data model
     *
     */
   private $schemaVersionModel;


    /**
      * Overridding this method will prevent default welcome message
      */
   public function _welcome() {

      $this->out('SQL Migration plugin');
      $this->schemaVersionModel = new SchemaVersion();

   } // End function _welcome()


   /**
     * Function called if no other command is psecified
     */
   public function main() {

      $this->update();

   } // End function main()


   /**
     * Get latest version
     * @return int Latest version number
     */
   private function getVersion() {
      $latest = $this->schemaVersionModel->find('first', array(
          'order' => array('version DESC'),
          'limit' => 1
      ));
      if ( $latest && is_numeric($latest['SchemaVersion']['version']) ) {
         return (int)$latest['SchemaVersion']['version'];
      }
      else {
         $this->out('No version found. Assuming 0.');
         return 0;
      }
   } // End function getVersion()


   /**
     * Get all version history
     * @return int Latest version number
     */
   private function getAllVersions() {
      $all = $this->schemaVersionModel->find('all', array(
          'order' => array('version ASC')
      ));
      return $all;
   } // End function getAllVersions()


   /**
     * Set version
     * Create new verision if doesn't exists, update if existing.
     * @param int $version Version
     * @param String $status Status of upgrade to version
     */
   private function setVersion($version, $status) {
      $existingVersion = $this->schemaVersionModel->findByVersion($version);
      if ( $existingVersion ) {
          $this->schemaVersionModel->id = $existingVersion['SchemaVersion']['id'];
          $this->schemaVersionModel->saveField('status', $status);
      }
      else {
          $data = array('SchemaVersion' => array(
               'version' => $version,
               'status' => $status));
          $this->schemaVersionModel->create();
          $saved = $this->schemaVersionModel->save($data);
          if ( !$saved ) {
            $this->out('Unable to set version');
            $this->_stop();
          }
      }
   } // End function setVersion()


   /**
     * Get SQL to run (from file) for a given version
     * @param  int $verion Version number
     * @return String SQL to run
     */
   private function getSql($version) {
      if (($text = file_get_contents($filename = APP.'Config/Sql/upgrade-'.$version.'.sql')) !== false) {
         return $text;
      } else {
         $this->out("Couldn't load contents of file {$filename}, unable to uograde/downgrade");
         $this->_stop();
      }
   }  // End function getSql()


   /**
     * Run the update.
     * This will try to run all upgrade SQL file in order of version.
     * It wil also try to run./re-run any version that might have been
     * skipped previously
     */
   public function update() {
      $sqlFolder = new Folder(APP.'Config/Sql');
      $updateErrors = array();
      list($dirs, $files)     = $sqlFolder->read();
      $upgrades = array();
      foreach ($files as $i => $file) {
         if (preg_match( '/upgrade-(\d+)\.sql$/', $file, $matches))  {
            $upgrades[(int)$matches[1]] = $file;
         }
      }
      ksort($upgrades);
      $version = max(array_keys($upgrades));
      $this->out('Upgrading up to version : '.$version);

      // Get all versions
      $allVersions = $this->getAllVersions();

      // Try to run missing/skipped versions
      $this->out('Looking for missing versions');
      foreach ($allVersions as $v ) {
         if ( $v['SchemaVersion']['status'] === $this->skippedStatus && isset($upgrades[$v['SchemaVersion']['version']]) ) {
            $this->out('Running skipped version: ' . $upgrades[$v['SchemaVersion']['version']]);
            try {
              if ( !$this->executeSql($v['SchemaVersion']['version']) ) {
                 break;
              }
            } catch(Exception $e) {
              array_push($updateErrors, $e);
            }
         }
      }
      // Run upgrades up to the highest/latest verion of the upgrade files found
      for ($currentVersion = $this->getVersion(); $currentVersion < $version; $currentVersion++) {
         $this->out('Currently at Version '.$currentVersion);
         $this->out('Updating to Version '.($currentVersion+1));
         if ( !isset($upgrades[$currentVersion+1]) ) {
            $this->out('No upgrade file for version '.($currentVersion+1).'. Skipping');
            $this->setVersion((int)($currentVersion+1), $this->skippedStatus);
            continue;
         }
         try {
           if ( !$this->executeSql($currentVersion+1) ) {
              break;
           }
         } catch(Exception $e) {
            array_push($updateErrors, $e);
         }
      }
      $numErrors = count($updateErrors);
      if ($numErrors) {
        $this->error("There were " . $numErrors . " errors found while trying to upgrade your database.  Please investigate.");
      }
      $this->out('Done with upgrades. Now at version '.$this->getVersion());
   } // End function update

   /**
     * Execute SQL file for a given version
     * @param  int $version Version to execute
     * @return boolean False if user choose to not run the SQL. 'Skip' will return true
     */
   private function executeSql($version) {
      $this->out('Executing sql:');
      $this->hr();
      $this->out($sql = $this->getSql($version));
      $this->hr();
      $a = $this->in('Execute SQL? [y/n/s]');
      if ( $a === 'n') {
         return false;
      }
      else if ( $a === 's') {
         $this->setVersion((int)($version), $this->skippedStatus);
         return true;
      } else {
         $this->out('Launching MySQL to execute SQL');
         $database = ConnectionManager::getDataSource('default')->config;
         $sql_file = APP.'Config/Sql/upgrade-'.$version.'.sql';
         exec("mysql -vv --host=${database['host']} --user=${database['login']} --password=${database['password']} --database=${database['database']} < ${sql_file}", $sqlOutput, $execReturn);
         // The '-vv' option above is required for MySQL to generate output we
         // can display
         foreach ( $sqlOutput as $line) {
            $this->out($line);
         }
         // In the case of an error code, return an error to the caller
         if ($execReturn > 0) {
           $this->out('An error occurred trying to execute ' . $sql_file);
           $e =  new Exception("An error occurred trying to execute   " . $sql_file, $execReturn);
           throw $e;
         }
         $this->setVersion((int)($version), $this->successStatus);
      }
      return true;
   } // End function executeSql()


   /**
     * Check if the appropriate database exists for the plugin
     * @return [type] [description]
     */
   public function setup() {

      $ds = ConnectionManager::getDataSource($this->connection);
      $result = $ds->execute("SHOW TABLES LIKE '".$this->tableName."'")->fetch();
      if ( empty($result) ) {
            $this->out('Looks like this plugin was never used. Creating table needed');
            $this->createMigrationSchema();
      }
      else {
            $this->out('Updating database table needed by plugin');
            $this->updateMigrationSchema();
      }

   } // End function checkMigrationSchema()


   /**
     * Update the database table for the plugin
     */
   private function updateMigrationSchema() {

      // Command to run
      $command = 'schema update --quiet --plugin '.$this->myName.' --connection ' . $this->connection;
      // Dispatch to shell
      $this->dispatchShell($command);

      // Update incorrect status
      // This is a typo in original code when 'SUCCESS' as set at 'STATUS'
      $this->schemaVersionModel->query("UPDATE schema_versions SET STATUS = '".$this->successStatus."' WHERE STATUS = 'STATUS'");
      $this->out('Updated');

   } // End function updateMigrationchema()


   /**
     * Create database table needed by plugin
     */
   private function createMigrationSchema() {

      // Command to run
      $command = 'schema create --quiet --plugin '.$this->myName.' --connection ' . $this->connection;
      // Dispatch to shell
      $this->dispatchShell($command);
      $this->out('Created');

   } // End function createMigrationchema()

}
?>