# Yet Another Laravel Translator

*Warning: this package is actively developed, be careful*

## Requires
- PHP 7.*
- Laravel 5.* (Only tested with 5.4)

## Installation

### Package

```bash
composer require shanginn/yalt
```
Add the service provider to **config/app.php**

```php
'providers' => [
	//...
	Shanginn\Yalt\YaltServiceProvider::class,
]
```

### Migrations

By default suffix for translatable table is set to `ll`, so for example 
translations table for `things` table should be named `things_lls`.

You can change this suffix in the config file (*translation_suffix*).

```bash
php artisan make:migration create_things_localizations_table
```

Let's assume you need translatable title and description for the `Thing`.
Open up your migration and edit `up` method like this:

```php
// database/migrations/2017_02_20_200652_create_things_localizations_table.php

public function up()
{
	Schema::create('things_lls', function (Blueprint $table) {
		$table->increments('id');
		$table->char('locale', 2)->index();
		$table->integer('thing_id')->index();
		$table->string('title');
		$table->string('description');

		$table->unique(['thing_id', 'locale']);

		$table->foreign('thing_id', 'thing_idx')
			->references('id')->on('things')
			->onDelete('cascade')
			->onUpdate('cascade');
	});
}
```

### Model

All you need to do to make `Thing` model translatable is to import 
Translatable trait, use it and define translatable fields.

```php
// App/Thing.php
//...
use Shanginn\Yalt\Eloquent\Concerns\Translatable;

class Thing extends Model
{
	use Translatable;
	
	protected $translatable = ['title', 'description'];
//...
```

That's it! No models, no relations. Pure magic.
### Middleware

If you want to change current app locale based on 'Accept-Language' header,
 register `locale` [middleware](laravel.com/docs/5.4/middleware) in the `Http/Kernel.php`.

```php
// Http/Kernel.php
//...
protected $routeMiddleware = [
//...
	'locale' => \Shanginn\Yalt\Http\Middleware\Localization::class,
//...
]

```

And apply it to some routes.

## Usage

```php
Thing::create([
	'title' => [ // Explicit locale definition for title
		'en' => 'Title in english',
		'ru' => 'Русский заголовок'
	],
	// Use default locale for description
	'description' => 'Description in the default locale'
])
```

*Sorry. That's it for now. More info coming... Take a look into sources.*

## TODO

- [x] Write basic info here
- [ ] Write more info here
- [ ] Create tests
- [ ] Test all provided functional
- [ ] Add artisan command to create migrations for Translatable models
- [ ] Replace `id` PK with [`item_id`, `locale`] composite PK in _lls table
- [ ] Add ability to choose between *plural* and *single* table suffix (ex. things_lls vs thing_lls)

## Additional info

Based on [dimsav/laravel-translatable](https://github.com/dimsav/laravel-translatable). 
But I was too lazy to create the same model for every translatable thing so I rewrote 
this package almost completely.

Feel free to contribute in any way!