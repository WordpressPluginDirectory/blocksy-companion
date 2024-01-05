<?php

function blc_get_capabilities() {
	static $capabilities = null;

	if ($capabilities === null) {
		$capabilities = new Blocksy\Capabilities();
	}

	return $capabilities;
}

function blc_can_use_premium_code() {
	return !! class_exists('Blocksy\Premium');
}

function blc_site_has_feature($feature = 'base_pro') {
	return (
		blc_can_use_premium_code()
		&&
		blc_get_capabilities()->has_feature($feature)
	);
}

// https://developer.wordpress.org/reference/functions/is_ssl/
function blc_maybe_is_ssl() {
	// cloudflare
	if (! empty($_SERVER['HTTP_CF_VISITOR'])) {
		$cfo = json_decode($_SERVER['HTTP_CF_VISITOR']);

		if (isset($cfo->scheme) && 'https' === $cfo->scheme) {
			return true;
		}
	}

	// other proxy
	if (
		! empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
		&&
		'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']
	) {
		return true;
	}

	return function_exists('is_ssl') ? is_ssl() : false;
}

// Don't use protocol relative URL, it's an anti pattern.
// https://www.paulirish.com/2010/the-protocol-relative-url/
function blc_normalize_site_url($url) {
	$parsed_url = parse_url($url);

	$protocol = 'http';

	if (blc_maybe_is_ssl()) {
		$protocol .= 's';
	}

	$result = $protocol . '://' . $parsed_url['host'];

	if (isset($parsed_url['port'])) {
		$result = $result . ':' . $parsed_url['port'];
	}

	if (isset($parsed_url['path'])) {
		$result = $result . $parsed_url['path'];
	}

	return $result;
}

function blc_get_ext($id, $args = []) {
	return \Blocksy\Plugin::instance()->extensions->get($id, $args);
}

if (! function_exists('blc_load_xml_file')) {
	function blc_load_xml_file($url, $useragent = '') {
		set_time_limit(300);

		if (ini_get('allow_url_fopen') && ini_get('allow_url_fopen') !== 'Off') {
			$context_options = [
				"ssl" => [
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				]
			];

			if (! empty($useragent)) {
				$context_options['http'] = [
					'user_agent' => $useragent
				];
			}

			return file_get_contents(
				$url, false,
				stream_context_create($context_options)
			);
		} else if (function_exists('curl_init')) {
			$curl = curl_init($url);

			if (! empty($useragent)) {
				curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
			}

			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

			$result = curl_exec($curl);
			curl_close($curl);

			return $result;
		} else {
			throw new Exception("Can't load data.");
		}
	}
}
