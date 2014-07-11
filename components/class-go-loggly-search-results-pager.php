<?php

/**
 * Retrieve and optionally paginate through multiple pages of search results.
 *  - Used for lists that may require multiple API calls to retrieve all the results.
 *
 * The pager moves forward only and can rewind to the first item.
 */
class GO_Loggly_Search_Results_Pager implements Iterator
{
	private $_position = 0;     // position within the current page
	private $_page = 0;         // current page number
	private $_count = 0;        // total number of records
	private $_page_count = 0;   // total number of pages
	private $_objects = array();// current page of records
	private $_uri;              // current endpoint URI
	private $_orig_query_args;  // original arguments to the query, if any
	private $_next;             // the next page number, if any

	public function __construct( $uri, $args = NULL )
	{
		$this->_uri = $uri;
		$this->_orig_query_args = $args;

		// get the pager to fetch results
		$this->load();
	}//end __construct

	/**
	 * Number of records in this list.
	 * @return integer number of records in list
	 */
	public function count()
	{
		return $this->_count;
	}//end count

	/**
	 * Rewind to the beginning
	 */
	public function rewind()
	{
		$this->_position = 0;
		$this->_page = 0;
		$this->load();
	}//end rewind

	/**
	 * The current object
	 * @return string the current object (json representation)
	 */
	public function current()
	{
		if ( empty( $this->_count ) )
		{
			return NULL;
		}//end if

		while ( $this->_position >= count( $this->_objects ) )
		{
			if ( NULL === $this->_next )
			{
				return NULL;
			}//end if

			if ( $this->_next != NULL && $this->_next <= $this->_page_count )
			{
				$num_objects = count( $this->_objects );
				$this->load( $this->_next, $this->_orig_query_args ); // load the next page
				$this->_position -= $num_objects;
			}//end if
		}//end while

		return $this->_objects[ $this->_position ];
	}// end current

	/**
	 * @return integer current position within the current page
	 */
	public function key()
	{
		return $this->_position;
	}//end key

	/**
	 * Increments the position to the next element
	 */
	public function next()
	{
		++$this->_position;
	}//end next

	/**
	 * @return boolean True if the current position is valid.
	 */
	public function valid()
	{
		return ( isset( $this->_objects[ $this->_position ] ) || $this->_next != NULL );
	}//end valid

	/**
	 * Determine if the http response contains valid, actionable data
	 */
	private function _valid_response( $response )
	{
		return is_array( $response ) && '200' == $response['response']['code'];
	}//end _valid_response

	/**
	 * Set the total number of results in the collection
	 *  - from the 'total_events' field in the fetch result.
	 */
	private function _load_record_count( $total )
	{
		if ( empty( $this->_count ) && $total !== NULL )
		{
			$this->_count = intval( $total );
		}//end if
	}//end _load_record_count

	/**
	 * Set the total number of pages in the collection
	 *  - by dividing the 'total_events' field in the fetch result by the default resultset rows size of 50)
	 */
	private function _load_page_count( $total )
	{
		if ( empty( $this->_page_count ) && $total !== NULL )
		{
			$this->_page_count = floor( intval( $total ) / 50 );
		}//end if
	}//end _load_page_count

	/**
	 * Set the current page number of the active results list
	 *  - by reading the events endpoint's 'page' parameter)
	 * Set the number of the next page in $this->_next, or if no further pages, unset that variable
	 */
	private function _load_current_page_number( $page )
	{
		$this->_page = intval( $page );

		// bump next link if there are more pages:
		if ( $this->_page < $this->_page_count )
		{
			$this->_next = $this->_page + 1;
		}
		else
		{
			$this->_next = NULL;
		}
	}//end _load_current_page_number

	/**
	 * Refresh the current object list with the list in the current page of results
	 */
	private function _load_objects( $objects )
	{
		$this->_objects = $objects;
	}//end _load_objects

	/**
	 * Load another page of results into this pager.
	 *
	 * @param string $next - URI of next end-point to call
	 *  - See Search and Events Endpoint at https://www.loggly.com/docs/api-retrieving-data/
	 *  - Note:
	 *    the events fetch REST api provides 3 arguments: 'page', 'format' and 'columns', we handle 'page' internally,
	 *    and always use json format, therefore we only need support optional 'columns' for Events Endpoint (fetch query) targeting.
	 */
	public function load( $next = NULL )
	{
		// if $next parameter is passed in, use it to call next page,
		// if not, we are in the initial load, where page = 0
		$response = wp_remote_get(
			( $next ) ? $this->_uri . '&page=' . $next : $this->_uri,
			$this->_orig_query_args
		);

		if (
			is_wp_error( $response )
			|| ! $this->_valid_response( $response )
		)
		{
			return new WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
		}//end if

		// read result of REST call:
		$loggly_result = json_decode( $response['body'] );

		// set total number of records in query results:
		$this->_load_record_count( $loggly_result->total_events );

		// set total number of pages in query results by dividing total by default of 50 entries / page
		$this->_load_page_count( $loggly_result->total_events );

		// set the current page:
		$this->_load_current_page_number( $loggly_result->page );

		// set the current page's results into the local collection:
		$this->_load_objects( $loggly_result->events );
	}//end load

	/**
	 * Get the current object list
	 */
	public function get_objects()
	{
		return $this->_objects;
	}//end get_objects
}//end class