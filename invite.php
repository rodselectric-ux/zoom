<?php
$iphone = strpos($_SERVER['HTTP_USER_AGENT'],"iPhone");
$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android");
$palmpre = strpos($_SERVER['HTTP_USER_AGENT'],"webOS");
$berry = strpos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
$ipod = strpos($_SERVER['HTTP_USER_AGENT'],"iPod");
$mac = strpos($_SERVER['HTTP_USER_AGENT'],"Macintosh");
{
if ($android == true)

header('Location: Android/');

if ($iphone || $ipod || $berry|| $mac == true)

header('Location: Iphone/');
//OR
echo "<script>window.location=' Windows/'</script>";
}
?>