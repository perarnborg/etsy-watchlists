<?php
class CronjobController extends ControllerBase
{
    public function initialize()
    {
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $this->config = $config->etsy;
        $this->parameters = Parameters::find();
    }

    public function indexAction()
    {

    	$watchlists = Watchlists::query()
		    ->where("last_checked < :one_hour_ago:")
		    ->bind(array("one_hour_ago" => time() - 3600))
		    ->limit(10)
		    ->order("last_checked")
		    ->execute();
		foreach($watchlists as $watchlist) {
			$etsyUser = EtsyUsers::findFirst($watchlist->etsy_users_id);
			$keywords = null;
			$category = null;
			$shipsto = null;
			foreach($watchlist->watchlistsParameters as $watchlistsParameter) {
				switch ($this->parameterApiName($watchlistsParameter->parameters_id)) {
					case 'keywords':
						$keywords = implode(',', explode(' ', $watchlistsParameter->value));
						break;
					case 'category':
						$category = $watchlistsParameter->value;
						break;
					case 'shipsto':
						$shipsto = $watchlistsParameter->value;
						break;
				}
			}
			$listings = array();
			$newListingsCount = 0;
			if($keywords) {
				$listings = EtsyApi::searchListings($this->config->api_key, $this->config->api_secret, $etsyUser->etsy_token, $etsyUser->etsy_secret, $keywords, $category, $shipsto);
				$listings = EtsyApi::parseListings($listings);
				$watchlist->setListings($listings, $newListingsCount);
			}
			$watchlist->last_checked = time();
			$watchlist->save();
			echo $watchlist->name . ' ' . $newListingsCount.'<br/>';
		}
		die();
    }

    public function emailAction()
    {

    	$watchlists = Watchlists::query()
		    ->where("email_interval > 0")
		    ->order("last_emailed")
		    ->execute();
		foreach($watchlists as $watchlist) {
			if($watchlist->last_emailed < time() - $watchlist->email_interval) {
				$etsyUser = EtsyUsers::findFirst($watchlist->etsy_users_id);
				$listings = WatchlistsListings::query()
				    ->where("watchlists_id = :watchlists_id:")
				    ->andWhere("is_emailed = 0")
				    ->bind(array("watchlists_id" => $watchlist->id))
				    ->order("creation")
				    ->execute();
				if(count($listings) > 0) {
					// Send email to $etsyUser->email
					$this->sendEmail($etsyUser, $watchlist, $listings);
					// Mark listings as emailed
					$phql = "Update WatchlistsListings SET is_emailed = 1 WHERE WatchlistsListings.watchlists_id = :watchlists_id:";
	                $result = $this->modelsManager->executeQuery($phql, array('watchlists_id' => $watchlist->id));
				}
                // Mark watchlist as emailed
				$watchlist->last_emailed = time();
				$watchlist->save();
			}
		}
		die();
    }

    private function parameterApiName($parameterId) {
    	foreach ($this->parameters as $parameter) {
    		if($parameter->id == $parameterId) {
    			return $parameter->api_name;
    		}
    	}
    }

    private function sendEmail($etsyUser, $watchlist, $listings) {
    	$message = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
	 "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>'.$watchlist->name.'</title>

</head>
<body style="padding: 0;margin-left: 0;margin-right: 0;margin-top: 0;margin-bottom: 0;">

<table id="outer" width="100%" cellspacing="0" cellpadding="28" border="0">
	<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
		<td align="center" width="100%" style="padding-left: 0;padding-right: 0;margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
			<table id="wrapper" width="700" cellspacing="0" cellpadding="0" border="0">
				<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
					<td width="700" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">

						<table id="top" width="700" cellspacing="0" cellpadding="0" border="0" style="padding-bottom: 11px;">
							<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
								<td width="150" align="center" class="read-in-browser" style="margin-top: 0;margin-bottom: 0;color: #939393;line-height: 1.36;text-decoration: none;font-size: 10px;">
									<!--
									Cant read this email?
									<br>
									<a href="#" style="color: #939393;text-decoration: none;font-size: 10px;"><span style="color: #939393;text-decoration: none;font-size: 10px;">Click here to view it online</span></a>
									-->
								</td>
								<td width="400" align="center" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
									<h3>Etsy Watchlists</h3>
								</td>
								<td width="150" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">&nbsp;</td>
							</tr>
						</table>

						<table id="header" width="700" cellspacing="0" cellpadding="0" border="0" style="border-bottom: 1px solid #c8c8c8;padding-bottom: 20px;">
							<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
								<td width="700" align="center" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
									<table id="intro" class="text" width="500" cellspacing="0" cellpadding="0" border="0">
										<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
											<td align="center" width="500" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
												<h2>New listings in you watchlist:</h2>
												<h1 style="margin-top: 0;margin-bottom: 0;padding-bottom: 11px;font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 46px;font-weight: bold;line-height: 40px;color: #000000;text-transform: uppercase;">'.$watchlist->name.'</h1>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<table width="700" cellspacing="0" cellpadding="34" border="0">
							<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
								<td width="700" align="center" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
									<table id="intro" class="text" width="500" cellspacing="0" cellpadding="0" border="0">
										<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
											<td align="center" width="500" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
												<table class="text" width="500" cellspacing="0" cellpadding="0" border="0">';
												foreach ($listings as $listing) {
													$message .= '
													<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
														<td width="195" align="left" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
															<br>
															<a href="'.$listing->url.'" target="_blank" style="color: #000000;"><img src="'.$listing->image_url.'" alt="" style="display: block;border: none;"></a>
															<br>
														</td>
														<td width="305" align="left" valign="top" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
															<br>
															<h3>'.$listing->title.'</h3>
															<p style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;padding-bottom: 4px;">
																'.$listing->currency_code.' '.$listing->price.'
															</p>
															<p style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;padding-bottom: 4px;">
																Shop: <a href="'.$listing->shop_url.'" target="_blank" style="color: #000000;">'.$listing->shop_loginname.'</a>
															</p>
															<p style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;padding-bottom: 4px;">
																<a href="'.$listing->url.'" style="color: #000000;">View listing on Etsy</a>
															</p>
															<br>
														</td>
													</tr>';
												}
												$message .= '
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<table id="footer" width="700" cellspacing="0" cellpadding="27" border="0" style="border-top: 1px solid #c8c8c8;">
							<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
								<td width="700" align="center" style="padding-bottom: 0;margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
									<table class="text" width="500" cellspacing="0" cellpadding="0" border="0">
										<tr style="font-family: Arial, Helvetica, Verdana, sans-serif;font-size: 11px;">
											<td align="center" width="500" style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;">
												<p style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;padding-bottom: 21px;">
													You can view all listings in this watchlists at <a href="http://etsywatchlists.perarnborg.se/oauth" style="color: #000000;">http://etsywatchlists.perarnborg.se</a>.
												</p>
												<p style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;padding-bottom: 21px;">
													There you can also edit the settings for your email notifications.
												</p>
												<p style="margin-top: 0;margin-bottom: 0;color: #000000;line-height: 1.36;padding-bottom: 21px;">
													<strong style="font-weight: bold;">Kind Regards, Etsy Watchlists</strong>
												</p>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>

					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>';
		$to = $etsyUser->email;
		$subject = 'Listings for Etsy Watchlist ' . $watchlist->name;
		$headers = "From: no-reply@perarnborg.se\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		echo $message;
		mail($to, $subject, $message, $headers);
    }
}
