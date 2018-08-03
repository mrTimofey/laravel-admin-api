API backend for administration panels based on [vue-admin-front](https://github.com/mrTimofey/vue-admin) package.

[Demo](http://admin.shit-free.space).

## Requirements

* PHP 7.1
* Laravel 5

## Install

```bash
npm i -D vue-admin-front
composer require mr-timofey/laravel-admin-api
```

Follow installation instructions from dependent packages:
[mr-timofey/laravel-aio-images](https://github.com/mrTimofey/laravel-aio-images) for images processing,
[mr-timofey/laravel-simple-tokens](https://github.com/mrTimofey/laravel-simple-tokens) for authorization.

**For Laravel <= 5.4** add `MrTimofey\LaravelAdminApi\ServiceProvider` to your `app.providers` config.

```bash
php artisan vendor:publish --provider="MrTimofey\LaravelAdminApi\ServiceProvider"
```

Look to `config/admin_api.php` for further package configuration instructions.

Follow [vue-admin-front quick start guide](https://mr-timofey.gitbooks.io/vue-admin/content/quick-start.html).

For development purpose you may also want to execute `npm i -D concurrently` and add this script to your npm scripts:

```json
{
	"scripts": {
		"dev": "concurrently --kill-others -n \" api ,admin\" -c \"blue,white\" \"php artisan serve\" \"npm run admin:dev\""
	}
}
```

It will run a development PHP server on port 8000 and vue-admin-front dev server on port 8080.

## Authentication and authorization

This package uses [mr-timofey/laravel-simple-tokens](https://github.com/mrTimofey/laravel-simple-tokens)
to maintain authentication and authorization logic.

You can change a guard which is used for API by setting a proper `auth:{guard name}` middleware and guard name
in `admin_api.api_middleware` and `admin_api.api_guard` config respectively.

Remove `auth` middleware if you want to disable authorization.

Also you can completely replace authentication and authorization logic by rebinding auth controller class:
`app()->bind(\MrTimofey\LaravelAdminApi\Http\Controllers\Auth::class, YourController::class)`

## Models configuration

Admin API will try to guess attribute types and generate field names by capitalizing attribute names using model's:
* `$visible` to get a list of fields to display on entity index page (if not set explicitly)
* `$fillable` to get a list of fields for editing (if not set explicitly)
* `$hidden` fields will receive a `password` field type
* `$dates` fields will receive a `datetime` field type
* `$casts` will just set field types as-is (don't worry, `vue-admin-front` supports any Eloquent compatible types by aliasing them)
* belongsTo, hasMany and belongsToMany relations will just work

You can also implement a `MrTimofey\LaravelAdminApi\Contracts\ConfiguresAdminHandler` interface and define a
`configureAdminHandler($handler)` method to make things more controllable.

Available field types and their options are described in
[vue-admin-front field types docs](https://mr-timofey.gitbooks.io/vue-admin/content/fields.html#available-field-types).

Usage example:

```php
<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use MrTimofey\LaravelAdminApi\Contracts\ConfiguresAdminHandler;
use MrTimofey\LaravelAdminApi\ModelHandler;

class Post extends Model implements ConfiguresAdminHandler
{
    protected $casts = [
        'published' => 'bool'
    ];
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
    
    public function scopeOnlyDrafts(Builder $q): Builder
    {
        return $q->whereRaw('draft IS TRUE');
    }
    
    public function configureAdminHandler(ModelHandler $handler): void
    {
        $handler->setTitle('Posts')
            ->setCreateTitle('Creating new post')
            // placeholders can be used, see more: https://mr-timofey.gitbooks.io/vue-admin/placeholders.html
            ->setItemTitle('Editing post #{{ id }}')

            // allow only these methods (everything is allowed by default)
            ->allowActions(['index', 'item', 'create', 'update', 'destroy'])
            // ...or use policies instead
            ->usePolicies(true,
                // optional policy method prefix (Policy::adminActionIndex, Policy::adminActionCreate, etc.)
                'adminAction')

            ->addPreQueryModifier(function(Builder $q, Request $req): void {
                // modify index query just after Model::newQuery() is called
            })
            ->addPostQueryModifier(function(Builder $q,Request $req): void {
                // modify index query just before execution
            })
            // automatically search with LIKE
            ->setSearchableFields(['title', 'summary'])
            // ...or/and set custom search callback
            ->setSearchCallback(function(
                Builder $q,
                Request $req,
                array $searchableFields): void {
                    $q->searchLikeAGod($req->get('search'));
                })
            
            // index page filters
            ->setFilterFields([
                // auto relation filter
                'category',
                // see more about available prefix modifiers in ModelHandler::applyFilters sources
                '>~created_at' => [
                    'title' => 'Created after',
                    'type' => 'datetime'
                ],
                // checkbox, applies scopeOnlyDrafts when checked
                'drafts' => [
                    'scope' => 'onlyDrafts',
                    'type' => 'switcher'
                ]
            ])
            
            ->setIndexFields([
                'id',
                'title',
                // will be automatically formatted as datetime
                'created_at'
            ])
            
            ->setItemFields([
                'title',
                // this just works
                'category', // categories should be added to api_admin.models config
                // this should just work as well but we want some customizations
                'tags' => [
                    'title' => 'Attach tags',
                    // 'type' => 'relation', // not necessary if field name is same as a relation method
                    // 'entity' => 'tags', // tags should be added to api_admin.models config
                    // placeholders can be used, see more: https://mr-timofey.gitbooks.io/vue-admin/placeholders.html
                    'display' => '{{ name }}',
                    'allowCreate' => true,
                    'createField' => 'name',
                    'createDefaults' => ['description' => 'New tag'],
                    'queryParams' => [
                        'sort' => ['sort' => 'asc']
                    ]
                ],
                'content' => ['type' => 'wysiwyg'],
                'published' // $casts will automatically set the right field type for you
            ])
            
            // you can set validation rules
            ->setValidationRules([
                'tags' => ['array', 'between:3,8'],
                'category' => ['required']
            ])
            // ...or/and custom validation callback
            ->setValidationCallback(
                /**
                 * @throws \Illuminate\Validation\ValidationException
                 */
                function(
                    \Illuminate\Http\Request $req,
                    array $rules,
                    array $messages,
                    // gathered from item fields configuration
                    array $titles
                ) {
                    $req->validate([ /* whatever */ ]);
                })
            ->setValidationMessages([
                'category.required' => 'No category - no post'
            ]);
    }
}
```
