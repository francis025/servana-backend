<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;

class Filters extends BaseConfig
{
	/**
	 * Configures aliases for Filter classes to
	 * make reading things nicer and simpler.
	 *
	 * @var array
	 */
	public $aliases = [
		'csrf'     => CSRF::class,
		'toolbar'  => DebugToolbar::class,
		'honeypot' => Honeypot::class,
		'admin_sanitizer' => \App\Filters\AdminPanelSanitizer::class, // Legacy - kept for backward compatibility
		'global_sanitizer' => \App\Filters\GlobalSanitizer::class, // New robust global sanitizer
		'auth'     => \App\Filters\AuthFilter::class,
		'protected' => \App\Filters\ProtectedRouteFilter::class,
		'ImageFallback' => \App\Filters\ImageFallback::class,
		'language' => \App\Filters\LanguageFilter::class,
		'output_escaper' => \App\Filters\OutputEscaper::class,
		'cors' => \App\Filters\Cors::class,

	];

	/**
	 * List of filter aliases that are always
	 * applied before and after every request.
	 *
	 * @var array
	 */
	public $globals = [
		'before' => [

			// 'csrf' =>[
			// 	'except' =>[
			// 		"/api/[a-z0-9_-]+/[a-z0-9_-]+",
			// 		"/partner/api/[a-z0-9_-]+/[a-z0-9_-]+",
			// 	]
			// ],
			// Global sanitizer - applies to all routes for XSS protection
			// Sanitizes all GET and POST inputs before controllers receive them
			'global_sanitizer' => [
				'except' => [
					'login/*',
					'logout/*',
					'/api/*', // Exclude API routes if they handle their own sanitization
					'/partner/api/*', // Exclude partner API routes
				]
			],
			'cors',
		],
		'after'  => [
			'ImageFallback',
			// OutputEscaper filter disabled - it's too aggressive and breaks legitimate HTML/JavaScript
			// Input sanitization is already handled by AdminPanelSanitizer and ProtectedRouteFilter
			// 'output_escaper' => [
			// 	'except' => [
			// 		'/api/*',
			// 		'/partner/api/*',
			// 		'/payment/*',
			// 		'/update_subscription_status',
			// 	]
			// ],
			'toolbar' => [
				'except' => [
					"/api/webhooks/*",
					// "/partner/api/[a-z0-9_-]+/[a-z0-9_-]+",
				]
			],
			'cors',

		],
	];

	/**
	 * List of filter aliases that works on a
	 * particular HTTP method (GET, POST, etc.).
	 *
	 * Example:
	 * 'post' => ['csrf', 'throttle']
	 *
	 * @var array
	 */
	public $methods = [
		// 'post' => ['throttle'],
		// 'get' => ['throttle'],

	];

	/**
	 * List of filter aliases that should run on any
	 * before or after URI patterns.
	 *
	 * Example:
	 * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
	 *
	 * @var array
	 */
	public $filters = [];
}
