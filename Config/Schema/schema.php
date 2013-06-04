<?php

class SqlMigrationSchema extends CakeSchema {
   var $name = 'SqlMigration';

   function before($event = array()) {
      return true;
   }

   function after($event = array()) {
   }

   var $schema_versions = array (
      'id'       => array('type' => 'integer',  'null' => false, 'default' => NULL, 'key' => 'primary'),
      'version'  => array('type' => 'integer',   'null' => false, 'default' => '-1'),
      'status'   => array('type' => 'string',   'null' => true),
      'created'  => array('type' => 'datetime', 'null' => true,  'default' => NULL),
      'modified' => array('type' => 'datetime', 'null' => true,  'default' => NULL),

      'indexes' => array(
         'PRIMARY'                    => array('column' => 'id',      'unique' => 1),
         'schema_version_idx'         => array('column' => 'version', 'unique' => 0),
         'schema_version_created_idx' => array('column' => 'created', 'unique' => 0)),

      'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
   );


} // End SqlMigrationSchema

?>
