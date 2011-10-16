<?php
/**
 * The Github class is tightly coupled with Request, because of the way this exists as a static factory pattern in Kohana. Therefore we mock the Github
 * class to allow us to intercept the Request object and provide mock responses.
 */
class Mock_Github extends Github
{
	/**
	 * A reference to the most recent request
	 * @var Request
	 */
	public $_test_last_request = null;
	
	/**
	 * Data to be passed to the next API call
	 * @var array
	 */
	protected $_test_response_data = array(
		'body' => null,
		'status' => '200',
		'headers' => array());
	
	/**
	 * Prepares the mock API to return a response
	 * @param string $response_body
	 * @param string $response_status
	 * @param array $response_headers 
	 */
	public function _test_prepare_response($response_body = null, $response_status = '200', $response_headers = array())
	{
		if (is_array($response_body))
		{
			$response_body = json_encode($response_body);
		}
		
		$this->_test_response_data['body'] = $response_body;
		$this->_test_response_data['status'] = $response_status;
		$this->_test_response_data['headers'] = $response_headers;
	}
	
	/**
	 * Injects a request mock into the API execution to allow testing of the request/response flow without interacting with Gith
	 * @param string $url
	 * @return Request 
	 */
	protected function _new_request($url)
	{
		// Mock a request
		$this->_test_last_request = PHPUnit_Framework_MockObject_Generator::getMock(
          'Request',
          array('execute'),
          array($url));
		
		// Setup a response object
		$response = $this->_test_last_request->create_response();
		$response->body($this->_test_response_data['body']);
		$response->status($this->_test_response_data['status']);
		
		// Setting headers one by one ensures that keys are set lowercase
		foreach ($this->_test_response_data['headers'] as $key=>$value)
		{
			$response->headers($key, $value);
		}		
		
		// Configure the request to return the response
		$this->_test_last_request->expects(new PHPUnit_Framework_MockObject_Matcher_InvokedCount(1))
				->method('execute')
				->will(new PHPUnit_Framework_MockObject_Stub_Return($response));
		
		// And pass the request object back into the API class
		return $this->_test_last_request;		
	}
	
}