<?php

//	Extracts OS informationen from the User Agent string
//	Return value:
//	array (
//		'Name'		=>	$Name,
//		'Version'	=>	$Version
//	);

//	Known limitations:
//	The only "standard" concerning UserAgents was the following rule:
//	AppName/Version (example: Mozilla/1.0)
//	You see: there is no information about the operating system at all!
//	Luckily (in this case) many browsers just send what they want to
//	send, and they often include information about the used operating
//	system. But as a result, you should not really rely on this data.
//	Extracting information from the UserAgent just means guessing.
//	Conclusion: don't trust data extracted by the UserAgent, you can
//	only hope that it's correct.
//	In some cases the operating system information is a bit more rough
//	than it seems to be: Windows 2000 does not necessarily mean that
//	the client operating system is Windows 2000. It may also be
//	Windows XP. The same goes with Windows 95: In some cases it just
//	means Windows 9x.

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
//	reported OS Name/Version/etc. There may be reasonable exceptions,
//	but please talk to us first.
//	For contact information see ReadMe.txt

	function Site_Tools_ExtractOsFromUserAgent ($UserAgent, $Recursion = 0)
	{
		$Name							=	'';
		$Version						=	'';

		$WindowsVersions				=	'95|98|NT \d+\.\d+|ME|XP|Vista';
		$WindowsVersionsAfterWhiteSpace	=	"$WindowsVersions|[a-z0-9\.\-_ ,]+";
		$WindowsVersionCorrection		=	array (
												'NT 5.0'		=>	'2000',
												'NT 5.1'		=>	'XP',
												'NT 6.0'		=>	'Vista',
												'3.95'			=>	'95',
												'4.10'			=>	'98',
												'4.90'			=>	'ME',
												'16-bit'		=>	'3.1',
												'32-bit'		=>	''
											);

		$UserAgent						=	preg_replace ('/ via .*$/', '', $UserAgent);

		if (
			! preg_match ('/ /', $UserAgent)	&&
			preg_match ('/\+/', $UserAgent)
		)
		{
			$UserAgent						=	str_replace ('+', ' ', $UserAgent);
		}

		if (
			preg_match ("/Win(?:dows)? 9x (4.90)/i", $UserAgent, $Matches)							||
			preg_match ("/Windows\s+($WindowsVersionsAfterWhiteSpace)/i", $UserAgent, $Matches) ||
			preg_match ("/Win(?:dows)?\s*($WindowsVersions)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Windows";
			if (! preg_match ("/x\d+/", $Matches [1]))
			{
				$Version						=	$Matches [1];
			}

			if (isset ($WindowsVersionCorrection [$Matches [1]]))
			{
				$Version						=	$WindowsVersionCorrection [$Matches [1]];
			}
		}
		else if (
			preg_match ("/WinNT/i", $UserAgent)
		)
		{
			$Name							=	"Windows";
			$Version						=	"NT";
		}
		else if (
			preg_match ("/Win16/i", $UserAgent)
		)
		{
			$Name							=	"Windows";
			$Version						=	"3.1";
		}
		else if (
			preg_match ("/Win32/i", $UserAgent)
		)
		{
			$Name							=	"Windows";
		}
		else if (
			preg_match ("/Windows-(NT(?: Server)?)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"Windows";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Windows/i", $UserAgent, $Matches)							||
			preg_match ("/Windoze/i", $UserAgent, $Matches)							||
			preg_match ("/Win\b/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Windows";
		}
		else if (
			preg_match ("/Linux\s*([0-9\.\-\_]*[0-9\.\_]+(?:-?[a-z\d]+)?)?/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Linux";
			$Version						=	(isset ($Matches [1])) ? $Matches [1] : $Version;
			$Version						=	preg_replace ('/^([\d\.\-]*[\d\.])([a-z].*)$/', '\1-\2', $Version);
		}
		else if (
			preg_match ("/Mac ?OS ?([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Mac OS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Macintosh/i", $UserAgent)	||
			preg_match ("/Mac(?:[^a-z]|$)/i", $UserAgent)
		)
		{
			$Name							=	"Mac OS";
		}
		else if (
			preg_match ("/SunOS ([\d\.\_A-Z]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"SunOS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Solaris\s*([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Solaris";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Solaris/i", $UserAgent)
		)
		{
			$Name							=	"Solaris";
		}
		else if (
			preg_match ("/FreeBSD(?:\/|\s*)([\d\.]+(?:\-[A-Z\d\-]+)?)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"FreeBSD";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/NetBSD ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"NetBSD";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/OpenBSD ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"OpenBSD";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/([\d\.]+)BSD/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"BSD";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/BSD\/OS ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"BSD";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/SCO(?:_SV)? ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"SCO Unix";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/AmigaOS ?([\d\.]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"AmigaOS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/AmigaOS/i", $UserAgent)	||
			preg_match ("/Amiga/i", $UserAgent)
		)
		{
			$Name							=	"AmigaOS";
		}
		else if (
			preg_match ("/BeOS/i", $UserAgent)
		)
		{
			$Name							=	"BeOS";
		}
		else if (
			preg_match ("/Risc OS ([\d\.]+)/i", $UserAgent, $Matches)	||
			preg_match ("/Risc OS-(NC [\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"RISC OS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Risc OS/i", $UserAgent)
		)
		{
			$Name							=	"RISC OS";
		}
		else if (
			preg_match ('/PalmPilot/', $UserAgent)
		)
		{
			$Name							=	"Palm OS";
		}
		else if (
			preg_match ("/HP-UX ([A-Z][\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"HP-UX";
			$Version						=	strtoupper ($Matches [1]);
		}
		else if (
			preg_match ("/IRIX(?:64)? ?([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"IRIX";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/NC OS ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"NC OS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/NEWS-OS ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"NEWS-OS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/NEOS ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"NEOS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/OSF1 [vt]?([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"OSF1";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/OpenVMS V?([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"OpenVMS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/OpenVMS/i", $UserAgent)
		)
		{
			$Name							=	"OpenVMS";
		}
		else if (
			preg_match ("/Mach ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"Mach";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/UnixWare ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"UnixWare";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/MUNIX/i", $UserAgent)
		)
		{
			$Name							=	"MUNIX";
		}
		else if (
			preg_match ("/Unix/i", $UserAgent)
		)
		{
			$Name							=	"Unix";
		}
		else if (
			preg_match ("/Ultrix ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"ULTRIX";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/OS\/2/", $UserAgent)
		)
		{
			$Name							=	"OS/2";
		}
		else if (
			preg_match ("/VAX ([\d\-]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"VAX";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/AIX ([\d\.]+)/i", $UserAgent, $Matches)
		)
		{
			$Name							=	"AIX";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/CP\/M/i", $UserAgent)
		)
		{
			$Name							=	"CP/M";
		}
		else if (
			preg_match ("/DOS\b/i", $UserAgent)
		)
		{
			$Name							=	"DOS";
		}
		else if (
			preg_match ("/Atari/i", $UserAgent)
		)
		{
			$Name							=	"Atari";
		}
		else if (
			preg_match ("/N(intendo )?64/i", $UserAgent)
		)
		{
			$Name							=	"Nintendo 64 Operating System";
		}
		else if (
			preg_match ("/CMS (?:Level\s*)?(\d+)/", $UserAgent, $Matches)	||
			preg_match ("/CMS\/([\d\.]+)/", $UserAgent, $Matches)
		)
		{
			$Name							=	"CMS";
			$Version						=	$Matches [1];
		}
		else if (
			preg_match ("/Passport/i", $UserAgent)		||
			preg_match ("/Saturn/i", $UserAgent)		||
			preg_match ("/DreamKey/i", $UserAgent)		||
			preg_match ("/Dreamcast/i", $UserAgent)
		)
		{
			$Name							=	"Dreamcast OS";
		}

		$Os								=	array (
												'Name'		=>	$Name,
												'Version'	=>	$Version
											);

		return $Os;
	}

?>
