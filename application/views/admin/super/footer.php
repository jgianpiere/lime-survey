<?php
/**
 * Footer view
 * Inserted in all pages
 */
?>

<!-- Footer -->
<footer class='footer'>
	<div class="container-fluid">
		<div class="row">
		    <!-- Link to manual -->
		    <div class="col-xs-6 col-md-4 col-lg-1 ">
		    	<a href='http://manual.limesurvey.org'><img alt='LimeSurvey - <?php eT("Online Manual");?>' title='LimeSurvey - <?php eT("Online manual");?>' src='<?php echo Yii::app()->getConfig('adminimageurl');?>docs.png' /></a>
		    </div>
		    
		    <!-- Support / Donate -->
		    <div  class="col-xs-6 col-md-4  col-lg-5 text-right"  >
		    	<a href='http://donate.limesurvey.org'><img alt='<?php eT("Support this project - Donate to "); ?>LimeSurvey' title='<?php eT("Support this project - Donate to "); ?>LimeSurvey!' src='<?php echo Yii::app()->getConfig('adminimageurl');;?>donate.png'/></a>
		    </div>
		    
		    <!-- Lime survey website -->
		    <div class="col-xs-6 col-md-4 col-lg-6 text-right">
		    	<a  title='<?php eT("Visit our website!"); ?>' href='http://www.limesurvey.org' target='_blank'>LimeSurvey</a><br /><?php echo $versiontitle."  ".$versionnumber." ".$buildtext;?>
		    </div>
		</div>
	</div>
</footer>


</body>
</html>

