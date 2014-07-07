<?php

//	Extracts Browser informationen from the User Agent string
//	Return value:
//	array (
//		'Name'		=>	$Name,
//		'Version'	=>	$Version
//	);

//	Known limitations:
//	The only "standard" concerning UserAgents was the following rule:
//	AppName/Version (example: Mozilla/1.0)
//	But few browsers really accepted this standard so de facto there is
//	no standard. Extracting browser information from the UserAgent just
//	means guessing. Conclusion: don't trust data extracted by the
//	UserAgent, you can only hope that it's correct.
//	Furthermore the browser can send whatever UserAgent it wants.
//	Many browsers identify themselves as Microsoft Internet Explorer
//	(more or less) to ignore UserAgent based reactions of a site.

//	Don't be to restrictive based on this data. Use it only if you
//	really need to. Try to use it only as a recommendation that helps
//	the user decide what they have to do. But don't force them.

//	The purpose of this function is to ease implementing UserAgent
//	specific behaviour, without the hassle of parsing the UserAgent
//	everytime. It should also decrease the amount of false detections.

//	IMPORTANT:
//	If this function returns data that is in itself wrong (a typo, a
//	wrong detection, etc.), please don't try to workaround - talk to
//	us! Such errors might be corrected in future versions which could
//	render your workaround a bug itself. Result would be chaos. This
//	function should stay the _only_ thing between the UserAgent and a
//	reported Browser Name/Version/etc. There may be reasonable
//	exceptions, but please talk to us first.
//	For contact information see ReadMe.txt

	function Site_Tools_ExtractBrowserFromUserAgent ($UserAgent)
	{
		$Name							=	'';
		$Version						=	'';

		$UserAgent						=	preg_replace ('/ via .*$/', '', $UserAgent);
		$GeneralNameReplacements		=	array (
												'aolbrowser'			=>	'AOL',
												'_'						=>	' ',
												'SEGASATURN'			=>	'Sega Saturn',
												'browser'				=>	'Browser',
												'COMBINE'				=>	'combine',
												'EI*Net'				=>	'EINet',
												'Eule-Robot'			=>	'Eule Robot',
												'.DLL'					=>	'.dll',
												'heraspider'			=>	'heraSpider',
												'IBM-WebExplorer-DLL'	=>	'IBM WebExplorer DLL',
												'Lotus-Notes'			=>	'Lotus Notes',
												'Lynx ALynx'			=>	'Lynx',
												'Unknown'				=>	'',
												'unknown'				=>	''
											);
		$GeneralVersionReplacements		=	array (
												'-beta'					=>	' beta',
												'-alpha'				=>	' alpha',
												'unspecified'			=>	'',
												'unkown'				=>	'',
												'undefined'				=>	'',
												'version'				=>	'',
												'Ver'					=>	''
											);

	//	Browsers that hide their name within the UserAgent (e.g. Mozilla/X.X (BrowserName X.X...))

		$ConflictingNames				=	array (
												'Powermarks',
												'OmniWeb',
												'PerMan Surfer',
												'WebCapture',
												'DreamKey',
												'DreamPassport',
												'Xpressware',
												'Linkbot',
												'BorderManager',
												'BullsEye',
												'MNTR',
												'SuperSonic Turbo Jet Nitro Browser',
												'Harvest',
												'VCI WebViewer',
												'WebTV',
												'FlashSite',
												'Columbus',
												'InterNotes Navigator',
												'WebAnalyzer',
												'AOL-IWENG'
											);

		$ConflictingNameReplacements	=	array (
												'AOL-IWENG'				=>	'AOL IWENG'
											);
							

		if (
			strpos ($UserAgent, ' ') === False
		)
		{
			$UserAgent						=	str_replace ('+', ' ', $UserAgent);
		}

		if (
				preg_match ('/^[A-Z\d\_]*[\d_][A-Z\d\_]*$/', $UserAgent)
		)
		{
			$UserAgent						=	preg_replace ('/(\d)_(\d)/', '\1.\2', $UserAgent);
			$UserAgent						=	str_replace ('_', ' ', $UserAgent);
			$UserAgent						=	strtolower ($UserAgent);
			$UserAgent						=	preg_replace ('/[a-z]+/e', 'ucfirst("\0")', $UserAgent);
		}


		if (
				strpos ($UserAgent, ' ') === False
			&&	strpos ($UserAgent, '+') !== False
		)
		{
			$UserAgent						=	str_replace ('+', ' ', $UserAgent);
		}

		$UserAgent						=	str_replace ('(tm)', '', $UserAgent);

		if (
				strpos ($UserAgent, 'Opera') === False
			&&	strpos ($UserAgent, 'WebTV') === False
			&&	(
					preg_match ("/MSIE ([\d\.]+)(?:[^a-z\d\.]|$)/i", $UserAgent, $Matches)
				||	preg_match ("/MSIE ([\d\.]+[a-z]\d?)(?:[^a-z\d\.]|$)/i", $UserAgent, $Matches)
				||	strpos ($UserAgent, 'MSIE') !== FALSE
				||	preg_match ("/^(?:IE|Internet Explorer) ([\d\.]+)$/", $UserAgent, $Matches)
			)
		)
		{
			$Name							=	"Microsoft Internet Explorer";
			$Version						=	(isset ($Matches [1])) ? $Matches [1] : '';
		}
		else if (
			preg_match ("/(?:^|\s|\()Opera[ \/]([\d\.]+(?:[a-z]\d)?)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Opera";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/(?:MS|Microsoft)?\s*FrontPage(?:\s*Wpp)?((?: Express)?)[ \/]([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Microsoft FrontPage$Matches[1]";
			$Version						=	$Matches [2];
		}
		else if (
				preg_match ("/Quarterdeck Mosaic Version ([\d\.]+[a-z]*)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Quarterdeck Mosaic";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/\bSPRY_Mosaic[ \/v]+([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"SPRY Mosaic";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/\bBTRON Basic Browser Version ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"BTRON Basic Browser";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/\bAOL ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"AOL";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Mozilla\/[\d\.]+ \(.*?PCN-.*?([\d\.]+).*?\)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"PCN";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Mozilla\/[\d\.]+ \(.*?MemoWeb[ \/]([\d\.]+).*?\)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"MemoWeb";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Canon-WebRecord[ \/]([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Canon WebRecord";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Webinator-.*?[ \/]([\d\.a-z]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Webinator";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/\(?Direct Hit Grabber\)[ \/]([\d\.a-z]+)/i", $UserAgent, $Matches)
			||	preg_match ("/Direct Hit Grabber()/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Direct Hit Grabber";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/E-Soft Web-?Probe/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"E-Soft WebProbe";
		}
		else if (
				preg_match ("/NAVIO/", $UserAgent)
		)
		{
		}
		else if (
				preg_match ("/.*(" . implode ("|", $ConflictingNames) . ")[ \/V](\d[\d\.a-z\-]*)/i", $UserAgent, $Matches)
			||	preg_match ("/.*(" . implode ("|", $ConflictingNames) . ")()/i", $UserAgent, $Matches)
		)
		{
			$Name							=	$Matches [1];
			$Version						=	$Matches [2];

			foreach ($ConflictingNameReplacements as $Search => $Replace)
			{
				$Name							=	str_replace ($Search, $Replace, $Name);
			}
		}
		else if (
				preg_match ("/PlanetWeb[ \/]([\d\.]+[a-z]?(?: (?:alpha|beta|pre))?)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"PlanetWeb";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/([a-z\d]*)PASSPORT[ \/]([\d\.]+(?: (?:alpha|beta|pre))?)/i", $UserAgent, $Matches)
			||	preg_match ("/([a-z\d]*)PASSPORT()/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"$Matches[1]PASSPORT";
			$Version						=	$Matches [2];
		}
		else if (
				preg_match ("/Mozilla\/[\d\.]+ \(.*?WebSurfer[ \/]([\d\.]+).*?\)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"WebSurfer";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Oracle.*?PowerBrowser.*?[ \/]([\d\.a-z]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Oracle PowerBrowser";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/ANT\s*Fresco[ \/]([\d\.a-z]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"ANT Fresco";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Mozilla\/[\d\.]+ \(.*?EZResult.*?\)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"EZResult";
		}
		else if (
				preg_match ("/Mozilla\/[\d\.]+ \(.*?NEWT ActiveX.*?\)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"NEWT ActiveX";
		}
		else if (
				preg_match ("/Mozilla-compatible\((.*?)\)\/([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	$Matches [1];
			$Version						=	strtolower ($Matches [2]);
		}
		else if (
			preg_match ("/Mozilla\/[5-9][\d.]* \(.*?\).*?Netscape\d*\/([\d\.b]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Netscape";
			$Version						=	$Matches [1];
			$Version						=	preg_replace ('/b([1-3])/', ' PR \1', $Version);
		}
		else if (
			preg_match ("/Mozilla\/[\d\.]+ \((?:.*?;){3}.*?([\d\.]+).*?\) Gecko\/\d{8} (.+)\/(.+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	$Matches [2];
			$Version						=	$Matches [3];
			$Version						=	preg_replace ("/^(\d+\.\d+)\.0$/", '\1', $Version);
		}
		else if (
			preg_match ("/Mozilla\/[\d\.]+ \((?:.*?;){3}.*?([\d\.]+).*?\) Gecko\/\d{8}/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Mozilla";
			$Version						=	$Matches [1];
			$Version						=	preg_replace ("/^(\d+\.\d+)\.0$/", '\1', $Version);
		}
		else if (
			preg_match ("/Emissary[ \/]([\da-z\.]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Emissary";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Net\.?Box[ \/]([\da-z\.]+(?: R\d+[a-z]))/", $UserAgent, $Matches)
		)
		{
			$Name							=	"NetBox";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/\bSlurp.*?[ \/](\d[\d\.]*)/", $UserAgent, $Matches)
			||	preg_match ("/\bSlurp()/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Slurp";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Sax Webster[ \/]([\d\.]+)/", $UserAgent, $Matches)
			||	preg_match ("/Sax Webster()/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Sax Webster";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Konquerer[ \/]([\d\.]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Konquerer";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Galeon[ \/;]+([\d\.]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Galeon";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/NetPositive[ \/]+([\d\.a-zA-Z]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"NetPositive";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Tcl\/Tk(?: browser[ \/v]+[\d\.a-z]+|[ \/v]+[\d\.a-z]+\s+browser)[ \/v]+([\d\.a-z]+)/", $UserAgent, $Matches)
			||	preg_match ("/Tcl\/Tk(?: browser[ \/v]+[\d\.a-z]+|[ \/v]+[\d\.a-z]+\s+browser)()/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Tcl/Tk Browser";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/SunLab's Tcl\/Tk Editor[ \/]+(\d[\d\.]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"SunLab's Tcl/Tk Editor";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/^Mozilla[ \/]?(\d+\.[\d\.]*\d+(?:\s+[a-z]+|[a-z\d]\d*\b|PE\b|gold)?)(.*?)\(/i", $UserAgent, $Matches)
			||	preg_match ("/^Mozilla[ \/](\d+\.[\d\.]*\d+[a-z]?)()/i", $UserAgent, $Matches)
			||	preg_match ("/^Mozilla()()/i", $UserAgent, $Matches)
		)
		{
			if (
					$Matches [1] != ''
				&&	$Matches [1] < "5.0"
				&&	! preg_match ('/compatible/i', $UserAgent)
				&&	! preg_match ('/not really/i', $UserAgent)
			)
			{
				if (preg_match ('/^([a-z]\d)/', $Matches [2], $Append))
				{
					$Matches [1]					.=	$Append [1];
				}

				$Name							=	"Netscape";
				$Version						=	strtolower ($Matches [1]);
				$Version						=	preg_replace ('/\.(\D|$)/', '.0\1', $Version);
				if (! preg_match ('/gold/i', $Version))
				{
					$Version						=	preg_replace ('/^([3-9]\.\d*b?\d*)(.*)$/', '\1', $Version);
				}
			}
		}
		else if (
				preg_match ("/InfoMosaic.*?\/([^\(\)]*)/i", $UserAgent, $Matches)
			||	preg_match ("/InfoMosaic.*?([\d\.]+.*)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"InfoMosaic";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/^NCSA Mosaic.*?(\d[\d\.a-z]+\s*(?:[a-z]\d|beta)?)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"NCSA Mosaic";
			$Version						=	$Matches [1];
		}
		else if (
				preg_match ("/Mothra\/(.*)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Mothra";
			$Version						=	strtolower ($Matches [1]);
		}
		else if (
				preg_match ("/^(.*?[\/]OTWR):([\da-z]+)/i", $UserAgent, $Matches)
			||	preg_match ("/(MacMosaic)\s*(.*)/", $UserAgent, $Matches)
			||	preg_match ("/^(Mosaic).*?\/(\d[\d\.]*)/", $UserAgent, $Matches)
			||	preg_match ("/^(MacWeb)(?:\/libwww)?\/?(\S*)/", $UserAgent, $Matches)
			||	preg_match ("/^(Links) \(([\d\.]+)/", $UserAgent, $Matches)
			||	preg_match ("/^(Digimarc [a-z]+)\/([\d\.]+)/i", $UserAgent, $Matches)
			||	preg_match ("/^(Lycos[ _]Spider).*?\/.*?v([\d\.]+)/", $UserAgent, $Matches)
			||	preg_match ("/^(Lynx)[ \/]([\d\.a-zA-Z\-]+\s*(?:beta|alpha|pre)?)/i", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ :]+)\s*-\s*([\d\.]+[a-z\d\.]*)/i", $UserAgent, $Matches)
			||	preg_match ("/^([a-z]+)\s*(\d[\d\.]+)$/i", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ :\d]+?)[ v]+(\d+\.[\d\.]+[a-z]?)/i", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ !\*:\d]+)(?:\(.*?\))?\b[\/v]+([\d\.][\d\.\-a-z]*(?: (?:beta|alpha))?)/i", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ !\*:\d]+)(?:\(.*?\))?[ \/v]+([\d\.]+[a-z]?\d*(?: (?:beta|alpha))?)+/i", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ !\*:\d]+)\/([\da-z\.\-_ ]+)/i", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ !\*:\/]+)(\d(?:\.[\da-z\.\-_]*)?\b)/i", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ !\*:]+)(\d+(\.\d+)+[a-z]*)/i", $UserAgent, $Matches)
			||	preg_match ("/^(\D+)[ \/v]?([\d\.]+)$/", $UserAgent, $Matches)
			||	preg_match ("/^([יטבא+'\@a-z\.\-_ !\*:\d]+)\/?(?:\(.*?\))?()$/i", $UserAgent, $Matches)
			||	preg_match ("/^(.*?)#([\d\.]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	$Matches [1];

			if ($Name == 'Netscape')
			{
				$Name							=	'';
			}
			else
			{
				$Version						=	strtolower ($Matches [2]);

				foreach ($GeneralNameReplacements as $Search => $Replace)
				{
					$Name							=	str_replace ($Search, $Replace, $Name);
				}
				foreach ($GeneralVersionReplacements as $Search => $Replace)
				{
					$Version						=	str_replace ($Search, $Replace, $Version);
				}

				$Name							=	preg_replace ('/version\s*$/i', '', $Name);
			}
		}

		$Name							=	trim ($Name);
		$Name							=	preg_replace ('/\b(for|from)[a-z ]+/i', '', $Name);
		$Name							=	preg_replace ('/\b([a-z]+)(\-[A-Z][a-z]+)+/e', 'ucfirst("\1")."\2"', $Name);
		$Version						=	strtolower ($Version);
		$Version						=	trim ($Version);
		$Version						=	preg_replace ('/^\./', '0.', $Version);
		$Version						=	preg_replace ('/\.$/', '.0', $Version);
		$Version						=	preg_replace ('/^\d$/', '\0.0', $Version);

		$Browser						=	array (
												'Name'		=>	$Name,
												'Version'	=>	$Version
											);

		return $Browser;
	}

?>
