Bounce [![Build Status](https://travis-ci.org/kamisama/bounce.png)](https://travis-ci.org/kamisama/bounce) [![Coverage Status](https://coveralls.io/repos/kamisama/bounce/badge.png)](https://coveralls.io/r/kamisama/bounce)
======

A very lightweight CakePhp plugin to make your models automagically indexable by Elastic Search

Just install the plugin and attach the **Bounce.Indexable**, … and voila !
It's the simplest plugin to easily get your CakePHP hooked with Elastic Search.

## Installation

### Via git

	clone git://github.com/kamisama/bounce.git path/to/app/Plugin/Bounce

### Via Composer

Just add *kamisama/bounce* to your require, in your app composer.json.

### Load the plugin

Load the plugin into your app by editing your Config/bootstrap.php

```php
CakePlugin::loadAll(array('Bounce' => array('bootstrap' => true)));
```

## Configuration

You can edit the connection info to your elastic search server in Bounce/Config/bootstrap

```php
Configure::write('Bounce', array(
	'host' => '127.0.0.1',
	'port' => '9200'
));
```

Then attach the Indexable Behavior to your model

```php
public $actAs = array('Bounce.Indexable');
```

The behavior offers a few options :

* `index` (string): specify the index name of your model, default to *main*
* `type` (string): specify the type name of your model, default to your model alias
* `mapping` (string): specify which fields you want to index in elastic search, default to *false*, will index all fields.

#### Example

```php
class Song extends AppModel
{

	public $actAs = array('Bounce.Indexable' => array(
		'index' => 'music',
		'type' => 'song',
		'mapping' => array(
			'title' => 'string',
			'track' => 'integer',
			'length' => 'integer'
		)
	));
}
```

The behavior will then only index the title, track and length fields, all the other field will be ignored. The values are used only for the [mapping](http://www.elasticsearch.org/guide/reference/mapping/).

You model will automatically indexed on save, on update and on delete.

### Notes

This plugin does not offers search function, it just index your models. If you want more advanced and complex indexing functions, check out the [other plugin by kvz](https://github.com/kvz/cakephp-elasticsearch-plugin).
