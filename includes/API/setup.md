# How to authenticate with the API using JWT Authentication

## Installation
* Install the plugin [JWT Authentication for WP REST API](https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/) and activate it.
* Set `JWT_AUTH_SECRET_KEY` in `wp-config.php` to a secret key of your choice.
 ```php
 define('JWT_AUTH_SECRET_KEY', 'your-top-secret-key');
 ```
* Send a POST request to `http://your-site.com/wp-json/jwt-auth/v1/token` with the following parameters:
  * `username` - The username of the user you want to authenticate.
  * `password` - The password of the user you want to authenticate.
* The response will be a JSON object with the following properties:
  * `token` - The JSON Web Token that you can use to authenticate requests.
  * `user_email` - The email of the user you authenticated.
  * `user_nicename` - The nice name of the user you authenticated.
  * `user_display_name` - The display name of the user you authenticated.

## Usage
* Save the token you received from the authentication request in a variable.
 * Set headers for your request:
 ```php
 $headers = array(
   'Authorization' => 'Bearer ' . $token
 );
 ```
