<?php
/**
 * This file display the Updater Update Message
 */
?>
<h2 class="maintitle"><?php eT("The ComfortUpdater need to be updated");?></h2>

<?php 
	if( isset($serverAnswer->html) )
		echo $serverAnswer->html;
?>

<div class="updater-background">
	<?php eT("It seems you didn't updated regularly your LimeSurvey installation.");?>
	<br/>
	<?php eT("Before you proceed to the LimeSurvey update, we must first update the updater.");?>
	<br/>
	<?php eT("At the end of the process, we'll proceed to the LimeSurvey update");?>
</div>



<!-- The form launching the update of the updater. -->
<?php echo CHtml::beginForm('update/sa/updateUptader', 'post', array('id'=>'launchUpdateUpdaterForm')); ?>
	<?php  echo CHtml::hiddenField('destinationBuild' , $serverAnswer->destinationBuild); ?>
	<a class="button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only limebutton" href="<?php echo Yii::app()->createUrl("admin/update"); ?>" role="button" aria-disabled="false">
		<span class="ui-button-text"><?php eT("Cancel"); ?></span>
	</a>
	<?php echo CHtml::submitButton(gT("Continue"), array('class'=>"ajax_button ui-button ui-widget ui-state-default ui-corner-all",)); ?>
<?php echo CHtml::endForm(); ?>

<!-- this javascript code manage the step changing. It will catch the form submission, then load the ComfortUpdater for the required build -->
<script type="text/javascript" src="<?php echo Yii::app()->baseUrl; ?>/scripts/admin/comfortupdater/comfortUpdateNextStep.js"></script>
<script>
	$('#launchUpdateUpdaterForm').comfortUpdateNextStep({'step': 0});	
</script>