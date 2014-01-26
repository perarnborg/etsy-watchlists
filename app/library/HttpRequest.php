<?php
/**
 *
 * HttpRequest is used to collect data with Curl
 *
 */
class HttpRequest
{
  private $url;
  private $userAgent;

  /**
   *
   * Sets the request url
   * @param string $url, string $userAgent
   */
  public function __construct($url, $userAgent = '')
  {
    $this->url = $url;
    $this->userAgent = empty($userAgent)?config::userAgent:$userAgent;
  }

  // This function makes the request and returns the response as a string. I an error is occured the message is set to the outpu variable errorMessage.
  public function getResponse(&$httpCode)
  {
    try {
      $response = false;
      $ch = curl_init();
      // Set URL to download
      curl_setopt($ch, CURLOPT_URL, $this->url);
      // Set a referer
      //curl_setopt($ch, CURLOPT_REFERER, "http://" . $_SERVER['HTTP_HOST']);
      // User agent
      curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
      // Include header in result? (0 = yes, 1 = no)
      curl_setopt($ch, CURLOPT_HEADER, 0);
      // Should cURL return or print out the data? (true = return, false = print)
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      // Disable ssl verification
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      // Timeout in seconds
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      //curl follow location
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      // Download the given URL
      $response = curl_exec($ch);

      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      $info = curl_getinfo($ch);
      $this->content_type = $info['content_type'];

      if(!$response)
      {
        logger::log('Couldn\'t make request: '.$info['http_code']);
        throw new Exception('Could not make request!', $info['http_code']);
      }
      return $response;
    }
    catch(Exception $ex) {
      $httpCode = $ex->getCode();
    }
    if(isset($info['http_code']))
    {
      $httpCode = $info['http_code'];
    }
    return $response;
  }

  public function checkxml(){

    if($this->content_type == 'text/xml'){

      return true;
    }else{

      return false;
    }
  }

}
