<?php
$MyWiki = Peachy::newWiki("arwiki1"); //Loads the Configs/arwiki.cfg file
// $MyWiki = Peachy::newWiki("arwiki"); //Loads the Configs/arwiki.cfg file

function splitintosections (
    $d // content 
,$level = 2) {
    //		preg_match('/^(.*)((?<=^|\n)==[^=]+==.*)?$/Us',$data,$header);
    //		echo $data."\n\n\n";
    //		print_r($header);
    //		$d = $header[2];
    //		$header = $header[1];
    //		preg_match_all('/(?<=^|\n)==([^=]+)==(\n.*(?m)$(?-m))(?===[^=]+==.*|$)/AUs',$d,$sections,PREG_SET_ORDER);
            $ret = array();
    //		$ret[] = $header;
            $sections = array();
    
            $th = '';
            $tb = '';
            $s = 0;
            for ($i = 0; $i < strlen($d); $i++) {
                if ((substr($d,$i,$level) == str_repeat('=',$level)) and ($d{$i + $level} != '=') and (($i == 0) or ($d{$i - 1} == "\n"))) {
                    $j = 0;
                    while (($d{$i + $j} != "\n") and ($i + $j < strlen($d))) $j++;
                    if ((substr(trim(substr($d,$i,$j)),-1 * $level,$level) == str_repeat('=',$level)) and (substr(trim(substr($d,$i,$j)),(-1 * $level) - 1,1) != '=')) {
                        if ($s == 1) $sections[] = array($th,$tb);
                        else $header = $tb;
                        $s = 1;
                        $th = substr(trim(substr($d,$i,$j)),$level,-1 * $level);
                        $tb = '';
                        $i += $j - 1;
                    }
                } else {
                    $tb .= $d{$i};
                }
            }
    
            if ($s == 1) $sections[] = array($th,$tb);
            else $header = $tb;
    
            $ret[] = $header;
    
    
    //		print_r($sections);
            foreach ($sections as $section) {
                $id = trim($section[0]);
                $i = 1;
                while (isset($ret[$id])) {
                    $i++;
                    $id = trim($section[0]).' '.$i;
                }
                $ret[$id] = array('header'=>$section[0],'content'=>$section[1]);
            }
            return $ret;
        }

        
function doarchive (
    $page,
    $archiveprefix,
    $archivename,
    $age,
    $minarch,
    $minkeep,
    $defaulthead,
    $archivenow = [],
    $level ,// level of section that while archive
    // $noindex,
    $maxsects,
    $maxbytes,
    $htransform,
    $maxarchsize,
    $archnumberstart,
    $key  ,
    $pageofusername  ) {
        global $MyWiki;
        // get last rev and check page
		$rv = $page->history(1,'older',true);
        if (!is_array($rv)) return false;
		$rv2 = $rv;

        // check time
		$wpStarttime = gmdate('YmdHis', time());
		$tmp = date_parse($rv[0]['timestamp']);
        $wpEdittime = gmdate('YmdHis', gmmktime($tmp['hour'],$tmp['minute'],$tmp['second'],$tmp['month'],$tmp['day'],$tmp['year']));
        unset($tmp);

        // sections
        $cursects = splitintosections($rv[0]['*'],$level);
        
        // what is this?
        $ans = array();
		$anr = array();
		foreach ($archivenow as $k => $v) $archivenow[$k] = trim($v);
		foreach ($archivenow as $v) {
			$ans[] = $v;
			if (strpos($v,':') !== false) {
				$anr[] = str_replace('{{','{{tlu|',$v);
			} else {
				$anr[] = str_replace('{{','{{tl|',$v);
			}
		}
        // // end


        $done = false;
		$lastrvid = null;
		while (!$done) {
            // make it 5000 
			$rv =  $page->history(2000,$dir = 'older',false,$lastrvid);
			foreach ($rv as $rev) {
                // var_dump($rev['timestamp']);
				if (preg_match('/(\d+)\-(\d+)\-(\d+)T(\d+):(\d+):(\d+)/',$rev['timestamp'],$m)) {
					$time = gmmktime($m[4],$m[5],$m[6],$m[2],$m[3],$m[1]);
					if ((time() - $time) >= ($age * 60 * 60)) {
						$done = true;
						break;
					}
				}
			}
			if ((!isset($rv[999])) and ($done == false)) break;
			$lastrvid = $rev['revid'];
			if( !$lastrvid ) break;
		}


        
		if ($lastrvid == NULL)
            $tmp = array(array('*'=>''));
        else
            $tmp = $page->history(1,'older',true,$lastrvid);
        
        $oldsects = splitintosections($tmp[0]['*'],$level);
		$header = $cursects[0];
		unset($cursects[0]);
		unset($oldsects[0]);
		$keepsects = array();
		$archsects = array();
		foreach ($oldsects as $id => $array) {
			if (!isset($cursects[$id])) {
				unset($oldsects[$id]);
			}
		}


        foreach ($cursects as $id => $array) {
			$an = false;
			foreach ($archivenow as $v) if (strpos($array['content'],$v) !== false) $an = true;
			if ((count($cursects) - count($archsects)) <= $minkeep) {
				$keepsects[$id] = $array;
			} elseif ($an == true) {
				$array['content'] = str_replace($ans,$anr,$array['content']);
				$archsects[$id] = $array;
			// } elseif (preg_match('/\{\{User:ClueBot III\/DoNotArchiveUntil\|(\d+)\}\}/',$array['content'],$m) && time() < $m[1]) {
			// 	$keepsects[$id] = $array;
			} elseif (preg_match('/\{\{لا للأرشفة\}\}/',$array['content'],$m)) {
				$keepsects[$id] = $array;
			} elseif (!isset($oldsects[$id])) {
				$keepsects[$id] = $array;
			} elseif (trim($array['content']) == trim($oldsects[$id]['content'])) {
				$archsects[$id] = $array;
			} else {
				$keepsects[$id] = $array;
			}
		}


        if (($maxsects > 0) or ($maxbytes > 0)) {
			$i = 0;
			$b = 0;
			$keepsects = array_reverse($keepsects,true);
			foreach ($keepsects as $id => $array) {
				$i++;
				$b += strlen($array['content']);
				if (($maxsects > 0) and ($i > $maxsects)) {
					$archsects[$id] = $array;
					unset($keepsects[$id]);
				} elseif (($maxbytes > 0) and ($b > $maxbytes)) {
					$archsects[$id] = $array;
					unset($keepsects[$id]);
				}
			}
			$keepsects = array_reverse($keepsects,true);
		}

        
		if ($htransform != '') {
			$search = array();
			$replace = array();
			$transforms = explode('&&&',$htransform);
			foreach ($transforms as $v) {
				$v = explode('===',$v,2);
				$search[] = $v[0];
				$replace[] = $v[1];
			}
			foreach ($archsects as $id => $array) $archsects[$id]['header'] = preg_replace($search,$replace,$array['header']);
		}

		foreach ($oldsects as $id => $array) $tmpsectsprintr['oldsects'][] = $id;
		foreach ($cursects as $id => $array) $tmpsectsprintr['cursects'][] = $id;
		foreach ($keepsects as $id => $array) $tmpsectsprintr['keepsects'][] = $id;
		foreach ($archsects as $id => $array) $tmpsectsprintr['archsects'][] = $id;

		// print_r($tmpsectsprintr);
        // need work
        // var_dump((count($archsects)));
        $corect_user = false;
        $archiveprefixold = $archiveprefix;
        if ((count($archsects) > 0) and (count($archsects) >= $minarch)) {
            $pdata = $header;
			foreach ($keepsects as $array) { $pdata .= str_repeat('=',$level).$array['header'].str_repeat('=',$level).$array['content']; }
			// echo '$pdata = '.$pdata."\n\n\n\n";
            $get_title = $page->get_title();
            $pass = "lokas";
			if (substr(strtolower(str_replace('_',' ',$archiveprefix)),0,strlen($get_title)) != strtolower($get_title)) {
				global $pass;
				$ckey = trim(md5(trim($get_title).trim($archiveprefix).trim($pass)));
				if (trim($key) != $ckey) {
					echo 'Incorrect key and archiveprefix.  $archiveprefix=\''.$archiveprefix.'\';$correctkey=\''.$ckey.'\';'."\n";
					$archiveprefix = $get_title."/أرشيف ";
                    $corect_user = true;
				}
			}

            
            if ($age == '99999'){
                $age = 0;
            }


            
            $temp = $MyWiki->prefixindex(str_replace(["نقاش المستخدمة:","نقاش المستخدم:"],"",$archiveprefix),3,null);
            $temp_array = [];
            foreach($temp as $singlietemp){
                $n = str_replace($archiveprefix,"",$singlietemp["title"]);
                if (preg_match("~^[0-9]+$~",$n)) {
                    $temp_array[] = intval($n);
                }
            }

            $i = $archnumberstart;



            if (count($temp_array) & $i == max($temp_array)) {
                $apage = $archiveprefix.gmdate(str_replace('%%i',$i,$archivename),(time() - ($age * 60 * 60)));
            }else{
                $i = max($temp_array);
                $apage = $archiveprefix.gmdate(str_replace('%%i',$i,$archivename),(time() - ($age * 60 * 60)));
            }

            // if (($maxarchsize > 10000) and (strpos($archivename,'%%i') !== false)){
            //     while (strlen(strval($page->get_text())) > $maxarchsize) {
            //         $apage = $archiveprefix.gmdate(str_replace('%%i',$i,$archivename),(time() - ($age * 60 * 60)));
            //         $i++;
            //     }
            // }
            $s = $i;

            // chick taok page
            $lokatest_page = $MyWiki->initPage($apage);
                
            foreach ($archsects as $array) { $loka_7755adata = str_repeat('=',$level).$array['header'].str_repeat('=',$level).$array['content']; }

            $loka_7755adata = intval($lokatest_page->get_length()) + intval(strlen($loka_7755adata));
            
            if ($lokatest_page->get_length() >= 100000 || $loka_7755adata >= 990000 ) {
                // var_dump("archnumberstart",$archnumberstart);
                $s = $i + 1;
                // var_dump("archnumberstart",$archnumberstart);
                $apage = $archiveprefix.gmdate(str_replace('%%i',$s,$archivename),(time() - ($age * 60 * 60)));
                $defaulthead = "\n{{تصفح أرشيف|$s}}\n{{تمت الأرشفة}}\n{{أرشيف صفحة رئيسية}}\n";
            }

            
            // $MyWiki = Peachy::newWiki("arwiki"); //Loads the Configs/arwiki.cfg file
            // new code
            $myarcive = $MyWiki->initPage($apage);
            // $page1 = $MyWiki->initPage($page->get_title());
            
            // new code
            // need  
            $adata = (($x = strval($myarcive->get_text())) ? $x : $defaulthead."\n")."\n";

			foreach ($archsects as $array) { $adata .= str_repeat('=',$level).$array['header'].str_repeat('=',$level).$array['content']; }

            // 

            
            $pdata = preg_replace_callback("~{{\s*أرشفة آلية(.*?)(\d*)\s*}}~",function($match) use ($s,$corect_user,$archiveprefixold,$archiveprefix) {
                if ($corect_user) {
                    $str = str_replace($archiveprefixold,$archiveprefix,$match[1]);
                }else{
                    $str = $match[1];
                }
                $str = str_replace("|عددي","",$str);
                $str = str_replace("|عددى","",$str);
                $str = str_replace("عددي|","",$str);
                $str = str_replace("عددى|","",$str);
                $temp = '{{أرشفة آلية'.$str.$s.'}}';
                // var_dump("tem=>".$temp);
                return $temp;

            },$pdata);

            $number_of_type_loka = "";

          if (count($archsects) > 2 && count($archsects) <= 10 ) {
            $number_of_type_loka = " ".count($archsects). " نقاشات ";
          }elseif (count($archsects) === 1) {
            $number_of_type_loka =  " نقاش واحد ";
          
          }elseif (count($archsects) === 2) {
            $number_of_type_loka =  " نقاشان ";
          }else {
            $number_of_type_loka = " ".count($archsects)." نقاش ";
          }

        //   if (!strpos($adata,"{{تصفح أرشيف")) {
        //     $adata = "{{تصفح أرشيف|$s}}"  . $adata;
        // }

        if (!strpos($adata,"{{تمت الأرشفة}}")) {
            $adata = "{{تمت الأرشفة}}\n"  . $adata;
        }
        
          if (!strpos($adata,"{{أرشيف صفحة رئيسية}}")) {
              $adata = "{{أرشيف صفحة رئيسية}}\n" . $adata;
          }
        
        
          echo "main page is => $pageofusername\n";
          echo "archivepage  page is => $apage\n";



        //   $MyWiki = Peachy::newWiki("arwiki"); //Loads the Configs/arwiki.cfg file
        //   $myarcive = $MyWiki->initPage($apage);
        //   $page = $MyWiki->initPage($pageofusername);



          $cannot_edit = false;
                foreach($myarcive->get_protection() as $arr){
                    if ($arr["type"] == "edit" & $arr["level"] == "sysop" ) {
                        $cannot_edit = true;
                    }
                }
                if (!$cannot_edit) {
                 
		 $arcommit = 'أرشفة'.$number_of_type_loka.' من [['.$pageofusername.']]';
   		 $encommit = 'أرشفة'.$number_of_type_loka .' إلى  [['.$apage.']]';

  		  if (!$myarcive->edit($adata,$arcommit,true)){
                        return false;
                    }
		    if (!$page->edit($pdata,$encommit,true)) {
                        $myarcive->edit($x,'الغاء أرشفة '.$number_of_type_loka.' من [['.$apage.']]. (Archive failed) ',true);
                        return false;
                    }
                    
                }

          
        
        }

}
