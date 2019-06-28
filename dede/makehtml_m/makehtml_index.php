<?php
    require_once (dirname(__FILE__) . "/../../include/common.inc.php");
    require_once DEDEINC."/arc.partview.class.php";
    $GLOBALS['_arclistEnv'] = 'index';
    $row = $dsql->GetOne("Select * From `#@__homepageset`");
    $row['templet'] = MfTemplet($row['templet']);

    $pv = new PartView();
    $row['templet'] =str_replace('.htm','_m.htm',$row['templet']);
    if ( !file_exists($cfg_basedir . $cfg_templets_dir . "/" . $row['templet']) )
    {
        echo "模板文件不存在，无法解析文档！";
        exit();
    }
    $pv->SetTemplet($cfg_basedir . $cfg_templets_dir . "/" . $row['templet']);
    $row['showmod'] = isset($row['showmod'])? $row['showmod'] : 0;
	$GLOBALS['dedemao_path'] = isset($GLOBALS['dedemao_path']) ? $GLOBALS['dedemao_path'] : '/m';
    $pv->SaveToHtml(DEDEROOT.$GLOBALS['dedemao_path'].'/index.html');
    ShowMsg("生成手机版主页HTML成功！<br><a target='_blank' href='{$GLOBALS['dedemao_path']}/index.html'>点击查看</a>","javascript:;");

?>