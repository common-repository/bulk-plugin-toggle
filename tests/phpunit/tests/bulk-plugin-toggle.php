<?php

defined( 'ABSPATH' ) or die();

class unprotected_c2c_Bulk_Plugin_Toggle extends c2c_Bulk_Plugin_Toggle {
	public static function activate_plugins( $plugins, $do_redirect = true ) {
		return parent::activate_plugins( $plugins, $do_redirect );
	}

	public static function deactivate_plugins( $plugins ) {
		return parent::deactivate_plugins( $plugins );
	}

	public static function split_plugins( $plugins ) {
		return parent::split_plugins( $plugins );
	}

}

class Bulk_Plugin_Toggle_Test extends WP_UnitTestCase {

	//
	// DATA PROVIDERS
	//


	public static function get_default_hooks() {
		return [
			[ 'action', 'admin_init',                  'remove_query_var',   10 ],
			[ 'filter', 'bulk_actions-plugins',        'add_bulk_toggle',    10 ],
			[ 'filter', 'handle_bulk_actions-plugins', 'handle_bulk_toggle', 10 ],
			[ 'action', 'pre_current_active_plugins',  'add_admin_notice',   10 ],
		];
	}


	//
	//
	// TESTS
	//
	//


	public function test_plugin_version() {
		$this->assertEquals( '1.0.2', c2c_Bulk_Plugin_Toggle::version() );
	}

	public function test_class_exists() {
		$this->assertTrue( class_exists( 'c2c_Bulk_Plugin_Toggle' ) );
	}

	public function test_hooks_plugins_loaded_to_initialize() {
		$this->assertEquals( 10, has_action( 'plugins_loaded', [ 'c2c_Bulk_Plugin_Toggle', 'init' ] ) );
	}

	/*
	 * init()
	 */

	/**
	 * @dataProvider get_default_hooks
	 */
	public function test_default_hooks( $hook_type, $hook, $function, $priority = 10 ) {
		$callback = 0 === strpos( $function, '__' ) ? $function : [ 'c2c_Bulk_Plugin_Toggle', $function ];

		$prio = $hook_type === 'action' ?
			has_action( $hook, $callback ) :
			has_filter( $hook, $callback );

		$this->assertNotFalse( $prio );
		if ( $priority ) {
			$this->assertEquals( $priority, $prio );
		}
	}

	/*
	 * remove_query_var()
	 */

	public  function test_remove_query_var() {
		$_SERVER['REQUEST_URI'] = 'http://example.com/wp-admin/plugins.php?toggle-multi=true&plugin_status=all&paged=1&s=';

		c2c_Bulk_Plugin_Toggle::remove_query_var();

		$this->assertEquals(
			'http://example.com/wp-admin/plugins.php?plugin_status=all&paged=1&s',
			$_SERVER['REQUEST_URI']
		);
	}

	public  function test_remove_query_var_when_query_var_not_present() {
		$expected = 'http://example.com/wp-admin/plugins.php?activate-multi=true&plugin_status=all&paged=1';
		$_SERVER['REQUEST_URI'] = $expected;

		c2c_Bulk_Plugin_Toggle::remove_query_var();

		$this->assertEquals( $expected, $_SERVER['REQUEST_URI'] );
	}

	/*
	 * check_user_permissions()
	 */

	public function test_check_user_permissions_with_user_without_any_caps() {
		$user_id = $this->factory->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( c2c_Bulk_Plugin_Toggle::check_user_permissions( false ) );
	}

	public function test_check_user_permissions_with_user_with_activate_plugins() {
		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );

		wp_set_current_user( $user->ID );

		$this->assertTrue( c2c_Bulk_Plugin_Toggle::check_user_permissions( false ) );
		$this->assertTrue( c2c_Bulk_Plugin_Toggle::check_user_permissions( true ) );
	}

	public function test_check_user_permissions_with_user_with_deactivate_plugins() {
		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );

		wp_set_current_user( $user->ID );

		$this->assertTrue( c2c_Bulk_Plugin_Toggle::check_user_permissions( false ) );
		$this->assertTrue( c2c_Bulk_Plugin_Toggle::check_user_permissions( true ) );
	}

	public function test_check_user_permissions_with_all_caps() {
		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );
		$user->add_cap( 'deactivate_plugins' );

		wp_set_current_user( $user->ID );

		$this->assertTrue( c2c_Bulk_Plugin_Toggle::check_user_permissions( false ) );
		$this->assertTrue( c2c_Bulk_Plugin_Toggle::check_user_permissions( true ) );
	}

	/**
	 * @expectedException WPDieException
	 */
	public function test_check_user_permissions_with_die_for_user_without_permissions() {
		$user_id = $this->factory->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $user_id );
		$expected = 'Sorry, you are not allowed to activate plugins for this site.';

		c2c_Bulk_Plugin_Toggle::check_user_permissions( true );
	}

	/*
	 * add_bulk_toggle()
	 */

	public function test_add_bulk_toggle_default() {
		unset( $_GET['plugin_status'] );

		$actions  = [ 'cat' => 'Cat', 'all' => 'All' ];
		$expected = [ 'cat' => 'Cat', 'all' => 'All', 'toggle-selected' => 'Toggle' ];

		$this->assertSame(
			$expected,
			c2c_Bulk_Plugin_Toggle::add_bulk_toggle( $actions )
		);
	}

	public function test_add_bulk_toggle_for_explicit_valid_plugin_status() {
		$_GET['plugin_status'] = 'all';
		$actions  = [ 'cat' => 'Cat', 'all' => 'All' ];
		$expected = [ 'cat' => 'Cat', 'all' => 'All', 'toggle-selected' => 'Toggle' ];

		$this->assertSame(
			$expected,
			c2c_Bulk_Plugin_Toggle::add_bulk_toggle( $actions )
		);

		$_GET['plugin_status'] = 'upgrade';

		$this->assertSame(
			$expected,
			c2c_Bulk_Plugin_Toggle::add_bulk_toggle( $actions )
		);
	}

	public function test_add_bulk_toggle_for_invalid_actions() {
		$_GET['plugin_status'] = 'draft';
		$expected = [ 'cat' => 'Cat', 'dog' => 'Dog' ];

		$this->assertSame( $expected, c2c_Bulk_Plugin_Toggle::add_bulk_toggle( $expected ) );
	}

	public function test_add_bulk_toggle_does_not_duplicate() {
		$_GET['plugin_status'] = 'all';
		$expected = [ 'cat' => 'Cat', 'dog' => 'Dog', 'toggle-selected' => 'Toggle' ];

		$this->assertSame( $expected, c2c_Bulk_Plugin_Toggle::add_bulk_toggle( $expected ) );
	}

	/*
	 * activate_plugins()
	 */

	public function test_activate_plugins() {
		deactivate_plugins( 'hello.php' );
		$this->assertFalse( is_plugin_active( 'hello.php' ) );

		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );
		wp_set_current_user( $user->ID );

		$this->assertTrue( unprotected_c2c_Bulk_Plugin_Toggle::activate_plugins( [ 'hello.php' ], false ) );
		$this->assertTrue( is_plugin_active( 'hello.php' ) );

		deactivate_plugins( 'hello.php' );
	}

	public function test_activate_plugins_on_already_activated_plugin() {
		activate_plugins( 'hello.php' );
		$this->assertTrue( is_plugin_active( 'hello.php' ) );

		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );
		$user->add_cap( 'deactivate_plugins' );
		wp_set_current_user( $user->ID );

		$this->assertFalse( unprotected_c2c_Bulk_Plugin_Toggle::activate_plugins( [ 'hello.php' ], false ) );
		$this->assertTrue( is_plugin_active( 'hello.php' ) );

		deactivate_plugins( 'hello.php' );
	}

	/*
	 * deactivate_plugins()
	 */

	public function test_deactivate_plugins() {
		activate_plugins( 'hello.php' );
		$this->assertTrue( is_plugin_active( 'hello.php' ) );

		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );
		wp_set_current_user( $user->ID );

		$this->assertTrue( unprotected_c2c_Bulk_Plugin_Toggle::deactivate_plugins( [ 'hello.php' ] ) );
		$this->assertFalse( is_plugin_active( 'hello.php' ) );

		deactivate_plugins( 'hello.php' );
	}

	public function test_activate_plugins_on_already_deactivated_plugin() {
		deactivate_plugins( 'hello.php' );
		$this->assertFalse( is_plugin_active( 'hello.php' ) );

		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );
		$user->add_cap( 'deactivate_plugins' );
		wp_set_current_user( $user->ID );

		$this->assertFalse( unprotected_c2c_Bulk_Plugin_Toggle::deactivate_plugins( [ 'hello.php' ] ) );
		$this->assertFalse( is_plugin_active( 'hello.php' ) );

		deactivate_plugins( 'hello.php' );
	}

	/*
	 * split_plugins()
	 */

	public function test_split_plugins_on_only_active_plugin() {
		$plugins = [ 'hello.php' ];
		deactivate_plugins( $plugins );
		$this->assertFalse( is_plugin_active( 'hello.php' ) );

		$this->assertSame(
			[ $plugins, [] ],
			unprotected_c2c_Bulk_Plugin_Toggle::split_plugins( $plugins )
		);
	}

	public function test_split_plugins_on_only_inactive_plugin() {
		$plugins = [ 'hello.php' ];
		activate_plugins( $plugins );
		$this->assertTrue( is_plugin_active( 'hello.php' ) );

		$this->assertSame(
			[ [], $plugins ],
			unprotected_c2c_Bulk_Plugin_Toggle::split_plugins( $plugins )
		);
	}

	/*
	 * handle_bulk_toggle()
	 */

	public function test_handle_bulk_toggle_with_invalid_action( ) {
		$this->assertEquals( 'something', c2c_Bulk_Plugin_Toggle::handle_bulk_toggle( 'something', 'activate-selected', [ 'pluginA', 'pluginB' ] ) );
	}

	public function test_handle_bulk_toggle_with_no_selections( ) {
		$this->assertEquals( 'something', c2c_Bulk_Plugin_Toggle::handle_bulk_toggle( 'something', 'toggle-selected', [] ) );
	}

	/**
	 * @expectedException WPDieException
	 */
	public function test_handle_bulk_toggle_with_invalid_user_permissions( ) {
		$user_id = $this->factory->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $user_id );
		$expected = 'Sorry, you are not allowed to activate plugins for this site.';

		c2c_Bulk_Plugin_Toggle::handle_bulk_toggle( 'something', 'toggle-selected', [ 'pluginA', 'pluginB' ] );
	}

	public function test_handle_bulk_toggle( ) {
		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );
		$user->add_cap( 'deactivate_plugins' );
		wp_set_current_user( $user->ID );

		$this->assertEquals(
			'http://example.org/wp-admin/plugins.php?toggle-multi=true&plugin_status=&paged=&s=',
			c2c_Bulk_Plugin_Toggle::handle_bulk_toggle( 'something', 'toggle-selected', [ 'pluginA', 'pluginB' ] )
		);
	}

	public function test_handle_bulk_toggle_with_globals( ) {
		global $page, $s, $status;

		$s = 'cat';
		$page = 1;
		$status = 'upgrade';

		$user = $this->factory->user->create_and_get( [ 'role' => 'author' ] );
		$user->add_cap( 'activate_plugins' );
		$user->add_cap( 'deactivate_plugins' );
		wp_set_current_user( $user->ID );

		$this->assertEquals(
			'http://example.org/wp-admin/plugins.php?toggle-multi=true&plugin_status=upgrade&paged=1&s=cat',
			c2c_Bulk_Plugin_Toggle::handle_bulk_toggle( 'something', 'toggle-selected', [ 'pluginA', 'pluginB' ] )
		);
	}

	/*
	 * add_admin_notice()
	 */

	public function test_add_admin_notice() {
		$this->expectOutputRegex( '~^$~', c2c_Bulk_Plugin_Toggle::add_admin_notice() );
	}

	public function test_add_admin_notice_success() {
		$_GET['toggle-multi'] = '1';
		$expected = '<div id="message" class="updated notice is-dismissible"><p>Selected plugins toggled.</p></div>' . "\n";

		$this->expectOutputRegex( '~^' . preg_quote( $expected ) . '$~', c2c_Bulk_Plugin_Toggle::add_admin_notice() );
	}

}
