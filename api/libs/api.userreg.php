<?php

 function zb_rand_string($size=4) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string.= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return ($string);
 }
 

 function zb_rand_digits($size=4) {
    $characters = '0123456789';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string.= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return ($string);
 }

 function zb_AddressGetBuildApts($buildid) {
     $buildid=vf($buildid,3);
     $query="SELECT * from `apt` WHERE `buildid`='".$buildid."'";
     $allapts=simple_queryall($query);
     $result=array();
     if (!empty ($allapts)) {
         foreach ($allapts as $io=>$each) {
             $result[$each['apt']]=$each['id'];
         }
     }
     return ($result);
 }
 
 //apt check javascript code
 function web_AddressBuildShowAptsCheck($buildid) {
     $buildid=vf($buildid,3);
     $allapts=zb_AddressGetBuildApts($buildid);
     $result='
         <script type="text/javascript">
        function aptusedalert() {
              alert(\''.__('The apartment has one lives, we have nothing against, just be warned').'\');
        }
        
        function aptemptyalert() {
            alert(\''.__('Are you sure you want to keep the homeless from this user').'\');
        }


        function checkapt()
        {
        var x=document.getElementById("apt").value;
        
        if (x == \'\') {
            aptemptyalert();
        }
  
         ';
     if (!empty ($allapts)) {
         foreach ($allapts as $aptnum=>$aptid) {
             if (!empty ($aptnum)) {
             $result.='
                 if (x == '.$aptnum.') {
                     aptusedalert();
                 }
                 ';
             }
         }
     }
     
     $result.='}
        </script>';
     return ($result);
 }

 

function web_UserRegFormLocation() {
    $aptsel='';
    $servicesel='';
    if (!isset($_POST['citysel'])) {
        $citysel=web_CitySelectorAc(); // onChange="this.form.submit();
        $streetsel='';
    } else {
        $citydata=zb_AddressGetCityData($_POST['citysel']);
        $citysel=$citydata['cityname'].'<input type="hidden" name="citysel" value="'.$citydata['id'].'">';
        $streetsel=web_StreetSelectorAc($citydata['id']);
    }

    if (isset($_POST['streetsel'])) {
        $streetdata=zb_AddressGetStreetData($_POST['streetsel']);
        $streetsel=$streetdata['streetname'].'<input type="hidden" name="streetsel" value="'.$streetdata['id'].'">';
        $buildsel= web_BuildSelectorAc($_POST['streetsel']);

    } else {
        $buildsel='';
    }

     if (isset($_POST['buildsel'])) {
        $submit_btn='';
        $builddata=zb_AddressGetBuildData($_POST['buildsel']);
        $buildsel=$builddata['buildnum'].'<input type="hidden" name="buildsel" value="'.$builddata['id'].'">';
        $aptsel=web_AddressBuildShowAptsCheck($builddata['id']).web_AptCreateForm();
        $servicesel=multinet_service_selector();
        //contrahens user diff
        $alter_conf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        if (isset($alter_conf['LOGIN_GENERATION'])) {
            if ($alter_conf['LOGIN_GENERATION']=='DEREBAN') {
                $agentCells=wf_TableCell(zb_RegContrAhentSelect('regagent', $alter_conf['DEFAULT_ASSIGN_AGENT']));
                $agentCells.=wf_TableCell(__('Contrahent name'));
                $submit_btn.=wf_TableRow($agentCells, 'row2');
                
            }
        }
        $submit_btn.='
            <tr class="row3">
            <td><input type="submit" value="'.__('Save').'"></td> <td></td>
            </tr>
            ';
     } else {
         $submit_btn='';
     }
     


    $form='
        <table width="100%" border="0">
        <form action="" method="POST">
        <tr class="row3">
        <td width="50%">'.$citysel.'</td> <td>  '.__('City').'</td>
        </tr>
        <tr class="row3">
        <td>'.$streetsel.'</td> <td>  '.__('Street').'</td>
        </tr>
        <tr class="row3">
        <td>'.$buildsel.'</td>  <td> '.__('Build').'</td>
        </tr>
        <tr class="row3">
        <td>'.$aptsel.'</td> <td>'.__('Apartment').'</td>
        </tr>
        <tr class="row3">
        <td>'.$servicesel.'</td> <td>'.__('Service').'</td>
        </tr>
        <br>
        '.$submit_btn.'
        </form>
        </table>
        ';
    return($form);
}

function zb_AllBusyLogins() {
            $query="SELECT `login` from `users`";
            $alluserlogins=  simple_queryall($query);
            $result=array();
            if (!empty($alluserlogins)) {
                foreach ($alluserlogins as $io=>$each) {
                    $result[$each['login']]=$each['login'];
                }
               
            }
            return ($result);
}

function zb_RegLoginFilter($login) {
    $login=str_replace(' ', '_', $login);
    $result= preg_replace("#[^a-z0-9A-Z_]#Uis",'',$login);
    return ($result);
}

function zb_RegLoginProposal($cityalias,$streetalias,$buildnum,$apt,$ip_proposal,$agentid='') {
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $result='';
    if (isset($alterconf['LOGIN_GENERATION'])) {
        $type=$alterconf['LOGIN_GENERATION'];
        //default address based generation
        if ($type=='DEFAULT') {
             $result=$cityalias.$streetalias.$buildnum.'ap'.$apt.'_'.zb_rand_string();
             $result=  zb_RegLoginFilter($result);
        }
        
        //same as default but without random
        if ($type=='ONLYADDRESS') {
             $result=$cityalias.$streetalias.$buildnum.'ap'.$apt;
             $result=  zb_RegLoginFilter($result);
        }
        
        //use an timestamp
        if ($type=='TIMESTAMP') {
            $result=time();
        }
        
        //use an timestamp md5 hash
        if ($type=='TIMESTAMPMD5') {
            $result=md5(time());
        }
        
        //use an next incremented number
        if ($type=='INCREMENT') {
            $busylogins=zb_AllBusyLogins();
            for ($i=1;$i<100000;$i++) {
                if (!isset($busylogins[$i])) {
                    $result=$i;
                    break;
                }
            }
        }
 
        //use five five digits increment with zero prefix
         if ($type=='INCREMENTFIVE') {
            $busylogins=zb_AllBusyLogins();
            $prefixSize=5;
            for ($i=1;$i<100000;$i++) {
                $nextIncrementFiveProposal=sprintf('%0'.$prefixSize.'d', $i);
                if (!isset($busylogins[$nextIncrementFiveProposal])) {
                    $result=$nextIncrementFiveProposal;
                    break;
                }
            }
            
        }
        
        //use five six digits increment with zero prefix
         if ($type=='INCREMENTSIX') {
            $busylogins=zb_AllBusyLogins();
            $prefixSize=6;
            for ($i=1;$i<1000000;$i++) {
                $nextIncrementFiveProposal=sprintf('%0'.$prefixSize.'d', $i);
                if (!isset($busylogins[$nextIncrementFiveProposal])) {
                    $result=$nextIncrementFiveProposal;
                    break;
                }
            }
            
        }
        
        //use five five digits increment
         if ($type=='INCREMENTFIVEREV') {
            $busylogins=zb_AllBusyLogins();
            $prefixSize=5;
            for ($i=1;$i<100000;$i++) {
                
               $nextIncrementFiveRevProposal=sprintf('%0'.$prefixSize.'d', $i);
               $nextIncrementFiveRevProposal=  strrev($nextIncrementFiveRevProposal);
                if (!isset($busylogins[$nextIncrementFiveRevProposal])) {
                    $result=$nextIncrementFiveRevProposal;
                    break;
                }
            }
            
        }
        
        //use an proposed IP last two octets
        if ($type=='IPBASED') {
             $ip_tmp=  str_replace('.', 'x', $ip_proposal);
             $result=$ip_tmp;
        }
        
        //use an proposed IP last two octets
        if ($type=='IPBASEDLAST') {
             $ip_tmp=  explode('.', $ip_proposal);
             if (($ip_tmp[2]<100)  AND ($ip_tmp[2]>=10)) { $ip_tmp[2]='0'.$ip_tmp[2]; }
             if (($ip_tmp[3]<100) AND ($ip_tmp[3]>=10)) { $ip_tmp[3]='0'.$ip_tmp[3]; }
             if ($ip_tmp[2]<10) { $ip_tmp[2]='00'.$ip_tmp[2]; }
             if ($ip_tmp[3]<10) { $ip_tmp[3]='00'.$ip_tmp[3]; }
             
             $result=$ip_tmp[2].$ip_tmp[3];
        }
        
        // just random string as login
        if ($type=='RANDOM') {
            $result=zb_rand_string(10);
        }
        
        //contrahent based model
        if ($type=='DEREBAN') {
            $busylogins=zb_AllBusyLogins();
            $agentPrefix=$agentid;
            $prefixSize=6;
              for ($i=1;$i<1000000;$i++) {
                $nextIncrementDerProposal=$agentPrefix.sprintf('%0'.$prefixSize.'d', $i);
                if (!isset($busylogins[$nextIncrementDerProposal])) {
                    $result=$nextIncrementDerProposal;
                    break;
                }
              }
        }
        
        
        /////  if wrong option - use DEFAULT
        if (empty($result)) {
             $result=$cityalias.$streetalias.$buildnum.'ap'.$apt.'_'.zb_rand_string();
             $result=  zb_RegLoginFilter($result);
        }
        
    } else {
        die(strtoupper('you have missed a essential option. before update read release notes motherfucker!'));
    }
    
    return ($result);
   
}


function zb_RegPasswordProposal() {
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    if ((isset($alterconf['PASSWORD_GENERATION_LENGHT'])) AND (isset($alterconf['PASSWORD_TYPE']))) {
            if ($alterconf['PASSWORD_TYPE']) {
                $password=zb_rand_string($alterconf['PASSWORD_GENERATION_LENGHT']);
            } else {
                $password=zb_rand_digits($alterconf['PASSWORD_GENERATION_LENGHT']);
            }
            
    } else {
       die(strtoupper('you have missed a essential option. before update read release notes motherfucker!'));
    }
    return ($password);
}

function zb_CheckLoginRscriptdCompat($login) {
    $maxRsLen=31;
    $maxStLen=42;
    $loginLen=strlen($login);
    if (($loginLen>=$maxRsLen)) {
        //rscriptd notice
        if ($loginLen<$maxStLen) {
        $alert=__('Attention generated login longer than').' '.$maxRsLen.' '.__('bytes').'. ('.$loginLen.') '.__('This can lead to the inability to manage this user on remote NAS running rscriptd').'. ';
        $alert.= __('Perhaps you need to shorten the alias, or use a different model for the generation of logins').'.';
        $result=  wf_modalOpened(__('Warning'), $alert, '500', '200');
        }
        //stargazer incompat notice
        if ($loginLen>=$maxStLen) {
            $alert=__('Attention generated login longer than').' '.$maxStLen.' '.__('bytes').'. ('.$loginLen.') '.__('And is not compatible with Stargazer').'. ';
            $alert.= __('Perhaps you need to shorten the alias, or use a different model for the generation of logins').'.';
            $result=  wf_modalOpened(__('Error'), $alert, '500', '200');
        }
    } else {
        $result='';
    }
    return ($result);
}

function web_UserRegFormNetData($newuser_data) {
$alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
$safe_mode=$alterconf['SAFE_REGMODE'];
$citydata=zb_AddressGetCityData($newuser_data['city']);
$cityalias=zb_TranslitString($citydata['cityalias']);
$streetdata=zb_AddressGetStreetData($newuser_data['street']);
$streetalias=zb_TranslitString($streetdata['streetalias']);
$buildata=zb_AddressGetBuildData($newuser_data['build']);
$buildnum=zb_TranslitString($buildata['buildnum']);
if (empty($newuser_data['apt'])) {
    $newuser_data['apt']=0;
}
$apt=zb_TranslitString($newuser_data['apt']);
//assign some agent from previously selected form
if (isset($alterconf['LOGIN_GENERATION'])) {
   if ($alterconf['LOGIN_GENERATION']=='DEREBAN') {
   $agentPrefixID=$newuser_data['contrahent'];  
   } else {
       $agentPrefixID=''; 
   }
} else {
   $agentPrefixID=''; 
}

$ip_proposal=multinet_get_next_freeip('nethosts', 'ip', multinet_get_service_networkid($newuser_data['service']));
$login_proposal=  zb_RegLoginProposal($cityalias, $streetalias, $buildnum, $apt, $ip_proposal,$agentPrefixID);
$password_proposal=  zb_RegPasswordProposal();


if (empty ($ip_proposal)) {
         $alert='
            <script type="text/javascript">
                alert("'.__('Error').': '.__('No free IP available in selected pool').'");
            </script>
            ';
        print($alert);
        rcms_redirect("?module=multinet");
        die();
}

//protect important options
if ($safe_mode) {
    $modifier='READONLY';
} else {
    $modifier='';
}

$form='
    <table width="100%" border="0">
    <form action="" method="POST">
    <tr class="row3">
    <td width="50%">
    <input type="text" name="login" value="'.$login_proposal.'" '.$modifier.'>
    </td>
    <td>
    '.__('Login').' '.zb_CheckLoginRscriptdCompat($login_proposal).' 
    </td>
    </tr>
    <tr class="row3">
    <td>
    <input tyle="text" name="password" value="'.  $password_proposal.'" '.$modifier.'>
    </td>
    <td>
    '.__('Password').'
    </td>
    </tr>
    <tr class="row3">
    <td>
    <input tyle="text" name="IP" value="'.$ip_proposal.'" '.$modifier.'>
    </td>
    <td>
    '.__('IP').'
    </td>
    </tr>
    </table>
    <input type="hidden" name="repostdata" value="'.base64_encode(serialize($newuser_data)).'">
    <input type="submit" value="'.__('Let register that user').'">

    </form>
    ';
return($form);
}


function zb_UserRegisterLog($login) {
$date=curdatetime();
$admin=whoami();
$login=vf($login);
$address=zb_AddressGetFulladdresslist();
$address=$address[$login];
$address=  mysql_real_escape_string($address);
$query="
    INSERT INTO `userreg` (
                `id` ,
                `date` ,
                `admin` ,
                `login` ,
                `address`
                )
                VALUES (
                NULL , '".$date."', '".$admin."', '".$login."', '".$address."'
                );
    ";
nr_query($query);
}

function zb_ip_unique($ip) {
    $ip=mysql_real_escape_string($ip);
    $query="SELECT `login` from `users` WHERE `ip`='".$ip."'";
    $usersbyip=simple_queryall($query);
    if (!empty ($usersbyip)) {
        return (false);
    } else {
        return (true);
    }
}


function zb_mac_unique($mac) {
    $ip=mysql_real_escape_string($mac);
    $query="SELECT `mac` from `nethosts` WHERE `mac`='".$mac."'";
    $usersbymac=simple_queryall($query);
    if (!empty ($usersbymac)) {
        return (false);
    } else {
        return (true);
    }
}

function zb_UserRegister($user_data,$goprofile=true) {
    global $billing;
    // Init all of needed user data
    $login=vf($user_data['login']);
    $login=  zb_RegLoginFilter($login);

    $password=vf($user_data['password']);
    $ip=$user_data['IP'];
    $cityid=$user_data['city'];
    $streetid=$user_data['street'];
    $buildid=$user_data['build'];
    @$entrance=$user_data['entrance'];
    @$floor=$user_data['floor'];
    $apt=$user_data['apt'];
    $serviceid=$user_data['service'];
    $netid=multinet_get_service_networkid($serviceid);
    $busylogins= zb_AllBusyLogins();
    //check login lenght
    $maxStLen=42;
    $loginLen=strlen($login);
    if($loginLen>$maxStLen) {
        log_register("HUGELOGIN REGISTER TRY (".$login.")");
        $alert=__('Attention generated login longer than').' '.$maxStLen.' '.__('bytes').'. ('.$login.' > '.$loginLen.') '.__('And is not compatible with Stargazer').'.';
        die($alert);
    }
    
    
    // empty login validation
    if (empty($login)) {
       $alert='
            <script type="text/javascript">
                alert("'.__('Error').': '.__('Empty login').'");
            </script>
            ';
        print($alert);
        rcms_redirect("?module=userreg");
        die();
    }
    
    //duplicate login validation
    if (isset($busylogins[$login])) {
        $alert='
            <script type="text/javascript">
                alert("'.__('Error').': '.__('Duplicate login').'");
            </script>
            ';
        print($alert);
        rcms_redirect("?module=userreg");
        die();
    }
    
    //last check
    if (!zb_ip_unique($ip)) {
          $alert='
            <script type="text/javascript">
                alert("'.__('Error').': '.__('This IP is already used by another user').'");
            </script>
            ';
        print($alert);
        rcms_redirect("?module=userreg");
        die();
    }
    
    // registration subroutine
    $billing->createuser($login);
    log_register("StgUser REGISTER (".$login.")");
    $billing->setpassword($login,$password);
    log_register("StgUser (".$login.") PASSWORD `".$password."`");
    $billing->setip($login,$ip);
    log_register("StgUser (".$login.") IP `".$ip."`");
    zb_AddressCreateApartment($buildid, $entrance, $floor, $apt);
    zb_AddressCreateAddress($login, zb_AddressGetLastid());
    multinet_add_host($netid, $ip);
    zb_UserCreateRealName($login, '');
    zb_UserCreatePhone($login, '', '');
    zb_UserCreateContract($login, '');
    zb_UserCreateEmail($login, '');
    zb_UserCreateSpeedOverride($login, 0);
    zb_UserRegisterLog($login);
    // if random mac needed
    $billingconf=rcms_parse_ini_file(CONFIG_PATH.'/billing.ini');
    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    if ($billingconf['REGRANDOM_MAC']) {
        // funny random mac, yeah? :)
        $mac='14:'.'88'.':'.rand(10,99).':'.rand(10,99).':'.rand(10,99).':'.rand(10,99);
        multinet_change_mac($ip, $mac);
        multinet_rebuild_all_handlers();
    }
    // if AlwaysOnline to new user needed
    if ($billingconf['REGALWONLINE']) {
        $alwaysonline=1;
        $billing->setao($login,$alwaysonline);
        log_register('CHANGE AlwaysOnline ('.$login.') ON '.$alwaysonline);
    }
    
    // if we want to disable detailed stats to new user by default
    if ($billingconf['REGDISABLEDSTAT']) {
        $dstat=1;
        $billing->setdstat($login,$dstat);
        log_register('CHANGE dstat ('.$login.') ON '.$dstat);
    }
    
    //set contract same as login for this user
    if (isset($alterconf['CONTRACT_SAME_AS_LOGIN'])) {
        if ($alterconf['CONTRACT_SAME_AS_LOGIN']) {
            $newUserContract=$login;
            zb_UserChangeContract($login, $newUserContract);
        }
    }
     
    ///////////////////////////////////
    if ($goprofile) {
    rcms_redirect("?module=userprofile&username=".$login);
    }
}

?>
