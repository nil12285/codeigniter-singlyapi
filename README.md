codeigniter-singlyapi
=====================

Singly API Implementation for Codeigniter.

## Requirements

1. PHP 5.1+
2. CodeIgniter 1.7.x - 2.0-dev
3. PHP 5 (configured with cURL enabled)
4. libcurl

## Examples

	// Load library
	$this->load->library('singly'); 
	
	// Authenticate a service with Singly
	// -- Pull from database, or storage
	$sTokenSingly = ''; 
	// -- Authenticate
	$this->singly->authenticate('twitter', $sTokenSingly, site_url('/callback/singly'));
	
	// Get tweets
	// -- Pull from database, or storage
	$sTokenSingly = ''; 
	// -- Call
	$aItems = $this->singly->call('services/twitter/tweets', sTokenSingly array('limit' => 100, 'map' => true));
	if (empty($aItems) || !is_array($aItems)) {
		throw new Exception('There was an error.');
	}
	
	// Posting to connected accounts
	// -- Pull from database, or storage
	$sTokenSingly = ''; 
	// -- Post
	$aParams = array(
		'services'  => 'twitter,facebook',
		'url'       => 'http://mattrmiller.com',
		'body'      => 'This is my message.'
	);
	// -- Post
	$oRes = $this->singly->call('/types/news', $sTokenSingly, $aParams, Singly::MODE_POST);
	// -- Check
	if (empty($oRes)) {
		throw new Exception('There was an error.');
	}
	