<?php

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

add_filter('do_parse_request', function($do_parse, $wp) {
    $router = Flight::router();
    $request = Flight::request();
    $flight_options = get_option('flight_integration_options', []);
	$app_folder_path = $flight_options['app_folder_path'] ?? dirname(ABSPATH) . '/app';
    $terminate_request = $flight_options['terminate_request'] ?? true;
	if(file_exists($app_folder_path . '/config/bootstrap.php')) {
		// Load the bootstrap file if it exists
		require $app_folder_path . '/config/bootstrap.php';
	} else {
		// if you want to manual configure your stuff, you can do that here.
	}

    $route = $router->route($request);
    if ($route !== false) {

        // Start Flight to handle the request
        Flight::start();

        // Terminate or continue based on setting
        if ($terminate_request === true) {
            exit;
        }
        
        // If we don't terminate, we tell WordPress to continue processing
        return $do_parse;
    }

    // No Flight route matched; let WordPress handle it
    return $do_parse;
}, 10, 2);

// Admin settings page
add_action('admin_menu', function() {
    add_options_page(
        'Integration for Flight Framework Settings',
        'Integration for Flight Framework',
        'manage_options',
        'flight-integration',
        'flight_integration_settings_page'
    );
});

// Register settings
add_action('admin_init', function() use ($flight_options) {

    register_setting('flight_integration_group', 'flight_integration_options', [
        'sanitize_callback' => 'flight_integration_sanitize_options'
    ]);

    add_settings_section('flight_integration_main', 'Main Settings', function() {
        echo '<p>Configure how Flight integrates with WordPress.</p>';
    }, 'flight-integration');

    add_settings_field('vendor_path', 'Vendor Autoload Path', function() use ($flight_options) {
        echo '<input type="text" name="flight_integration_options[vendor_path]" value="' . esc_attr($flight_options['vendor_path']) . '" class="regular-text">';
        echo '<p class="description">Path to Composer\'s autoload.php (e.g., ' . esc_html(FLIGHT_INTEGRATION_DIR) . 'vendor/autoload.php).</p>';
    }, 'flight-integration', 'flight_integration_main');
	
    add_settings_field('app_folder_path', 'app/ Folder Path', function() use ($flight_options) {
        echo '<input type="text" name="flight_integration_options[app_folder_path]" value="' . esc_attr($flight_options['app_folder_path']) . '" class="regular-text">';
        echo '<p class="description">Path to your custom Flight code resides (e.g., ' . esc_html(dirname(ABSPATH)) . '/app). This is usually the folder where you store your controllers, helper classes, views, etc.</p>';
		echo '<p class="description">You can create this folder structure by clicking the button below. If you do not want to create this folder structure, you can manually edit the plugin file at <code>'.esc_html(FLIGHT_INTEGRATION_DIR).'integration-for-flight-framework.php</code>, but it is highly recommended to create an app/ folder so your settings aren\'t overwritten by future plugin updates.</p>';
        echo '<button type="button" id="create_folder_structure" class="button button-secondary">Create Folder Structure</button>';
        echo '<span id="folder_structure_result" style="margin-left: 10px;"></span>';
        
        // Add JavaScript for the button
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#create_folder_structure').on('click', function() {
                    var button = $(this);
                    var resultSpan = $('#folder_structure_result');
                    var appPath = $('input[name="flight_integration_options[app_folder_path]"]').val();
                    
                    if (!appPath) {
                        resultSpan.html('<span style="color:red;">Please enter a valid app folder path first.</span>');
                        return;
                    }
                    
                    button.prop('disabled', true);
                    resultSpan.html('<span style="color:#666;">Creating folder structure...</span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flight_create_folder_structure',
                            app_path: appPath,
                            nonce: '<?php echo esc_js(wp_create_nonce('flight_create_folders')); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultSpan.html('<span style="color:green;">' + response.data.message + '</span>');
                            } else {
                                resultSpan.html('<span style="color:red;">' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            resultSpan.html('<span style="color:red;">Error occurred while creating folders.</span>');
                        },
                        complete: function() {
                            button.prop('disabled', false);
                        }
                    });
                });
            });
        </script>
        <?php
    }, 'flight-integration', 'flight_integration_main');
    
    add_settings_field('terminate_request', 'Terminate Request', function() use ($flight_options) {
        $checked = isset($flight_options['terminate_request']) && $flight_options['terminate_request'] ? 'checked' : '';
        echo '<input type="checkbox" name="flight_integration_options[terminate_request]" value="1" ' . esc_html($checked) . '>';
        echo '<p class="description">When checked, Flight will terminate the request after processing (recommended). When unchecked, WordPress will continue processing after Flight handles the request.</p>';
    }, 'flight-integration', 'flight_integration_main');
    
    // Add new setting for WordPress DB connection
    add_settings_field('use_wp_db', 'Use WordPress DB Connection', function() use ($flight_options) {
        $checked = isset($flight_options['use_wp_db']) && $flight_options['use_wp_db'] ? 'checked' : '';
        echo '<input type="checkbox" name="flight_integration_options[use_wp_db]" value="1" ' . esc_html($checked) . '>';
        echo '<p class="description">When checked, Flight will use the WordPress database connection at <code>Flight::db()</code>. Uncheck if you want to configure a separate database connection in your Flight application.</p>';
    }, 'flight-integration', 'flight_integration_main');
});

// Settings page rendering
function flight_integration_settings_page() {
    ?>
    <div class="wrap">
        <h1>Flight Framework Integration Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('flight_integration_group');
            do_settings_sections('flight-integration');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Sanitize settings input
function flight_integration_sanitize_options($input) {
    $sanitized = [];
    $sanitized['vendor_path'] = sanitize_text_field($input['vendor_path']);
    $sanitized['app_folder_path'] = sanitize_text_field($input['app_folder_path']);
    $sanitized['terminate_request'] = isset($input['terminate_request']) ? (bool) $input['terminate_request'] : false;
    $sanitized['use_wp_db'] = isset($input['use_wp_db']) ? (bool) $input['use_wp_db'] : false;
    return $sanitized;
}

// Add AJAX handler for creating folder structure
add_action('wp_ajax_flight_create_folder_structure', 'flight_create_folder_structure');

function flight_create_folder_structure() {
	global $wp_filesystem;
	if (empty($wp_filesystem)) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
	}

	$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

    // Verify nonce for security
    if (!$nonce || !wp_verify_nonce($nonce, 'flight_create_folders')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
    }
    
    // Get the app path from the form
    $app_path = isset($_POST['app_path']) ? sanitize_text_field(wp_unslash($_POST['app_path'])) : '';
    
    if (empty($app_path)) {
        wp_send_json_error(['message' => 'App path cannot be empty.']);
    }
    
    // Create directories
    $directories = [
        'controllers',
        'middlewares',
        'config',
        'views'
    ];
    
    $created_files = [];
    $errors = [];
    
    // Create main app directory if it doesn't exist
    if (!file_exists($app_path)) {
        if (!$wp_filesystem->mkdir($app_path, 0755, true)) {
            wp_send_json_error(['message' => 'Failed to create app directory.']);
        }
    }
    
    // Create subdirectories
    foreach ($directories as $dir) {
        $dir_path = $app_path . '/' . $dir;
        if (!file_exists($dir_path)) {
            if ($wp_filesystem->mkdir($dir_path, 0755, true)) {
                $created_files[] = $dir_path;
            } else {
                $errors[] = "Failed to create directory: {$dir}";
            }
        }
    }
    
    // Create sample files
    $files = [
        'controllers/ApiSampleController.php' => '<?php
/**
 * Sample API Controller
 */
namespace app\controllers;

use Flight;

class ApiSampleController {
    
    /**
     * Hello world example
     */
    public function hello() {
		global $wpdb;
		// Example of using WordPress database connection
		$posts = [];
		if(Flight::get(\'flight_options\')[\'use_wp_db\']) {
			$posts = Flight::db()->query("SELECT * FROM {$wpdb->prefix}posts")->fetchAll();
		}
        Flight::json([
            "message" => "Hello from Flight API! Here\'s all the wordpress posts",
			"posts" => $posts
        ], 200, true, \'utf8\', JSON_PRETTY_PRINT);
    }
    
    /**
     * Return the current time
     */
    public function time() {
        Flight::json([
            "time" => date("Y-m-d H:i:s"),
            "timestamp" => time()
        ]);
    }
}',
        'middlewares/SampleMiddleware.php' => '<?php
/**
 * Sample Middleware
 */
namespace app\middlewares;

class SampleMiddleware {
    
    /**
     * Example middleware function
     *
     * @param mixed $params Parameters passed from route
     * @return mixed
     */
    public function before($params) {
        // Do something before the route executes
        // For example, check authentication
        echo \'Middleware before route execution<br>\';
    }
}',
        'config/config.php' => '<?php
/**
 * Flight Configuration
 * 
 * @var array $config
 * @var array $flight_options
 */

// This loads the app directory
Flight::path(dirname($flight_options[\'app_folder_path\']));

Flight::set(\'flight_options\', $flight_options);

return [
    // Add other configuration here
];',
		'config/services.php' => '<?php
/**
 * Flight Services File
 * 
 * @var array $config
 * @var array $flight_options
 */

// This file is loaded after the configuration and before the routes
// Here you can define your services, database connections, etc.

// Set up Flight\'s PDO connection using WordPress if enabled
if (!empty($flight_options[\'use_wp_db\'])) {
    Flight::map(\'db\', function() {
        global $wpdb;
        return new \flight\database\PdoWrapper(
            "mysql:host={$wpdb->dbhost};dbname={$wpdb->dbname}",
            $wpdb->dbuser,
            $wpdb->dbpassword,
            [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES => false,
				PDO::ATTR_STRINGIFY_FETCHES => false,
			]
        );
    });
}
',
        'config/routes.php' => '<?php
/**
 * Flight Routes
 * 
 * @var array $config
 * @var array $flight_options
 */

use app\controllers\ApiSampleController;
use app\middlewares\SampleMiddleware;

// Define your routes
// Here\'s some sample routes for you
Flight::route("GET /api/hello", [ ApiSampleController::class, "hello" ]);
Flight::route("GET /api/time", [ ApiSampleController::class, "time" ]);

// Example with middleware
Flight::route("GET /secure/resource", function() {
    echo "Secure Resource";
})->addMiddleware(new SampleMiddleware);',
        'config/bootstrap.php' => '<?php
/**
 * Flight Bootstrap File
 * This file is loaded when Flight starts and configures the application
 */

// Load configuration
$config = include __DIR__ . "/config.php";

// Load Services
// Here you can load your services, database connections, etc.
require __DIR__ . "/services.php";

// Load routes
require __DIR__ . "/routes.php";'
    ];
    
    foreach ($files as $file => $content) {
        $file_path = $app_path . '/' . $file;
        $dir_name = dirname($file_path);
        
        // Ensure directory exists
        if (!file_exists($dir_name)) {
            if (!$wp_filesystem->mkdir($dir_name, 0755, true)) {
                $errors[] = "Failed to create directory: " . $dir_name;
                continue;
            }
        }
        
        // Write file
        if (file_put_contents($file_path, $content) !== false) {
            $created_files[] = $file;
        } else {
            $errors[] = "Failed to create file: " . $file;
        }
    }
    
    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Some errors occurred: ' . implode(', ', $errors)
        ]);
    } else {
        wp_send_json_success([
            'message' => 'Folder structure created successfully!',
            'files' => $created_files
        ]);
    }
}