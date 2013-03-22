<?php

/**
 * CodeIgniter Singly
 *
 * A basic library to support Singly's API
 *
 * @package		Singly
 * @author		smartbits.co llc.
 * @copyright	Copyright (c) 2013, smartbits.co llc.
 * @license		http://opensource.org/licenses/MIT
 * @link		http://smartbits.co
 * @link		http://mattrmiller.com
 * @since		Version 1.0.0
 */

class Singly
{
    /**
     * Constants
     */
    const URL_AUTH      =   'https://api.singly.com/oauth/authenticate';
    const URL_ACCESS    =   'https://api.singly.com/oauth/access_token';
    const URL_API       =   'https://api.singly.com';

    /**
     * Variables
     */
    private $_iTimeout = 10;

    /**
     * Mode
     */
    const MODE_GET      =   1;
    const MODE_POST     =   2;

    /**
     * Variables
     */
    protected $_oCi = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Code igniter instance
        $this->_oCi =& get_instance();

        // Load config
        $this->_oCi->config->load('singly');

        // Save config values
        $this->_iTimeout = $this->_oCi->config->item('singly_timeout');
    }

    /**
     * Authorize service
     * @param string Service
     * @param string Access token
     * @param string Redirect Url
     * @param array Scope
     */
    public function authenticate($sService, $sToken, $sRedirectUrl, $aScope = array())
    {
        // Get the client Id
        $sClientId = $this->_oCi->config->item('api_singly_id');

        // Scope
        $sScope = implode(',', $aScope);

        // Form url
        $sUrl = self::URL_AUTH . '?client_id=' . urlencode($sClientId) . '&redirect_uri=' . urlencode($sRedirectUrl) . '&service=' . urlencode($sService) . '&response_type=code&force_login=true';

        // Add scope only if specified
        if (count($aScope)) {
            $sUrl .= '&scope=' . urlencode($sScope);
        }

        // Add existing token
        if (!empty($sToken)) {
            $sUrl .= '&access_token=' . urlencode($sToken);
        }

        // Redirect
        redirect($sUrl);
    }

    /**
     * Callback
     * @return string/false
     */
    public function callback()
    {
        // Get code
        $sCode = $this->_oCi->input->get('code');
        if (empty($sCode)) {
            return false;
        }

        // Make post parameters
        $aPost = array(
            'client_id'         =>  $this->_oCi->config->item('api_singly_id'),
            'client_secret'     =>  $this->_oCi->config->item('api_singly_secret'),
            'code'              =>  $sCode,
            'grant_type'        =>  'authorization_code'
        );

        // Get access token
        $sRes = $this->_post(self::URL_ACCESS, $aPost);
        if (empty($sRes)) {
            return false;
        }

        // Decode
        $oJson = json_decode($sRes);
        if (empty($oJson)) {
            return false;
        }

        // Access token
        if (!isset($oJson->access_token)) {
            return false;
        }

        return $oJson->access_token;
    }

    /**
     * Api call
     * @param string Route
     * @param string Access token
     * @param array Parameters
     * @param integer Mode
     * @param mixed Body
     * @return object/false
     */
    public function call($sRoute, $sToken, $aParams = array(), $iMode = self::MODE_GET, $sBody = null)
    {
        // Validate params
        if (empty($sRoute) || empty($sToken)) {
            return false;
        }

        // Add access token to the parameters
        $aParams['access_token'] = $sToken;

        // Make full url
        $sUrl = self::URL_API . '/' . ltrim($sRoute, '/');

        // Make
        $sRes = false;
        if ($iMode == self::MODE_POST) {
            $sRes = $this->_post($sUrl, $aParams, $sBody);
        }
        else if ($iMode == self::MODE_GET) {
            $sRes = $this->_get($sUrl, $aParams);
        }

        // Error?
        if (empty($sRes)) {
            return false;
        }

        // Decode
        $oJson = json_decode($sRes);
        if (empty($oJson)) {
            return false;
        }

        return $oJson;
    }

    /**
     * Post
     * @param string Url
     * @param array Params
     * @param mixed Body
     * @return string
     */
    private function _post($sUrl, $aParams = array(), $sBody = null)
    {
        // Add to url
        if (count($aParams)) {
            $sUrl .= '?' . http_build_query($aParams);
        }

        // Log
        log_message('debug', 'Curl Url: ' . $sUrl);

        // Make curl call
        $oCurl = curl_init();
        // -- Options
        curl_setopt($oCurl, CURLOPT_URL, $sUrl);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->_iTimeout);
        // -- Post fields
        curl_setopt($oCurl, CURLOPT_POST, 1);
        if (empty($sBody)) {
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, http_build_query($aParams));
        }
        else {
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $sBody);
        }
        // -- Exec
        $sRet = curl_exec($oCurl);
        // -- Response code
        if (curl_errno($oCurl) != 0) {
            log_message('error', 'Curl Error: ' . curl_error($oCurl));
            curl_close($oCurl);
            return false;
        }
        // -- Close
        curl_close($oCurl);

        return $sRet;
    }

    /**
     * Get
     * @param string Url
     * @param array Params
     * @return string
     */
    private function _get($sUrl, $aParams = array())
    {
        // Add to url
        if (count($aParams)) {
            $sUrl .= '?' . http_build_query($aParams);
        }

        // Log
        log_message('debug', 'Curl Url: ' . $sUrl);

        // Make curl call
        $oCurl = curl_init();
        // -- Options
        curl_setopt($oCurl, CURLOPT_URL, $sUrl);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->_iTimeout);
        // -- Exec
        $sRet = curl_exec($oCurl);
        // -- Response code
        if (curl_errno($oCurl) != 0) {
            log_message('error', 'Curl Error: ' . curl_error($oCurl));
            curl_close($oCurl);
            return false;
        }
        // -- Close
        curl_close($oCurl);

        // Response
        if (empty($sRet)) {
            $sRet = null;
        }

        return $sRet;
    }
}