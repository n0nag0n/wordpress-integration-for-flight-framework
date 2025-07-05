=== Integration for Flight Framework ===
Contributors: n0nag0n
Tags: framework, flight, rest, api, mvc
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seamlessly integrate the Flight PHP micro-framework with WordPress for building APIs and custom applications.

== Description ==

Integration for Flight Framework enables you to use the lightweight Flight PHP micro-framework within your WordPress site. This plugin bridges the gap between WordPress's content management capabilities and Flight's simple yet powerful routing system for building custom applications and APIs.

= Features =

* **Seamless Integration**: Route requests to either Flight or WordPress based on URL patterns
* **MVC Architecture**: Organize your code with controllers, models, and views
* **Folder Structure Creator**: Easily set up the recommended Flight application structure
* **Database Options**: Use WordPress's database connection or configure your own
* **Configuration Control**: Fine-tune how Flight interacts with WordPress
* **Easy Setup**: Simple admin interface to configure all aspects of the integration

This plugin is perfect for developers who want to build custom applications or APIs within their WordPress site while maintaining a clean, organized codebase.

== Installation ==

1. Upload the `flight-integration` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Flight Framework to configure the plugin
4. Set the vendor path to your Flight installation or use Composer to install Flight
5. Configure your app folder path and create the folder structure
6. Start building your Flight application!

== Frequently Asked Questions ==

= What is Flight PHP framework? =

Flight is a fast, simple, extensible PHP micro-framework. It's designed to be easy to learn and use while still providing the features needed for modern web applications. Learn more at [docs.flightphp.com](https://docs.flightphp.com/en/v3/awesome-plugins/n0nag0n-wordpress).

= Do I need to know Flight to use this plugin? =

Yes, this plugin is intended for developers who want to use Flight within WordPress. Basic knowledge of Flight's routing and request handling is recommended.

= Will this plugin slow down my WordPress site? =

No, the plugin only processes requests that match your Flight routes. All other requests pass through to WordPress as usual with minimal overhead.

= Can I use WordPress functions in my Flight application? =

Yes! You have full access to all WordPress functions, hooks, and the global variables from within your Flight routes and controllers.

= How do I create custom routes? =

Define your routes in the `config/routes.php` file in your app folder. See the sample file created by the folder structure generator for examples.

== Screenshots ==

1. Flight Framework settings page
2. Created folder structure

== Usage Examples ==

= Basic Route Example =

In your app/config/routes.php file:

```php
Flight::route('GET /api/hello', function() {
    Flight::json(['message' => 'Hello World!']);
});
```

= Controller Example =

```php
// app/controllers/ApiController.php
namespace app\controllers;

use Flight;

class ApiController {
    public function getUsers() {
        // Using WordPress functions inside Flight
        $users = get_users();
        $result = [];
        
        foreach($users as $user) {
            $result[] = [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email
            ];
        }
        
        Flight::json($result);
    }
}

// In your routes.php:
Flight::route('GET /api/users', [app\controllers\ApiController::class, 'getUsers']);
```

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Integration for Flight Framework plugin for WordPress.
