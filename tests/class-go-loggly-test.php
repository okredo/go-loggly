<?php

/**
 * GO_Loggly unit tests
 */
class GO_Loggly_Test extends WP_UnitTestCase
{
	/**
	 * this is run before each test* function in this class, to set
	 * up the environment each test runs in.
	 */
	public function setUp()
	{
		parent::setUp();
	}//end setUp

	/**
	 * test the GO_Loggly::inputs function with tags for the message
	 * - use an array for the message data (should result in parsed object visible in dashboard)
	 * (See the dashboard at loggly.com for results)
	 * - $response is the standard WP response (array) or WP_Error
	 */
	public function test_loggly_tagged_json_entry()
	{
		$response = go_loggly()->inputs( array( 'message' => 'hello world!', 'from' => 'gomtest' ), array( 'tagA', 'tagB' ) );
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
		{
			$this->assertEquals( 'OK', $response['response']['message'] );
		}
	}//end test_loggly_tagged_json_entry

	/**
	 * test the GO_Loggly::inputs function with null tags
	 * - use an array for the message data ( should result in parsed object in dashboard under 'go-loggly' tag )
	 * (See the dashboard at loggly.com for results)
	 * - $response is the standard WP response (array) or WP_Error
	 */
	public function test_loggly_tagless_json_entry()
	{
		$response = go_loggly()->inputs( array( 'message' => '(Not tagged) hello world!', 'from' => 'gomtest' ) );
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
		{
			$this->assertEquals( 'OK', $response['response']['message'] );
		}
	}//end test_loggly_tagless_json_entry

	/**
	 * test the GO_Loggly::inputs function with tags
	 * - use a string for the message data ( should result in urlencoded string in 'message' field in dashboard, under specified tag(s) )
	 * (See the dashboard at loggly.com for results)
	 * - $response is the standard WP response (array) or WP_Error
	 */
	public function test_loggly_tagged_string_entry()
	{
		$response = go_loggly()->inputs( 'hello world! from gomtest', array( 'tagA', 'tagB' ) );
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
		{
			$this->assertEquals( 'OK', $response['response']['message'] );
		}
	}//end test_loggly_tagged_string_entry

	/**
	 * test the GO_Loggly::inputs function without tags
	 * - use a string for the message data ( should result in urlencoded string in 'message' field in dashboard under 'go-loggly' tag )
	 * (See the dashboard at loggly.com for results)
	 * - $response is the standard WP response (array) or WP_Error
	 */
	public function test_loggly_tagless_string_entry()
	{
		$response = go_loggly()->inputs( '(Not tagged) hello world! from gomtest' );
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
		{
			$this->assertEquals( 'OK', $response['response']['message'] );
		}
	}//end test_loggly_tagless_string_entry

	/**
	 * test the GO_Loggly::search function
	 * - search for all entries, using default search facets:
	 * (Start time for the search: “-24h”;
	 * End time for the search: Defaults to “now”;
	 * Descending order;
	 * Default number of rows returned by search: 50 ... pager handles pulling down the rest.)
	 *
	 * - $response is the iterator class
	 */
	public function test_loggly_search_all_use_defaults()
	{
		// Testing this REST call:
		// http://gomtest.loggly.com/apiv2/search?q=*
		$response = go_loggly()->search( '*' );

		// note: we should expect (if there's data)
		// $response->count() X 3 assertions in the first loop below, followed by
		// $response->count() assertions in the second loop...

		// go-loggly->search() returns either a pager or WP_Error
		if ( ! is_wp_error( $response ) )
		{
			if ( $response->count() > 0 )
			{
				echo $response->count() . ' records returned by search';
				foreach ( $response as $key => $value )
				{
					$this->assertObjectHasAttribute( 'id', $value );
					$this->assertObjectHasAttribute( 'logmsg', $value );
					$this->assertObjectHasAttribute( 'event', $value );
				}// end foreach

				// can iterate again as needed - uses the full collection:
				foreach ( $response as $key => $value )
				{
					$this->assertObjectHasAttribute( 'event', $value );
				}// end foreach
			}//end if
		}//end if
		else
		{
			echo 'Search response failed' . $response->get_error_message();
		}//end else
	}//end test_loggly_search_all_use_defaults

	/**
	 * test the GO_Loggly::search function
	 * - search for all entries, using 'from', 'until' and 'size' 'search' API facets
	 * - $response is the iterator class
	 */
	public function test_loggly_search_all_use_facets()
	{
		// Testing this REST call:
		// http://gomtest.loggly.com/apiv2/search?q=*&from=-7d&until=now&size=4
		$response = go_loggly()->search(
			array(
				'q'     => '*',
				'from'  => '-27d',
				'until' => 'now',
				'size'  => '4',
			)
		);

		// note: for a restricted result size of 4, we should expect (if there's data)
		// 12 assertions in the first loop below, followed by
		// 4 assertions in the second loop...

		// check response for wp_errors:
		if ( ! is_wp_error( $response ) )
		{
			if ( $response->count() > 0 )
			{
				echo $response->count() . ' records returned by search';
				foreach ( $response as $key => $value )
				{
					$this->assertObjectHasAttribute( 'id', $value );
					$this->assertObjectHasAttribute( 'logmsg', $value );
					$this->assertObjectHasAttribute( 'event', $value );
				}// end foreach

				// can iterate again as needed - uses the full collection:
				foreach ( $response as $key => $value )
				{
					$this->assertObjectHasAttribute( 'event', $value );
				}// end foreach
			}//end if
		}//end if
		else
		{
			echo $response->get_error_message();
		}//end else
	}//end test_loggly_search_all_use_facets
}// end GO_Loggly_Test