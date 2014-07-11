Gigaom Loggly
================

* Tags: wordpress, loggly
* Requires at least: 3.9
* Tested up to: 3.9
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Description
-----------

* Loggly API wrapper. 
* Establishes a connection and pushes custom log events to Loggly.
* Search and retrieve specific events.


Usage Notes
-----------
We will store all log entries in json format. Entries can be either key-value data pairs, or just message strings.

1. Write a log entry ( tags are optional; default will be 'go-loggly' ): 
	* Use an array to store a message consisting of key-value pairs:
		* `go_loggly()->inputs( array( 'message' => 'hello world!', 'from' => 'gomtest' ), TAGS )`
		* the message will be stored in `message` and `from` json fields on loggly.com
	* Log an unstructured string message:
		* `go_loggly()->inputs( 'hello world', TAGS )`
		* the message will be stored in the `message` field in json format on loggly.com

2. Retrieve a log entry:
	* Use a simple query string to search for log entries:
		* `$response = go_loggly()->search( '*' );`
		* `$response` will be an iterator containing the full results of the search, or a `WP_Error`. 
		* Paging through the results (which are returned by Loggly in pages of 50 rows) 
		is handled by the iterator that the ``go-loggly()->search()` API returns.
	* Use an array to search for log entries with valid search facets:
		* <pre>$response = go_loggly()->search(
               array(
                   'q'     => '*',
                    'from'  => '-27d',
                    'until' => 'now',
                    'size'  => '4',
                )
            );</pre>
		* valid search parameters shown [here](https://www.loggly.com/docs/api-retrieving-data/)

Hacking notes
-------------
1. The `go-loggly` plugin only returns json formatted responses.
2. We are not supporting every feature of the Loggly APIs, for example, one could filter the log entries returned by a search 
call by passing a value for the `columns` parameter in the [retrieval API](https://www.loggly.com/docs/api-retrieving-data/),
but they don't actually support that for json formatted responses.

