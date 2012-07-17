<?php

require_once( "xmlrpc.php" );
eval( getPluginConf( $plugin["name"] ) );

$session = rTorrentSettings::get()->session;
if( !strlen($session) || !is_executable(addslash(rTorrentSettings::get()->session)))
{
	$jResult .= "plugin.disable(); log('".$plugin["name"].": '+theUILang.webBadSessionError+' (".$session.").');";
}
else
{
	if($updateInterval)
	{
		$tm = getdate();
		$startAt = mktime($tm["hours"],
			((integer)($tm["minutes"]/$updateInterval))*$updateInterval+$updateInterval,
			0,$tm["mon"],$tm["mday"],$tm["year"])-$tm[0];
		if($startAt<0)
			$startAt = 0;
		$interval = $updateInterval*60;

		$req = new rXMLRPCRequest( $theSettings->getScheduleCommand('rutracker_check',$updateInterval,
			getCmd('execute').'={sh,-c,'.escapeshellarg(getPHP()).' '.escapeshellarg(dirname(__FILE__).'/update.php').' '.escapeshellarg(getUser()).' &}' ));
		if($req->success())
			$theSettings->registerPlugin($plugin["name"],$pInfo["perms"]);
		else
			$jResult .= "plugin.disable(); log('rutracker_check: '+theUILang.pluginCantStart);";
	}
	else
	{
		require( 'done.php' );
		$theSettings->registerPlugin($plugin["name"],$pInfo["perms"]);
	}
}

?>