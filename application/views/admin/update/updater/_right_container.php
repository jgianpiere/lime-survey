<?php
/**
 * This view display the right container of the updater
 * The ajax code change the active step.
 */
?>

	<!-- the ajax loader -->
	<div id="ajaxContainerLoading" style="text-align : center; margin-top: 70px; margin-bottom: 100px; display: none">
		<p><?php eT('PLease wait, data loading...');?></p>
		<img src="<?php echo Yii::app()->baseUrl;?>/images/ajax-loader.gif" alt="loading..."/>    <br/>
	</div>
	
	<!-- Here come the different steps content. Content is loaded by the ajax request (see ./steps for html views)	-->
	<div id="updaterContainer">
		<!-- content loaded by ajax -->
	</div>		
