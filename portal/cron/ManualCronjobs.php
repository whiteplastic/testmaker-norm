<?php 

$def_reloadInterval = 5000; // reload this page every .. milliseconds
$def_execInterval = 30; // call script every .. seconds

if ( isset($_GET["i"] ) )
	$count = $_GET["i"];
else
	$count = 0;

if ($count+1 > ($def_execInterval / $def_reloadInterval * 1000)) {
	include "cronjob.php";
	$delay = 10000;
	$count = 0;
} 
else {
	$delay = $def_reloadInterval;
	$count ++;
}

$nextexec = $def_execInterval - $delay / 1000 * $count;

$redirect_url = $_SERVER['PHP_SELF']."?i=".$count;

?>

<html>
<head>
	<title>Cronjob execution running...</title>
	
	
	<script type="text/javascript">
	<!--

		function refresher(){
		    window.location = "<?php echo $redirect_url; ?>";
		}
		
	//-->
	</script>


</head>

<body onLoad="setTimeout('refresher()', <?php echo $delay; ?>)">
<p>
testMaker cronjob script: 
<p>
Next execution in <?php echo $nextexec." / ".$def_execInterval; ?> seconds.
<p>
<font size=-2>(You can always stop the execution cycle by closing this window)</font>

</body>

</html>