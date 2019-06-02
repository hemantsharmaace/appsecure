<?php  

include(__DIR__."/config.php");
include(__DIR__."/functions.php");
 
foreach (glob(__DIR__."/classes/*.php") as $filename){
  include $filename;
} 
 
?>