<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Thomas Kieslich <thomaskieslich@gmx.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

if (substr_count($_SERVER['SCRIPT_FILENAME'], 'typo3conf') > 0) {
	define('TYPO3_MOD_PATH','../typo3conf/ext/tkcropthumbs/');
}
else {
	define('TYPO3_MOD_PATH','ext/tkcropthumbs/');
}

$BACK_PATH='../../../typo3/';

// include
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
// lang
$LANG->includeLLFile('EXT:tkcropthumbs/locallang.xml');

$IMG_BACK_PATH='../../../';

if ($_GET['action']) {
	$GLOBALS['TYPO3_DB']->exec_SELECTquery('id','tx_tkcropthumbs','image="'.$_GET['image'].'" AND uid='.$_GET['uid']);
	$cnt = $GLOBALS['TYPO3_DB']->sql_affected_rows();
	if ($cnt==0) {
		$table       = 'tx_tkcropthumbs';
		$fieldValues = array (
				'image' => $_GET['image'],
				'uid'   => $_GET['uid'],
				'tstamp'=> time()
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $fieldValues);
	}
	//update values
	$table       = 'tx_tkcropthumbs';
	$where       = 'image = "'.$_GET['image'].'" AND uid = '.$_GET['uid'];
	$fieldValues = array (
			'x'		=> $_GET['x'],
			'y'		=> $_GET['y'],
			'x2'	=> $_GET['x2'],
			'y2'	=> $_GET['y2'],
			'tstamp'=> time()
	);
	$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);
}

if ($_GET['reset']) {
	//delete values
	$table       = 'tx_tkcropthumbs';
	$where       = 'image = "'.$_GET['image'].'" AND uid = '.$_GET['uid'];
	$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);
}

//get values
$select = 'x,y,x2,y2';
$table  = 'tx_tkcropthumbs';
$where  = 'image = "'.$_GET['image'].'" AND uid = '.$_GET['uid'];
$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
$values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

// resize img
$imgsize=getimagesize(PATH_site.$_GET["image"]);
$max_x=$imgsize[0];
$max_y=$imgsize[1];

if (!$values["x2"]) {
	$values["x"]=$values["y"]=0;
	$values["x2"]=$max_x;
	$values["y2"]=$max_y;
}

$zoom=1;
if (($max_x>600)||($max_y>600)) {
	if ($max_y>$max_x) {
		$zoom=600/$max_y;
		$max_x=$max_x*$zoom;
		$max_y=$max_y*$zoom;
		$height = 600;
	}
	else {
		$zoom=600/$max_x;
		$max_x=$max_x*$zoom;
		$max_y=$max_y*$zoom;
		$width =  600;
	}
}
else {
	$zoom=1;
}

$aspect;
if($_GET['aspectratio'] == 1)$aspect = 'aspectRatio: 1,';
else if($_GET['aspectratio'] == 2)$aspect = 'aspectRatio: 1.33,';
else if($_GET['aspectratio'] == 3)$aspect = 'aspectRatio: 1.44,';
else if($_GET['aspectratio'] == 4)$aspect = 'aspectRatio: 1.77,';

//field
$ratioField = '';
if($aspect)$ratioField = '<label for="ratio">'.$LANG->getLL("aspectratio").'&nbsp;&nbsp;&nbsp;</label><input type="text" size="9" id="ratio" name="ratio" readonly />';
else $ratioField = '<label for="ratio">'.$LANG->getLL("aspectratio").'&nbsp;&nbsp;&nbsp;</label><input type="text" size="9" id="ratio" name="ratio" />';

// init window
$template=new template;
$template->charset = "utf-8";
$template->docType = "xhtml_trans";

$template->JScode = '
<link rel="stylesheet" href="res/css/jquery.Jcrop.css" type="text/css" />
<script language="JavaScript" src="res/js/jquery.min.js"></script>
<script language="JavaScript" src="res/js/jquery.Jcrop.js"></script>

<script language="Javascript">
var jcrop_api="";
jQuery(document).ready(function(){' .
		'jQuery("#ratiounset").attr("disabled","disabled");
jcrop_api=$.Jcrop("#cropbox",{
setSelect: [ '.$values['x']*$zoom.', '.$values['y']*$zoom.', '.$values['x2']*$zoom.
		', '.$values['y2']*$zoom.' ],'.$aspect.'
onChange: showCoords,
onSelect: showCoords
});'.
	'jQuery("#x").change(changeSelection);' .
	'jQuery("#y").change(changeSelection);' .
	'jQuery("#x2").change(changeSelection);' .
	'jQuery("#y2").change(changeSelection);' .
	'jQuery("#w").change(widthchange);' .
	'jQuery("#h").change(heightchange);' .
	'jQuery("#ratio").change(ratiochange);' .
	'jQuery("#ratiounset").click(ratiounset);
})
function showCoords(c)
{
jQuery("#x").val(Math.round(c.x/'.$zoom.'));
jQuery("#y").val(Math.round(c.y/'.$zoom.'));
jQuery("#x2").val(Math.round(c.x2/'.$zoom.'));
jQuery("#y2").val(Math.round(c.y2/'.$zoom.'));
jQuery("#w").val(Math.round(c.w/'.$zoom.'));
jQuery("#h").val(Math.round(c.h/'.$zoom.'));
jQuery("#ratio").val(((c.w/'.$zoom.')/(c.h/'.$zoom.')).toFixed(2));
};
function changeSelection(){' .
	'x1=$("#x").val();' .
	'x2=$("#x2").val();' .
	'y1=$("#y").val();' .
	'y2=$("#y2").val();
	jcrop_api.setSelect([x1,y1,x2,y2]);' .
	'return false
}' .
'function widthchange(){
	x1=$("#x").val();' .
	'x2=Math.round($("#x").val())+Math.round($("#w").val());' .
	'y1=$("#y").val();' .
	'y2=$("#y2").val();
	jcrop_api.setSelect([x1,y1,x2,y2]);' .
	'return false
}' .
'function heightchange(){
	x1=$("#x").val();' .
	'x2=$("#x2").val();' .
	'y1=$("#y").val();' .
	'y2=Math.round($("#y").val())+Math.round($("#h").val());
	jcrop_api.setSelect([x1,y1,x2,y2]);' .
	'return false
}' .
'function ratiochange(){
	myratio=$("#ratio").val();
	jQuery("#cropbox").Jcrop({aspectRatio:myratio});' .
	'jQuery("#ratiounset").attr("disabled","");' .
	'jQuery("#ratiochange").attr("disabled","true");
	return false
}
function ratiounset(){
	jQuery("#cropbox").Jcrop({aspectRatio:""});' .
	'jQuery("#ratiounset").attr("disabled","true");' .
	'jQuery("#ratiochange").attr("disabled","");
	return false
}
</script>
';
$template->bodyTagAdditions="style=\"margin:0px;padding:0px;\"";
$template->backPath=$BACK_PATH;
//$template->styleSheetFile ='';


$thumbnail=$IMG_BACK_PATH.$_GET["image"];

echo $template->startPage("TYPO3 Crop Thumbs - ".$_GET["image"]);

echo '<div style="float:left; margin-right: 15px;">';
if ($width) echo "<img src=\"$thumbnail\" width=\"$width\" id=\"cropbox\" />";
elseif ($height) echo "<img src=\"$thumbnail\" height=\"$height\" id=\"cropbox\" />";
else echo "<img src=\"$thumbnail\" id=\"cropbox\" />";
echo '</div>';

echo $LANG->getLL("editor_title").'
<form name="crop" method="get">
    <fieldset>
        <label for="x">X1</label><input type="text" size="4" id="x" name="x" value="'.$values['x'].'" />
        <label for="y">Y1</label><input type="text" size="4" id="y" name="y" value="'.$values['y'].'" /><br />
        <label for="x2">X2</label><input type="text" size="4" id="x2" name="x2" value="'.$values['x2'].'" />
        <label for="y2">Y2</label><input type="text" size="4" id="y2" name="y2" value="'.$values['y2'].'" /><br />
        <label for="w">W&nbsp;</label><input type="text" size="4" id="w" name="w" readonly />
        <label for="h">H&nbsp;</label><input type="text" size="4" id="h" name="h" readonly />
		'.$ratioField.'
    </fieldset>
    <input type="hidden" name="action" value="submit" />
    <input type="hidden" name="image" value="'.$_GET['image'].'" />
    <input type="hidden" name="uid" value="'.$_GET['uid'].'" />
    <input type="submit" value="'.$LANG->getLL("save_values").'" onClick="self.close();" />
</form>
<br /><br />
<form name="reset" method="get">
    <input type="hidden" name="reset" value="submit" />
    <input type="hidden" name="image" value="'.$_GET['image'].'" />
    <input type="hidden" name="uid" value="'.$_GET['uid'].'" />
    <input type="submit" value="'.$LANG->getLL("reset_values").'" onClick="self.close();" id="reset" />
</form>';

//display aspect ratio
if($_GET['aspectratio'] > 0) {
	echo $LANG->getLL("aspectratio").': '.$LANG->getLL("aspectratio.I.".$_GET['aspectratio']);
}


echo $template->endPage();
?>