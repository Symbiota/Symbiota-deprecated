<?php 
if(isset($googleAnalyticsKey) && $googleAnalyticsKey) { 
?> 
	  window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', '<?php echo $googleAnalyticsKey; ?>');
<?php 
} 
?>

