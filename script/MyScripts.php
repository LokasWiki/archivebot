<?php
/*
***************************************************************************************
* ⚠️ Warning ⚠️
*
* This code is old and not recommended for use.
* It is preferable to use the Python bot code as it is more effective and secure.
* This code is here solely for archival purposes and to prevent its loss, so it can be referred to at any time.
*
* Programmer joke: Why don't programmers like nature? It has too many bugs! 😄
*
* Another joke: If you read this code despite the warning, don't blame me if you get frustrated! 😜
***************************************************************************************
*/
//php -f D:\work\bot\Peachy-master\script\MyScripts.php >  D:\work\bot\Peachy-master\text.txt
require( dirname(__DIR__,1) .'/Init.php' );

require( dirname(__DIR__,1) .'/script/Lib2.php' );
// $MyWiki = Peachy::newWiki("arwiki"); //Loads the Configs/arwiki.cfg file



function readtemp($MyPage,$nameofuser){


    
    preg_match('~{{(?:\s*)أرشفة آلية(.*)}}~',$MyPage->get_text(),$m);
    // var_dump($m[0]);

    if (strpos($m[0],"عددي")) {
        $str = str_replace("|عددي","",$m[0]);
        $str = str_replace("|عددى","",$str);
        $str = str_replace("عددي|","",$str);
        $str = str_replace("عددى|","",$str);
        preg_match('~{{(?:\s*)أرشفة آلية(.*)}}~',$str,$m);
    }

    if (isset($m[1])) {

        // var_dump($m[0]);    
        $op = explode("|",$m[1]);

        $type = $op[1];
        
        $value = $op[2];
        
        $nameop = $op[3];

        preg_match("~{{\s*أرشفة آلية(.*?)(\d*)\s*}}~",$MyPage->get_text(),$number_archive);
        // $number_archive = (int) filter_var(str_replace($nameofuser,"",$nameop), FILTER_SANITIZE_NUMBER_INT);  
        
        $number_archive = isset($number_archive[2])? $number_archive[2]: false;

        if (isset($number_archive)) {
            $prefix = str_replace($number_archive,"",$nameop);
        }   else{
            
            $prefix = $nameop;
        }

        // $type = "ششششششش";
        // $value = 1;

        if (trim($type) == "قسم") {
            $age = $value * 24;
            if ($number_archive) {
                $defaulthead = "\n{{تصفح أرشيف|$number_archive}}\n{{تمت الأرشفة}}\n{{أرشيف صفحة رئيسية}}\n";
                doarchive($MyPage,$prefix," %%i",$age,0,0,$defaulthead,[],2,0,0,"",0,$number_archive,"",$nameofuser);
            }else{
                $defaulthead = "\n{{تصفح أرشيف|1}}\n{{تمت الأرشفة}}\n{{أرشيف صفحة رئيسية}}\n";
                doarchive($MyPage,$prefix," %%i",$age,0,0,$defaulthead,[],2,0,0,"",0,1,"",$nameofuser);
            }
        }else{

            $age = 1;
            if ($MyPage->get_length() >= ($value * 1000)) {
                if ($number_archive) {
                    $defaulthead = "\n{{تصفح أرشيف|$number_archive}}\n{{تمت الأرشفة}}\n{{أرشيف صفحة رئيسية}}\n";
                    doarchive($MyPage,$prefix," %%i",$age,0,0,$defaulthead,[],2,0,0,"",0,$number_archive,"",$nameofuser);
                }else{
                    $defaulthead = "\n{{تصفح أرشيف|1}}\n{{تمت الأرشفة}}\n{{أرشيف صفحة رئيسية}}\n";
                    doarchive($MyPage,$prefix," %%i",$age,0,0,$defaulthead,[],2,0,0,"",0,1,"",$nameofuser);
                } 
            }
            
        }




    }else{
        echo "skip $nameofuser\n";
    }
    

}

$tempname = "قالب:أرشفة آلية";
$list = $MyWiki->embeddedin($tempname,3,null);
foreach ($list as $key => $name) {
            // $MyWiki = Peachy::newWiki("arwiki1"); //Loads the Configs/arwiki.cfg file
            if (!strpos($name,'/')) {
                // $MyWiki = Peachy::newWiki("arwiki1"); //Loads the Configs/arwiki.cfg file
                $MyPage = $MyWiki->initPage( $name );

                $cannot_edit = false;
                foreach($MyPage->get_protection() as $arr){
                    if ($arr["type"] == "edit" & $arr["level"] == "sysop" ) {
                        $cannot_edit = true;
                    }
                }
                if (!$cannot_edit) {
                    readtemp($MyPage,$name);
                    sleep(3);  
                }
                
            }else{
                echo "skip $name";
            }
    
}

// var_dump($list);
// $defaulthead = "
// {{تصفح أرشيف|57}}
// {{تمت الأرشفة}}
// {{أرشيف صفحة رئيسية}}
// ";
// $age = 24*3;

// doarchive($MyPage,"نقاش المستخدم:فيصل/أرشيف"," %%i",$age,0,0,$defaulthead,[],2,0,0,"",0,57,"");




// // $name = "نقاش المستخدم:لوقا";

// // parsetemplate($name);



// $MyWiki = Peachy::newWiki("arwiki"); //Loads the Configs/arwiki.cfg file

// $MyPage = $MyWiki->initPage( "MyTestPage" ); // اسم الصفحة التي ستعدل
// $MyPageTxt = $MyPage->get_text(); // جلب محتوى الصفحة

// $MyNewPageTxt = $MyPageTxt . "\n [[تصنيف:مقالات عدلها البوت]]";  // إضافة سطر جديد في آخر محتوى الصفحة

// $MyPage->edit($MyNewPageTxt,"تعديل بوتي");  // تطبيق التعديل بإرسال المحتوى الجديد + تعليق حول هذا التعديل