<?php
function smarty_block_cacher($params, $content, $smarty, &$repeat) {
    $trace = reset(debug_backtrace());
    $data=$smarty->getTemplateVars('_gsdata');

    $ids=array($data['gspgid'],basename($trace['file']));
    if (isset($params['name']) && $params['name']) array_push($ids,$params['name']);
    if (!isset($params['static']) || !$params['static']) array_unshift($ids,$_SERVER['REQUEST_URI']);

    $idstr=implode(':',$ids);
    $uid=md5($idstr);

    gs_eventer::unsubscribe('record_after_load_records', 'cacher_listener::cache_depends_'.$uid);

    $rs=new cacher_cache;
    if($repeat) {
        $c=$rs->find_records(array('uid'=>$uid),array('uid','text'))->first();
        if ($c) {
            $repeat=FALSE;
            return $c->text;
        }
        gs_eventer::subscribe('record_after_load_records', 'cacher_listener::cache_depends_'.$uid);
    }
    if(!$repeat) {
        $c=$rs->find_records(array('uid'=>$uid))->first(true);
        $c->address=$idstr;
        $c->text=$content;
        $c->commit();
        return $content;
    }

}

?>

