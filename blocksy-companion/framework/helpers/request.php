<?php

namespace Blocksy;

class WpRemoteRequest {
	public function request($url, $args = []) {
		$request = wp_remote_get(
			$url,
			[
				'user-agent' => $args['user_agent'],
			]
		);

		if (is_wp_error($request)) {
			return false;
		}

		if (wp_remote_retrieve_response_code($request) !== 200) {
			return false;
		}

		$body = wp_remote_retrieve_body($request);

		if (! $body) {
			return false;
		}

		return $body;
	}
}

class FileGetContentsRequest {
	public function request($url, $args = []) {
		if (
			! ini_get('allow_url_fopen')
			||
			ini_get('allow_url_fopen') === 'Off'
		) {
			return false;
		}

		$context_options = [
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false,
			]
		];

		if (! empty($args['user_agent'])) {
			$context_options['http'] = [
				'user_agent' => $args['user_agent']
			];
		}

		return file_get_contents(
			$url,
			false,
			stream_context_create($context_options)
		);
	}
}

class CurlRequest {
	public function request($url, $args = []) {
		if (! function_exists('curl_init')) {
			return false;
		}

		$curl = curl_init($url);

		if (! empty($args['user_agent'])) {
			curl_setopt($curl, CURLOPT_USERAGENT, $args['user_agent']);
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		$result = curl_exec($curl);
		curl_close($curl);

		return $result;
	}
}

class RequestRemoteUrl {
	private $strategies = [];

	public function __construct() {
		$this->strategies[] = new FileGetContentsRequest();
		$this->strategies[] = new CurlRequest();

		$this->strategies[] = new WpRemoteRequest();
	}

	public function request($url, $args = []) {
		$args = wp_parse_args($args, [
			'user_agent' => '',

			// wp | as-is
			'user_agent_type' => 'as-is'
		]);

		if ($args['user_agent_type'] === 'wp') {
			$args['user_agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url');
		}

		set_time_limit(300);

		foreach ($this->strategies as $strategy) {
			$result = $strategy->request($url, $args);

			if ($result) {
				return $result;
			}
		}

		return null;
	}
}

