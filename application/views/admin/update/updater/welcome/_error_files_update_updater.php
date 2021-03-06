<?php 
/**
 * This view displays the Step 1 : pre-installation checks. 
 * The javascript inject it inside the div#updaterContainer, in the _updater view. (like any steps)
 * 
 * @var object $localChecks an object containing all the checks results
 * @var int $destinationBuild the destination build 
 */
?>

<h2 class="maintitle" style="color: red;"><?php eT("write error !"); ?></h2>
<?php 
	if( isset($serverAnswer->html) )
		echo $serverAnswer->html;
?>
<p>
	<strong><?php eT("Those files/directories are not writable :")?></strong>
	<br/>
	<?php foreach( $localChecks->readOnly as $readonly ):?>
		<?php echo $readonly."<br/>";?>
	<?php endforeach; ?>
	<br/>
	<?php eT('When checking your installation we found one or more problems. Please check for any error messages above and fix these before you can proceed.'); ?>
	
	<a class="button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only limebutton" href="<?php echo Yii::app()->createUrl("admin/update"); ?>" role="button" aria-disabled="false">
		<span class="ui-button-text"><?php eT("Cancel"); ?></span>
	</a>		
</p>

