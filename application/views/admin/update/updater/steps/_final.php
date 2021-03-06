<?php
/**
 * This view display the result of the update
 * @var int $destinationBuild the destination build
 */
?>

<h2 class="maintitle"><?php eT('Update complete !'); ?></h2>
<div class="updater-background">
<?php
	echo sprintf(gT('Buildnumber was successfully updated to %s.'),Yii::app()->session['updateinfo']['toversion']).'<br />';
    eT('The update is now complete!'); 
?> 
    <br /> 
<?php  eT('As a last step you should clear your browser cache now.'); ?> 
  <br /> 

	<a class="button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only limebutton" href="<?php echo Yii::app()->createUrl("admin/update"); ?>" role="button" aria-disabled="false">
		<span class="ui-button-text"><?php eT('Back to main menu'); ?></span>
	</a>	    
		

</div>
	
