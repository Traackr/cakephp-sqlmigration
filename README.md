CakePHP SQL Migration Plugin
============================

Introduction
------------

This is a CakePHP plugin to manage database schema updates


Installation
------------

To install this plugin you can:

* Clone the project on GitHub: <https://github.com/Traackr/cakephp-sqlmigration>. Make sure to clone in in your `app/Plugin` directory, preferably in a directory called `SqlMigration`
* Use Composer to manage this plugin as a dependency. To do this, simply add this to your `composer.json` file: 

```
"minimum-stability": "dev",
"require": {
   "traackr/sql-migration": "dev-master"
}
```

Requirements
------------

All upgarde scripts are SQL scripts and are run via `mysql` therefore the `mysql` executable you want to use must be in your path for the plugin to work.


Setup
-----

This plugin uses a database table (`schema_versions`) to keep track of schema upgrades. To get started you need to setup the plugin, which will create that table for you. Once the plugin is installed, simply do (in your `app` directory):

```
Console/cake SqlMigration.SqlMigration setup
```

It is safe to run this command multiple times. On subsequent calls, the command will simply try to apply any changes to the `schema_versions` table if any is requires (this might happen if/when you upgrade to a new version of this plugin).


Upgrade script
--------------

Upgarde scripts are simple SQL scripts and need to live in the following directory: `app/Config/Sql`.
Ths script's names must follow this naming convention: `upgrade-<version-number>.sql`


Running an upgrade
------------------

To run the upgrade scripts on your schema, simply call:

```
Console/cake SqlMigration.SqlMigration
```

The SqlMigration plugin will run all the upgrade scripts that have not been applied yet (based on the information found in the `schema-versions` table). The version numbers to not have to be continuous (ie. you can skip some versions), the plugin will apply the scripts in sequence and skip missing versions. If you later add a new upgrade scripts for any missing version (e.g. one was created in a branch and merged with the main core later), these scripts will be applied the next time you run the plugin (i.e. it will try to fill the gaps).
