<?php

namespace Blocksy;

/**
 * Manages Blocksy theme demo installation and management.
 *
 * ## EXAMPLES
 *
 *     # List all available demos
 *     $ wp blocksy demo list
 *
 *     # Install a demo
 *     $ wp blocksy demo install "Tasty"
 *
 *     # Clean installed demo
 *     $ wp blocksy demo clean
 */
class DemoCli {
	public function __construct() {
		\WP_CLI::add_command('blocksy demo', $this);

		// Non standard commands for demo management have to be added manually.
		\WP_CLI::add_command(
			'blocksy demo import:start',
			[$this, 'demo_import_start']
		);

		\WP_CLI::add_command(
			'blocksy demo import:plugins',
			[$this, 'demo_import_plugins']
		);

		\WP_CLI::add_command(
			'blocksy demo import:options',
			[$this, 'demo_import_options']
		);

		\WP_CLI::add_command(
			'blocksy demo import:widgets',
			[$this, 'demo_import_widgets']
		);

		\WP_CLI::add_command(
			'blocksy demo import:content',
			[$this, 'demo_import_content']
		);

		\WP_CLI::add_command(
			'blocksy demo import:finish',
			[$this, 'demo_import_finish']
		);
	}

	/**
	 * List available demos.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - ids
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Fields to display in the output.
	 * ---
	 * default: name,builder,categories,plugins
	 * options:
	 *  - name
	 *  - builder
	 *  - categories
	 *  - plugins
	 *  - created_at
	 *  - keywords
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo list
	 *     wp blocksy demo list --format=json
	 *
	 * @when after_wp_load
	 * @subcommand list
	 */
	public function demo_list($args, $assoc_args) {
		$demo_data = Plugin::instance()->demo->fetch_all_demos();

		$merged_data = [];

		foreach ($demo_data as $demo) {
			$name = $demo['name'];
			$id = strtolower($name);

			// Initialize array key if it doesn't exist
			if (!isset($merged_data[$id])) {
				$merged_data[$id] = [
					'name' => $name,
					'categories' => [],
					'keywords' => [],
					'created_at' => [],
					'builder' => [],
					'plugins' => [],
				];
			}

			// Safely add data, ensuring uniqueness and handling missing properties
			$merged_data[$id]['categories'] = array_unique(
				array_merge(
					$merged_data[$id]['categories'],
					! empty($demo['categories']) ? $demo['categories'] : []
				)
			);

			$merged_data[$id]['keywords'] = array_unique(
				array_merge(
					$merged_data[$id]['keywords'],
					explode(
						', ',
						! empty($demo['keywords']) ? $demo['keywords'] : ''
					)
				)
			);

			$merged_data[$id]['created_at'][] = ! empty($demo['created_at']) ? $demo['created_at'] : '';

			$merged_data[$id]['builder'][] = ! empty($demo['builder']) ? $demo['builder'] : 'gutenberg';

			$merged_data[$id]['plugins'] = array_unique(
				array_merge(
					$merged_data[$id]['plugins'],
					! empty($demo['plugins']) ? $demo['plugins'] : []
				)
			);
		}

		// Sort alphabetically by name
		usort($merged_data, function($a, $b) {
			return $a['name'] <=> $b['name'];
		});

		// Remove duplicates from non-array fields if necessary and convert arrays to strings
		foreach ($merged_data as $key => $value) {
			$merged_data[$key]['builder'] = implode(', ', array_unique($value['builder']));
			$merged_data[$key]['created_at'] = implode(', ', array_unique($value['created_at']));
			$merged_data[$key]['categories'] = implode(', ', array_unique($value['categories']));
			$merged_data[$key]['plugins'] = implode(', ', array_unique($value['plugins']));
			$merged_data[$key]['keywords'] = implode(', ', array_filter($value['keywords'], function($keyword) { return !empty($keyword); }));
		}

		// Known fields
		$known_fields = ['id', 'name', 'builder', 'plugins', 'categories', 'keywords', 'created_at'];

		// Get the format from the --format flag. Defaults to 'table'.
		$format = \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

		// Get and validate fields from the --fields flag. Defaults to all known fields.
		$fields = array_filter(
			explode(
				',',
				\WP_CLI\Utils\get_flag_value($assoc_args, 'fields', implode(',', $known_fields))
			),
			function($field) use ($known_fields) {
				return in_array($field, $known_fields);
			}
		);

		// Output the data in the specified format.
		\WP_CLI\Utils\format_items($format, $merged_data, $fields);
	}

	/**
	 * Install demo profile.
	 *
	 * ## OPTIONS
	 *
	 * <demo>
	 * : The identifier or name of the demo to install.
	 *
	 * [<builder>]
	 * : The builder to use for the demo. Defaults to 'gutenberg'.
	 *
	 * [--clean]
	 * : Whether to clean the existing content before installing the demo.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo install "Tasty"
	 *     wp blocksy demo install "Tasty" --clean
	 *     wp blocksy demo install "Tasty" elementor --clean
	 *     wp blocksy demo install "Tasty" elementor --clean --yes
	 *
	 * @when after_wp_load
	 * @subcommand install
	 */
	public function demo_install($args, $assoc_args) {
		$clean = \WP_CLI\Utils\get_flag_value($assoc_args, 'clean', false);

		// Get demo profile arguments.
		$demo_args = $this->get_demo_args($args);

		$demo_data = Plugin::instance()->demo->fetch_single_demo([
			'demo' => $demo_args['demo'],
			'builder' => $demo_args['builder']
		]);

		// Check for empty demo.
		if (empty($demo_data)) {
			\WP_CLI::error('Demo not found. Please check the demo name and builder configuration and try again.');
		}

		// Import individual demo components.
		$commands = [
			'blocksy demo import:start' => "Starting demo import for {$demo_args['demo']}...",
			'blocksy demo import:plugins' => 'Importing demo plugins...',
			'blocksy demo import:options' => 'Importing demo options...',
			'blocksy demo import:widgets' => 'Importing demo widgets...',
			'blocksy demo import:content' => 'Importing demo content...',
			'blocksy demo import:finish' => 'Finishing demo import...',
		];

		// Confirm the clean option, run clean command first.
		if ($clean) {
			\WP_CLI::confirm(
				"This option will remove the previous imported content and will perform a fresh and clean install.",
				$assoc_args
			);

			$commands = ['blocksy demo clean' => 'Cleaning up current demo...'] + $commands;
		}

		// Create a progress bar
		$progress = \WP_CLI\Utils\make_progress_bar(
			'Overall Progress',
			count($commands) + 1
		);

		// Run each command in sequence.
		foreach ($commands as $command => $message) {
			\WP_CLI::runcommand($command, [
				'return' => true,
				'launch' => true,
				'exit_error' => false,
				'command_args' => $args
			]);

			// Update the progress bar.
			$progress->tick(1, $message);
		}

		$progress->finish();
		\WP_CLI::success("Import completed.");
	}

	/**
	 * Kickstart the demo import process.
	 *
	 * ## OPTIONS
	 *
	 * <demo>
	 * : The demo name.
	 *
	 * [<builder>]
	 * : The builder name. Default to `gutenberg`.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo import:start my-demo
	 *
	 * @subcommand import:start
	 */
	public function demo_import_start($args, $assoc_args) {
		$args = $this->get_demo_args($args);

		update_option('blocksy_ext_demos_current_demo', [
			'demo' => $args['demo'] . ':' . $args['builder']
		]);

		$demo_content = Plugin::instance()->demo->fetch_single_demo([
			'demo' => $args['demo'],
			'builder' => $args['builder'],
			'field' => 'all'
		]);

		update_option('blocksy_ext_demos_currently_installing_demo', [
			'demo' => json_encode($demo_content)
		]);
	}

	/**
	 * Import the plugins required by the demo.
	 *
	 * ## OPTIONS
	 *
	 * <demo>
	 * : The demo name.
	 *
	 * [<builder>]
	 * : The builder name. Default to `gutenberg`.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo import:plugins my-demo
	 *
	 * @subcommand import:plugins
	 */
	public function demo_import_plugins($args) {
		$args = $this->get_demo_args($args);

		$demo_data = Plugin::instance()->demo->fetch_single_demo([
			'demo' => $args['demo'],
			'builder' => $args['builder']
		]);

		$plugins = new DemoInstallPluginsInstaller([
			'plugins' => implode(':', $demo_data['plugins']),
			'is_ajax_request' => false,
		]);

		$plugins->import();
	}

	/**
	 * Import the options required by the demo.
	 *
	 * ## OPTIONS
	 *
	 * <demo>
	 * : The demo name.
	 *
	 * [<builder>]
	 * : The builder name. Default to `gutenberg`.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo import:options my-demo
	 *
	 * @subcommand import:options
	 */
	public function demo_import_options($args, $assoc_args) {
		$args = $this->get_demo_args($args);

		$options = new DemoInstallOptionsInstaller([
			'demo_name' => $args['demo'] . ':' . $args['builder'],
			'is_ajax_request' => false,
		]);

		$options->import();
	}

	/**
	 * Import the widgets required by the demo.
	 *
	 * ## OPTIONS
	 *
	 * <demo>
	 * : The demo name.
	 *
	 * [<builder>]
	 * : The builder name. Default to `gutenberg`.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo import:widgets my-demo
	 *
	 * @subcommand import:widgets
	 */
	public function demo_import_widgets($args) {
		$args = $this->get_demo_args($args);

		$widgets = new DemoInstallWidgetsInstaller([
			'demo_name' => $args['demo'] . ':' . $args['builder'],
			'is_ajax_request' => false,
		]);

		$widgets->import();
	}

	/**
	 * Import the content required by the demo.
	 *
	 * ## OPTIONS
	 *
	 * <demo>
	 * : The demo name.
	 *
	 * [<builder>]
	 * : The builder name. Default to `gutenberg`.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo import:content my-demo
	 *
	 * @subcommand import:content
	 */
	public function demo_import_content($args) {
		$args = $this->get_demo_args($args);

		$content = new DemoInstallContentInstaller([
			'demo_name' => $args['demo'] . ':' . $args['builder'],
			'is_ajax_request' => false,
		]);

		$content->import();
	}

	/**
	 * Clean the currently installed demo.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo clean
	 *
	 * @subcommand clean
	 */
	public function demo_clean($args) {
		update_option('blocksy_ext_demos_current_demo', null);

		$eraser = new DemoInstallContentEraser([
			'is_ajax_request' => false,
		]);

		$eraser->import();

		\WP_CLI::success("Site cleaned up.");
	}

	/**
	 * Finish the demo import process.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy demo import:finish
	 *
	 * @subcommand import:finish
	 */
	public function demo_import_finish($args) {
		$finish = new DemoInstallFinalActions([
			'is_ajax_request' => false,
		]);

		$finish->import();
	}

	/**
	 * Get demo arguments from CLI arguments.
	 *
	 * @param array $cli_argv The CLI arguments.
	 * @return array The demo arguments.
	 */
	private function get_demo_args($cli_argv) {
		if (empty($cli_argv)) {
			echo 'Please provide demo name.';
			exit;
		}

		if (! isset($cli_argv[1])) {
			$cli_argv[1] = '';
		}

		return [
			'demo' => $cli_argv[0],
			'builder' => $cli_argv[1]
		];
	}
}

