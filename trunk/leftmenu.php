<?php 
if(!isset($hideHeader) || !$hideHeader){
?>	

<div style="margin:40px 0 0 10px;">
	<div class='menuheader'>
		<a href='<?php echo $clientRoot; ?>/index.php'>
			Biodiversity
		</a>
	</div>
	<div class='menuitem'>
		<a href='<?php echo $clientRoot; ?>/collections/index.php'>
			Search Collections
		</a>
	</div>
    <div class='menuitem'>
    	<a href='<?php echo $clientRoot; ?>/checklists/index.php'>
    		Species Lists
    	</a>
    </div>
    <div class='menuitem'>
    	<a href='<?php echo $clientRoot; ?>/ident/index.php'>
    		Identification Keys
    	</a>
    </div>
    <div class='menuitem'>
    	<a href='<?php echo $clientRoot; ?>/imagelib/index.php'>
    		Image Library
    	</a>
    </div>
    <div class='menuitem'>
    	<a href='<?php echo $clientRoot; ?>/misc/links.php'>
    		Links
    	</a>
    </div>
</div>
<?php } ?>
