<?php

	function Site_Tools_ExtractServerFromServerSoftware ($ServerSoftware)
	{
		$Name							=	'';
		$Version						=	'';

		if (
				preg_match ("/^(.*?)\/(\S*)/", $ServerSoftware, $Matches)
		)
		{
			$Name							=	$Matches [1];
			$Version						=	$Matches [2];
		}

		$Server							=	array (
												'Name'		=>	$Name,
												'Version'	=>	$Version
											);

		return $Server;
	}

?>
