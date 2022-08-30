<?php

class RESTLogic
{
	protected $postTypes;

	public function __construct()
	{
		$this->postTypes = get_post_types(['_builtin' => false, 'public' => true], 'names');
	}

	public function register_routes()
	{
		// register_rest_route('some/namespace', '/some-endpoint', [
		// 	'method' => 'GET',
		// 	'callback' => [$this, 'some_method']
		// ]);
	}

	public function register_fields()
	{
		foreach ($this->postTypes as $postType) {
			register_rest_field($postType, 'acf', [
				'get_callback'    => [$this, 'expose_ACF_fields'],
				'schema'          => null,
			]);

			register_rest_field($postType, 'taxonomies', [
				'get_callback'    => [$this, 'expose_taxonomies'],
				'schema'          => null,
			]);
		}
	}

	public function expose_ACF_fields($object)
	{
		return get_fields($object['id']);
	}

	public function expose_taxonomies($object)
	{
		$taxonomies = get_object_taxonomies($object['type']);

		return wp_get_post_terms($object['id'], $taxonomies);
	}
}
