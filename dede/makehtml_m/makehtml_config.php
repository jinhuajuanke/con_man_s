<?php
/**
 * 生成静态页配置
 *
 * @license        http://www.dedemao.com/dedeplug/makehtml_m.html
 * @link           http://www.dedemao.com
 */
require_once(dirname(__FILE__)."/../config.php");
if(empty($dopost)) $dopost = "";

$configfile = DEDEDATA.'/config.cache.inc.php';
//更新配置函数
function ReWriteConfig()
{
    global $dsql,$configfile;
    if(!is_writeable($configfile))
    {
        echo "配置文件'{$configfile}'不支持写入，无法修改系统配置参数！";
        exit();
    }
    $fp = fopen($configfile,'w');
    flock($fp,3);
    fwrite($fp,"<"."?php\r\n");
    $dsql->SetQuery("SELECT `varname`,`type`,`value`,`groupid` FROM `#@__sysconfig` ORDER BY aid ASC ");
    $dsql->Execute();
    while($row = $dsql->GetArray())
    {
        if($row['type']=='number')
        {
            if($row['value']=='') $row['value'] = 0;
            fwrite($fp,"\${$row['varname']} = ".$row['value'].";\r\n");
        }
        else
        {
            fwrite($fp,"\${$row['varname']} = '".str_replace("'",'',$row['value'])."';\r\n");
        }
    }
    fwrite($fp,"?".">");
    fclose($fp);
}

function checkMobileTemplate()
{
    global $dsql,$cfg_basedir,$cfg_templets_dir,$dedemao_path;
    if(!$dedemao_path) $dedemao_path = '/m/';
    $row = $dsql->GetOne("Select * From `#@__homepageset`");
    $row['templet'] = MfTemplet($row['templet']);
    $row['templet'] =str_replace('.htm','_m.htm',$row['templet']);
    $index_path = $cfg_basedir . $cfg_templets_dir . "/" . $row['templet'];
    if ( !file_exists($index_path) )
    {
        return '<p style="color:red">手机版首页模板不存在</p>';
    }
    $content = file_get_contents($index_path);
    if(strstr($content,$dedemao_path)===false){
        return '<p style="color:red">手机模板css路径不正确，如果确定路径正确，可忽略本提示。原因：请修改手机模板中的css，js等路径，将相对路径改为绝对路径，以免造成生成的手机静态网页布局混乱。</p>';
    }

}

function getRandString()
{
    $str = strtoupper(md5(uniqid(md5(microtime(true)),true)));
    return substr($str,0,8).'-'.substr($str,8,4).'-'.substr($str,12,4).'-'.substr($str,16,4).'-'.substr($str,20);
}

function setCache()
{
    global $cfg_version;
    $first_use = false;
    $use_time = date('Y-m-d');
    $txt = DEDEDATA.'/module/makehtml_m.txt';
    if(!file_exists($txt))
    {
        $id = getRandString();
        $first_use = true;
        $fp = fopen($txt,'w');
        $tData['id'] = $id;
        $tData['time'] = $use_time;
        fwrite($fp,serialize($tData));
        fclose($fp);
    }else{
        $fp = fopen($txt,'r');
        $content = fread($fp, filesize($txt));
        fclose($fp);
        $content = unserialize($content);
        $id = $content['id'];
        $use_time = $content['time'];
        $tData['id'] = $id;
        $tData['time'] = date('Y-m-d');
        $fp = fopen($txt,'w');
        fwrite($fp,serialize($tData));
        fclose($fp);
    }
    if($first_use || $use_time!=date('Y-m-d')){
        echo '<script>
                var _hmt = _hmt || [];
                (function() {
                    var hm = document.createElement("script");
                    hm.src = "//www.dedemao.com/api/stat.php?id='.$id.'&v='.$cfg_version.'";
                    var s = document.getElementsByTagName("script")[0];
                    s.parentNode.insertBefore(hm, s);
                })();
            </script>';
    }
}

//保存配置的改动
if($dopost=="save")
{
    $info = $_POST['info'];
    $data = $_POST['data'];
    foreach($data as $k=>$v)
    {
        $row = $dsql->GetOne("SELECT varname FROM `#@__sysconfig` WHERE varname LIKE '$k' ");
        if(is_array($row))
        {
            //存在就更新
            $dsql->ExecuteNoneQuery("UPDATE `#@__sysconfig` SET `value`='$v' WHERE varname='$k' ");
        }else{
            $row = $dsql->GetOne("SELECT aid FROM `#@__sysconfig` ORDER BY aid DESC ");
            $aid = $row['aid'] + 1;
            $inquery = "INSERT INTO `#@__sysconfig`(`aid`,`varname`,`info`,`value`,`type`,`groupid`)
    VALUES ('$aid','$k','{$info[$k]}','$v','string','8')";
            $rs = $dsql->ExecuteNoneQuery($inquery);
            if(!$rs)
            {
                ShowMsg("有非法字符！");
                exit();
            }
            if(!is_writeable($configfile))
            {
                ShowMsg("成功保存，但由于 $configfile 无法写入，因此不能更新配置文件！");
                exit();
            }
        }

    }
    ReWriteConfig();
    ShowMsg("成功更改配置！", "makehtml_config.php");
    exit();
}
$dsql->SetQuery("Select * From `#@__sysconfig` where groupid='8' order by aid asc");
$dsql->Execute();
$i = 1;
$data = array();
while($row = $dsql->GetArray()) {
    $data[$row['varname']] = $row['value'];
    $i++;
}

if(!isset($data['dedemao_usetype']) && !isset($data['dedemao_usetype'])){
    $data['dedemao_usetype'] = 1;
    $data['dedemao_usearc'] = 1;
}
//检查手机模板路径
$msg = checkMobileTemplate();
include DedeInclude('makehtml_m/templets/makehtml_config.htm');
setCache();