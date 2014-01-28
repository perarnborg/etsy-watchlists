<?php
class OauthController extends ControllerBase
{
    public function initialize()
    {
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $this->config = $config->etsy;
    }

    public function indexAction()
    {
        //Go to My Watchlists if already authenticated
        $auth = $this->session->get('auth');
        if($auth && isset($auth['etsyuser_id'])) {
            header('Location: /mywatchlists');
            return false;
        }

        // instantiate the OAuth object
        // OAUTH_CONSUMER_KEY and OAUTH_CONSUMER_SECRET are constants holding your key and secret
        // and are always used when instantiating the OAuth object
        $oauth = new OAuth($this->config->api_key, $this->config->api_secret);

        // make an API request for your temporary credentials
        $req_token = $oauth->getRequestToken('https://openapi.etsy.com/v2/oauth/request_token?scope=email_r', 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/oauth/access');

        // Set request token secret in session
        $this->session->set('oauth_request_token_secret', $req_token['oauth_token_secret']);

        // Redirect to Etsy
        header('Location: ' . $req_token['login_url']);
    }

    public function AccessAction()
    {
        // get temporary credentials from the url
        $request_token = $_GET['oauth_token'];

        // get temporary secret from session
        $request_token_secret = $this->session->get('oauth_request_token_secret');

        // get the verifier from the url
        $verifier = $_GET['oauth_verifier'];

        $oauth = new OAuth($this->config->api_key, $this->config->api_secret);

        // set the temporary credentials and secret
        $oauth->setToken($request_token, $request_token_secret);

        try {
            // set the verifier and request Etsy's token credentials url
            $acc_token = $oauth->getAccessToken("https://openapi.etsy.com/v2/oauth/access_token", null, $verifier);
        } catch (OAuthException $e) {
            error_log($e->getMessage());
            error_log(print_r($oauth->getLastResponse(), true));
            error_log(print_r($oauth->getLastResponseInfo(), true));
            $this->flash->error('You could not be authorized against Etsy');
            return false;
        }

        $oauth = new OAuth($this->config->api_key, $this->config->api_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauth->setToken($acc_token['oauth_token'], $acc_token['oauth_token_secret']);

        try {
            $data = $oauth->fetch("https://openapi.etsy.com/v2/users/__SELF__", null, OAUTH_HTTP_METHOD_GET);
            $json = $oauth->getLastResponse();
            $user = json_decode($json)->results[0];

        } catch (OAuthException $e) {
            error_log($e->getMessage());
            error_log(print_r($oauth->getLastResponse(), true));
            error_log(print_r($oauth->getLastResponseInfo(), true));
            $this->flash->error('You could not be authorized against Etsy');
            return false;
        }

        // Create or update Etsy user
        $etsyUser = EtsyUsers::findFirst("etsyid = ".$user->user_id);
        var_dump($etsyUser);
        if($etsyUser) {
            // Update if changed
            $isUpdated = false;
            if($etsyUser->etsy_token != $acc_token['oauth_token']) {
                $etsyUser->etsy_token = $acc_token['oauth_token'];
                $isUpdated = true;
            }
            if($etsyUser->etsy_secret != $acc_token['oauth_token_secret']) {
                $etsyUser->etsy_secret = $acc_token['oauth_token_secret'];
                $isUpdated = true;
            }
            if($isUpdated) {
                $etsyUser->update();
            }
        } else {
            $etsyUser = new EtsyUsers();
            $etsyUser->etsyid = $user->user_id;
            $etsyUser->username = $user->login_name;
            $etsyUser->email = $user->primary_email;
            $etsyUser->etsy_token = $acc_token['oauth_token'];
            $etsyUser->etsy_secret = $acc_token['oauth_token_secret'];
            $etsyUser->created_at = new Phalcon\Db\RawValue('now()');
            $etsyUser->create();
        }

        $this->session->set('auth', array(
            'etsyuser_id' => $etsyUser->id
        ));

        header('Location: /mywatchlists');
    }
}
