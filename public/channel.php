<?php
  $cache_expire = 60*60*24*365;
  header("Pragma: public");
  header("Cache-Control: maxage=".$cache_expire);
  header('Expires: '.gmdate('D, d M Y H:i:s', time()+$cache_expire).' GMT');
?>
<script type='text/javascript' src="//connect.facebook.net/en_US/all.js"></script>
