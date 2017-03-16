# Post Deploy Actions for Laravel
Easily run environment specific post deploy actions for any Laravel project (>= version 5.1)

## Installation
Install the package via composer:
```
$ composer require dblencowe/post-deploy-actions
```

Register the service provider with your app:
```php
// config/app.php
'providers' => [
    ...
    Dblencowe\PostDeploy\PostDeployServiceProvider::class,
]
```

Publish vendor files to add the migrations:
```bash
php artisan vendor:publish --tag=migrations
```

Create the database table that tracks run actions:
```bash
php artisan migrate
```

## Usage
To create a Post Deployment command use artisans make command. Not specifying an environment will add a global action which will be run on all.
```bash
php artisan make:deploy_action name --env=ENVIRONMENT
```

To run outstanding actions there is an artisan command:
```bash
php artisan postdeploy:run
```