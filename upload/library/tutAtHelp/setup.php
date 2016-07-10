<?php

class tutAtHelp_setup {
	public static function install(){
		tutAtHelp_sharedStatic::createTableDB();
	}
	public static function reinstall(){
		tutAtHelp_sharedStatic::dropTableDB();
		tutAtHelp_sharedStatic::createTableDB();
	}
	public static function uninstall(){
		tutAtHelp_sharedStatic::dropTableDB();
	}
}
