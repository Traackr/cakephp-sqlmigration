CakePHP SQL Migration Plugin
============================

Introduction
------------

This is a CakePHP plugin to manage database schema updates


Installation
------------

To install this plugin you can:

* Clone the project on GitHub: <https://github.com/Traackr/cakephp-sqlmigration>. Make sure to clone in in your `app/Plugin` directory, preferably in a directory called `SqlMigration`
* User Composer to manage this plugin as a dependency. Add this to your `composer.json` file: 

```
"minimum-stability": "dev",
"require": {
   "traackr/sql-migration": "dev-master"
}
```

Requirements
------------

All upgarde scripts are SQL scripts are are run via `mysql` so the `mysql` executable you want to use need to be in your path for the plugin to work.


Setup
-----

This plugin uses a database table (`schema_versions`) to keep track of schema upgrade. Ti get started you need to setup the plugin, which will create that table for you. Once the plugin installed, simply do (in your `app` directory):

```
Console/cake SqlMigration.SqlMigration setup
```

It is safe to run this command multiple times. On subsequent calls, this command will simply try to apply any changes to the `schema_versions` table if any is requires (this might happen if/when you upgrade to a new version of this plugin).


Upgrade script
--------------

Upgarde scripts are simple SQL scripts and need to be put in the following directory: `app/Config/Sql`.
These script's names must follow the naming convention: `upgrade-<version-number>.sql`


Running an upgrade
------------------

To run the upgrade scripts on your schema, simply call:

```
Console/cake SqlMigration.SqlMigration
```

The Sql Migration plugin will run all the upgrade scripts that have not been applied yet (based on the information found in the `schema-versions` table). The versions number to not have to be continuous (ie. you can skip some versions), the plugin will apply the scripts in sequence and skip missing versions. If you later add a new upgrade scripts for this missing version (e.g. one was created in a code branch) they will will applied the next time you run the plugin (i.e. it will try to fill the gaps).
