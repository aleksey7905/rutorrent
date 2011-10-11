<?php

require_once( dirname(__FILE__)."/../../php/settings.php");
eval(getPluginConf('autotools'));

class rAutoTools
{
	public $hash = "autotools.dat";
	public $enable_label = 0;
	public $label_template = "{DIR}";
	public $enable_move = 0;
	public $path_to_finished = "";
	public $fileop_type = "Move";
	public $enable_watch = 0;
	public $path_to_watch = "";
	public $watch_start = 0;

	static public function load()
	{
		$cache = new rCache();
		$at = new rAutoTools();
		$cache->get( $at );
		return $at;
	}
	public function store()
	{
		$cache = new rCache();
		return $cache->set( $this );
	}
	public function set()
	{
		if( !isset( $HTTP_RAW_POST_DATA ) )
			$HTTP_RAW_POST_DATA = file_get_contents( "php://input" );
		if( isset( $HTTP_RAW_POST_DATA ) )
		{
			$vars = explode( '&', $HTTP_RAW_POST_DATA );
			$this->enable_label = 0;
			$this->label_template = "{DIR}";
			$this->enable_move = 0;
			$this->fileop_type = "Move";
			$this->path_to_finished = "";
			$this->enable_watch = 0;
			$this->path_to_watch = "";
			$this->watch_start = 0;
			foreach( $vars as $var )
			{
				$parts = explode( "=", $var );
				if( $parts[0] == "enable_label" )
				{
					$this->enable_label = $parts[1];
				}
				else if( $parts[0] == "label_template" )
				{
					$this->label_template = $parts[1];
				}
				else if( $parts[0] == "enable_move" )
				{
					$this->enable_move = $parts[1];
				}
				else if( $parts[0] == "fileop_type" )
				{
					$this->fileop_type = $parts[1];
				}
				else if( $parts[0] == "path_to_finished" )
				{
					$this->path_to_finished = $parts[1];
				}
				else if( $parts[0] == "enable_watch" )
				{
					$this->enable_watch = $parts[1];
				}
				else if( $parts[0] == "path_to_watch" )
				{
					$this->path_to_watch = $parts[1];
				}
				else if( $parts[0] == "watch_start" )
				{
					$this->watch_start = $parts[1];
				}
			}
			$this->setHandlers();
		}
		$this->store();
	}
	public function get()
	{
		$ret  = "theWebUI.autotools = { ";
		$ret .= "EnableLabel: ".$this->enable_label;
		$ret .= ", LabelTemplate: '".addslashes( $this->label_template )."'";
		$ret .= ", EnableMove: ".$this->enable_move;
		$ret .= ", FileOpType: '".$this->fileop_type."'";
		$ret .= ", PathToFinished: '".addslashes( $this->path_to_finished )."'";
		$ret .= ", EnableWatch: ".$this->enable_watch;
		$ret .= ", PathToWatch: '".addslashes( $this->path_to_watch )."'";
		$ret .= ", WatchStart: ".$this->watch_start;
		return $ret." };\n";
	}
	public function setHandlers()
	{
		global $autowatch_interval;
		$req = new rXMLRPCRequest();
		$theSettings = rTorrentSettings::get();
		$pathToAutoTools = dirname(__FILE__);
		if($this->enable_label)
			$cmd = 	$theSettings->getOnInsertCommand(array('autolabel'.getUser(), 
				getCmd('branch').'=$'.getCmd('not').'=$'.getCmd("d.get_custom1").'=,"'.
				getCmd('execute').'={'.getPHP().','.$pathToAutoTools.'/label.php,$'.getCmd("d.get_hash").'=,'.getUser().'}"'));
		else
			$cmd = 	$theSettings->getOnInsertCommand(array('autolabel'.getUser(), getCmd('cat=')));
		$req->addCommand($cmd);
		if($this->enable_move && (trim($this->path_to_finished)!=''))
		{
			if(rTorrentSettings::get()->iVersion<0x808)
			{
				$cmd = 	$theSettings->getOnFinishedCommand(array('automove'.getUser(), 
						getCmd('d.set_custom').'=x-dest,"$'.getCmd('execute_capture').
						'={'.getPHP().','.$pathToAutoTools.'/move.php,$'.getCmd('d.get_hash').'=,$'.getCmd('d.get_base_path').'=,$'.
						getCmd('d.get_base_filename').'=,$'.getCmd('d.is_multi_file').'=,'.getUser().'}" ; '.
						getCmd('branch').'=$'.getCmd('not').'=$'.getCmd('d.get_custom').'=x-dest,,'.getCmd('d.set_directory_base').'=$'.getCmd('d.get_custom').'=x-dest'
					));
			}
			else
			{
				if($this->fileop_type=="Move")
				{
					$cmd = 	$theSettings->getOnFinishedCommand(array('automove'.getUser(), 
							getCmd('d.set_directory_base').'="$'.getCmd('execute_capture').
							'={'.getPHP().','.$pathToAutoTools.'/check.php,$'.getCmd('d.get_base_path').'=,$'.
							getCmd('d.get_base_filename').'=,$'.getCmd('d.is_multi_file').'=,'.getUser().'}" ; '.
							getCmd('execute').'={'.getPHP().','.$pathToAutoTools.'/move.php,$'.getCmd('d.get_hash').'=,$'.getCmd('d.get_base_path').'=,$'.
							getCmd('d.get_base_filename').'=,$'.getCmd('d.is_multi_file').'=,'.getUser().'}'
						));
				}
				else
				{
					$cmd = 	$theSettings->getOnFinishedCommand(array('automove'.getUser(), 
							getCmd('execute').'={'.getPHP().','.$pathToAutoTools.'/move.php,$'.getCmd('d.get_hash').'=,$'.getCmd('d.get_base_path').'=,$'.
							getCmd('d.get_base_filename').'=,$'.getCmd('d.is_multi_file').'=,'.getUser().'}'
						));
				}
			}
		}
		else
			$cmd = $theSettings->getOnFinishedCommand(array('automove'.getUser(), getCmd('cat=')));
		$req->addCommand($cmd);
		if($this->enable_watch && (trim($this->path_to_watch)!='')) 
			$cmd = 	new rXMLRPCCommand('schedule', array( 'autowatch'.getUser(), '10', $autowatch_interval."", 
				getCmd('execute').'={sh,-c,'.escapeshellarg(getPHP()).' '.escapeshellarg($pathToAutoTools.'/watch.php').' '.escapeshellarg(getUser()).' &}' ));
		else
			$cmd = new rXMLRPCCommand('schedule_remove', 'autowatch'.getUser());
		$req->addCommand($cmd);
		return($req->success());
	}
}

?>