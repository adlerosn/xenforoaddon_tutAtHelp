<?php
class tutAtHelp_CodeEventListeners_ControllerPublic_Help
{
	public static function load_class($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Help')
		{
			$extend[] = 'tutAtHelp_ControllerPublic_Help';
		}
	}
}
