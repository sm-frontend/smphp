<?php
return [
	'router' => [
		'routes' => [
			'home' => [
				'path'   => '/',
				'class'  => 'demo',
				'method' => 'index'
			],
			'common' => [
				'path'   => '/:class/:method',
				'class'  => ':class',
				'method' => ':method'
			]
		],
		'dispatcher' => [
			'suffix'    => '',
			'classpath' => 'App\\Controller'
		],
	],
	'db' => [
		'database_type' => 'sqlite',
		'database_name' => BASE_PATH . '/data/demo.db'
	],
	'view' => [
		'tplPath' => BASE_PATH . '/app/View/'
	]
];
