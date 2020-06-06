<?php
//define('rest_URL0', 'http://172.26.192.250/whcomm/michi/WebLink');	//tokyo restサーバ
//define('rest_URL0', 'http://localhost:81/whcomm/butsu/WebLink');	//michi restサーバ
define('OK_bgcolor', 'white');	//正常処理背景色
define('NG_bgcolor', 'yellow');	//異常処理背景色
define('OK_img', 'img/OK.gif');	//正常終了img
define('NG_img', 'img/NG.gif');	//異常正常終了img
define('now', date("Y-m-d H:i:s"));	//現在日時間
define('Today',date("Ymd"));	//当日YYYYMMDDの西暦
define('Lokehyoji', 1);	//ロケコードからエリアコード取得表示桁数
define('Lanehyoji', 2);	//ロケコードからレーン取得表示桁数

//ini_set('session.save_path', 'ディレクトリ(フルパス)' );
@ini_set('session.gc_maxlifetime', 86400 );  // 秒(デフォルト:1440)
@set_time_limit(300);	//タイムアウト５分
// キャシュ対策
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
/*
function OKNG_html() {
	print '<div id="OKimg">
	正常に処理おわりました<br>
	<img src="../OK.gif" width="60" height="60" alt="OK" >
	</div>
	<div id="NGimg">
	？？？？異常に処理おわりました<br>
	<img src="../NG.gif" width="300" height="300" alt="OK" >
	</div>';
}
*/
//------------------------------------------------------------------------------
//ログインチェック
//------------------------------------------------------------------------------
function login_check() {
	if(isset($_SESSION['ZN'])==false) {
		print '<br />****************************************************';
		print '<br />* WMS ログインされてません。×で閉じて下さい ';
		print '<br />****************************************************';
		print '</font>';
		//print '<a href="#" onClick="window.open('about:blank', '_self').close()">  [閉じる]</a>';
		//print '<input type="button" onclick="window.open('','_self').close();" value="[閉じる]">';
		exit();
	}
}
//------------------------------------------------------------------------------
//rest リクエスト処理　$buf リターン
//------------------------------------------------------------------------------
//$scr=0 親画面
function rest_cURL_fail($scr,$errMesg,$alert) {
echo <<<EOM
	<script type="text/javascript">
	scr = $scr;
	if (scr == 0) { 
		document.getElementById("errMesg").innerText = "$errMesg";
	} else {
		window.parent.document.getElementById("errMesg").innerText = "$errMesg";
	}
	alert("$alert");	//メッセージ確認下さい");

	</script>
EOM;
}
//------------------------------------------------------------------------------
//rest リクエスト
//------------------------------------------------------------------------------
//$scr=0 親画面　以外インフレーム
function rest_cURL($scr,$URL,$data) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_USERPWD, $USERNAME . ":" . $PASSWORD);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	//curl_setopt($ch, CURLOPT_HEADER, true);   // ヘッダーも出力する
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest"));

	//$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	$buf = curl_exec($ch);
	curl_close($ch);
	if ((substr($buf,0,19)) == 'Service Unavailable') {	//ライセンス不足の可能性 Service Unavailable
		rest_cURL_fail($scr,"?ライセンス不足　再度処理下さい","アクセスが拒否されました（Service Unavailable）");
		exit();
	}
		$return=json_decode($buf,true);		//配列に変換　ＯＫ
	if (isset($return["sts"])) {
		if ($return["sts"] == "OK") {
			$Mesg=$return["Mesg"];
echo <<<EOM
	<script type="text/javascript">
		document.getElementById("Mesg").innerHTML = '$Mesg';
	</script>
EOM;
			rest_OK($return);
			//HDTBL_grid($return["data"]);	//$Mesg);
		} else {
			rest_err_alert($scr,"Cache プログラム異常/サーバ稼働してない?","プログラムエラー=".$return["data"]);
		}
	} else {
		rest_cURL_fail($scr,"system err プログラム異常/サーバ稼働してない?","");
		//print "system err サーバ稼働してない?";
	}
	return $buf;
	}
//-----------------------------------------------------------------------------
// sftから流用 (YMDtoYOUBI)
//曜日算出 INは9(8)リターンは漢字曜日 michi 20200410
//------------------------------------------------------------------------------
function fnc_Ymd_week($yyyymmdd) {
	$days = array('日', '月', '火', '水', '木', '金', '土');
	$date = strtotime($yyyymmdd);
	$w = intval(date('w', $date));
	$youbi=$days[$w];
	return $youbi;
}
//------------------------------------------------------------------------------
// Sql prepare ? を組み立てる
// ●michi 20200322
// $data=array("123","bbb")
//  SELECT * FROM m_syohin WHERE a = ? and bbb = ?
//	print fnc_sql_prepare($sql,$data);		//debug sql print-------------------
//  SELECT * FROM m_syohin WHERE a = 123 and bbb = bbb
//------------------------------------------------------------------------------
function fnc_sql_prepare($sql,$data) {
	$array=explode("?",$sql);
	$chr="";	//$array[0];
	//$cnt=0;
	//print "<br/>func_sql_prepare sql arry="; print_r($array);	//debug
	//print "<br/>func_sql_prepare data arry="; print_r($data);	//debug
	foreach ($array as $key => $val) {
		if (isset($data[$key])) {
			$chr.=$val.$data[$key];
		}
	}	
	//print "func_sql_prepare sql=".$chr;	//debug
	
	return $chr;
}
//------------------------------------------------------------------------------
//*!50003 CREATE*/ /*!50020 DEFINER=`root`@`%`*/ /*!50003 FUNCTION `fnc_getLaneCd`(pSoukoCD CHAR(10), pLocaCD CHAR(10)) RETURNS char(20) CHARSET utf8
// ACTYは2桁なのでマスタ読まずに固定とする(11230) 1+1=2文字
// ●michi 20200324 
//------------------------------------------------------------------------------
function fnc_getLaneCd($souko_cd,$loca_cd) {
	$char=substr($souko_cd,Lanehyoji);
	return $char;
}

//------------------------------------------------------------------------------
//*!50003 CREATE*/ /*!50020 DEFINER=`root`@`%`*/ /*!50003 FUNCTION `fnc_getAreaCd`(pSoukoCD CHAR(10), pLocaCD CHAR(10)) RETURNS char(20) CHARSET utf8
// ACTYは１桁なのでマスタ読まずに固定とする(11230)
// ●michi 20200324 
//------------------------------------------------------------------------------
function fnc_getAreaCd($souko_cd,$loca_cd) {
	$char=substr($souko_cd,Lokehyoji);
	return $char;
}

//------------------------------------------------------------------------------
// 画面表示用にゼロを空白に
// ●michi 20200318 
//------------------------------------------------------------------------------
function fnc_zero_blank($su) {
	$su = intval($su);
	if ($su == 0) {
		$su='';
	}
	return $su;
}
//------------------------------------------------------------------------------
// YYYYMMDDto to YYYY/MM/DDに変換 html date対応
// ●michi 20200318 
//------------------------------------------------------------------------------
function fnc_date_display($symd) {
	$date_display='';
	if (strlen($symd) == 8) {
		$YYYY=substr($symd,0,4);
		$MM=substr($symd,4,2);
		$DD=substr($symd,6,2);
		$date_display=$YYYY.'/'.$MM.'/'.$DD;
	}
	//print '<br /> in='.$symd.'>'.$date_display;
	return $date_display;
}
//------------------------------------------------------------------------------
// 日数加算 fnc_date_add(起算日,加減算日数) 変換不可は0に　戻りは数値変換後
// ●michi 20200310 
//------------------------------------------------------------------------------
function fnc_date_calc($date,$add_dd) {
	$res=0;
	if ((strlen($date) == 8) and (is_numeric($date)) and (is_numeric($add_dd))) {
		$yyyy=mb_substr($date,0,4);
		$mm=mb_substr($date,4,2);
		$dd=mb_substr($date,6,2);
		$date1=mktime(0,0,0,$mm,$dd,$yyyy);
		$date2=$date1 + 60 * 60 * 24 * $add_dd;	//秒　分　時間で１日
		$res=intval(date("Ymd",$date2));	//数値に変換
	}
	//print "<br/> ?date=".$date." add=".$add_dd." ans=".$res."<br/>";
	return $res;
}
//------------------------------------------------------------------------------
// ● michi 20200306 総バラからケースとバラに
//------------------------------------------------------------------------------
function fnc_case_bara_sep($bara_su,$irisu) {
	$su=array(0,0);
	if ($irisu == 0) {	//入数ゼロはケース数計算しない
		$su[1]=$bara_su;
	} else {
		if ($bara_su > 0) {
			if ($bara_su >= $irisu) {
				$su[0] = floor($bara_su / $irisu);
			} else {
				$su[1]=$bara_su % $irisu;
			}
		}
	}
	//print "<br/>bara=".$bara_su." irisu=".$irisu." > cs=".$su[0]." bara=".$su[1];	
	return $su;
}
//------------------------------------------------------------------------------
// ● 20200306 管理日 表示処理 99
//------------------------------------------------------------------------------
function fnc_syohin_kanri_date($syohin_kanri,$syomi_nisu,$lot_no) {
	$kanribi="";
	switch($syohin_kanri):
		case 3:	//ロットNO
			$kanribi=$lot_no;
			break;
		case 2:	//入庫日管理
			break;
		case 1:	//賞味期間
		 	$kanribi=fnc_date_calc($lot_no,$syomi_nisu);	//日数加算
			break;
	    default:
	        $kanribi="";
	        
		endswitch;
	//print "syohin_kanri=".$syohin_kanri." syomi_nisu =".$syomi_nisu."lot_no=".$lot_no." -->".$kanribi;	//debug
	return $kanribi;
}
//------------------------------------------------------------------------------
// ● 20200306 loca_cd 表示処理 1x00999 -> 1x-00-999
//------------------------------------------------------------------------------
function fnc_loca_cd_hyoji($loca_cd) {
	$loca_cd_display = mb_substr($loca_cd,0,2).'-'.
		mb_substr($loca_cd,2,2).'-'.mb_substr($loca_cd,4);
	return $loca_cd_display;
}
//------------------------------------------------------------------------------
// ● 20200206_OK WMS ヘッダー表示処理 wms_head から改名　20200306
// $passは gifへのpass
//------------------------------------------------------------------------------
function fnc_head_display($AP,$midashi,$act,$pass) {
	$pass =$pass.'img/';		//wms/img　配下に移動 michi 20200402
	print '<input type="text" hidden name="syacd" value="'.$_SESSION['syacd'].'">';
	print '<table width="100%" cellspacing="0" >';
	print '<td><input type="button" value="画面コピー" onclick="window.print();" />';	//add michi 20204005
	print '<img src="'.$pass.'saver.gif" width="26" height="26" alt="saver">';
		print '<font size="-2">['.gethostname().']->DB:'.$_SESSION['db_host'];
	//	print '<img src="../kaisya.gif" width="60" height="30" alt="kaisya.gif">';
		print '<img src="'.$pass.'kaisya.gif" width="60" height="30" alt="kaisya.gif">';
	//	print $ap.'['.$_SESSION['ZN'].']</font></td>';
		$ap_name = explode(".", $AP);
		print $ap_name[0].'['.$pass.']</font></td>';
		if ($_SESSION['background_color'] != "white") {	//テストサイト
			print '<td><img src="'.$pass.'test_run.gif" width="60" height="30" alt=""></td>';
		}
		print '<td><div id="info" style="visibility:hidden;"><img src="'.$pass.'712-48.gif" width="32" height="33" alt=""></div></td>';	//アニメGIF
		print '<td align="center"><font size="+1" color="blue">'.'['.$_SESSION['db_name'].']　</font></td>';	//MysqlDB
		print '<td align="center"><font size="+2" color="blue">'.$midashi.'　　</font></td>';
		print '<td align="right">  '.$_SESSION['wms_syain_nm'].' ';
	//	print '<a href="../butsu_login.php" > ログイン</a> '; 
		print '<a href="'.$pass.'wms_login.php" > ログイン</a> '; 
		print '<a href="#" onclick="window.close()">閉じる</a></td>'; 
	print '</table>';
}
//******************************************************************************
//		商品マスタ
//******************************************************************************
// 商品コード指定でマスタ読む　結果のレコード返す　RESTで使用
// 20200319 michi
// rest_xxxx.php から呼ばれる
function m_syohin_search($syohin_cd) {
try {
	$db88 = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);

	$sql='SELECT * FROM m_syohin WHERE syohin_cd = ? ';
	$stmt=$db88->prepare($sql);
	$data=array($syohin_cd);
	$stmt->execute($data);
	$rec=$stmt->fetch(PDO::FETCH_ASSOC);	//$db->query('SET NAMES utf8');
	//print "m_syohin_search=".$syohin_cd ; print_r($rec);
	$db88=null;
	return $rec;
}	
	catch (Exception $e) {
		PHP_DBalert($e);
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		exit();
	}
}
//******************************************************************************
//		出庫指示パターン
//******************************************************************************
// ●michi 20200413 パラメータ(出庫指示区分,出庫指示区分,para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_syukosj_ptn_read(ケースバラ区分,パターンNO,$para);
function m_syukosj_ptn_read($case_bara_kbn,$patten_no,$para) {
	//unset($_SESSION['m_tantohsya']);
	if(!isset($_SESSION['m_syukosj_ptn'][Today])) {	//存在しなければ配列作成
		m_syukosj_ptn_array();
	}
	$code = $_SESSION['m_syukosj_ptn'];

	if(isset($code[$case_bara_kbn][$patten_no])) {
		$char=$code[$case_bara_kbn][$patten_no];
	} else {
		$char="?????";
	}
	if ($para[0] == "MIX") {
		$char=$patten_no.":".$char; 
	} 
	return $char;
}
// パラメータ(tantohsya_cd,$para')
// $para para[0]~[5]　必須
// ex <?php echo m_vendir_select(tantohsya_cd,$para);
// ex <?php echo m_syukosj_ptn_select(ケースバラ区分,$para);

function m_syukosj_ptn_select($case_bara_kbn,$para) {
	//unset($_SESSION['m_tantohsya']);
	if(!isset($_SESSION['m_syukosj_ptn'][Today])) {	//存在しなければ配列作成
		m_tantohsya_array();
	}
	$code = $_SESSION['m_syukosj_ptn'];
	$html_val ='<select name="m_syukosj_ptn">';
	if ($para[0] == 'insert') {	//追加オプション
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	if(isset($code[$tantohsya_cd])) {
		$html_val.=	'<option value="sagyogroup_cd" select >'.$code[$tantohsya_cd].'</option>';
	} 
	if ($tantohsya_cd == null) {$tantohsya_cd = "9999999999999";}	// debug---------------------
	foreach ($code as $key => $val) {
		if (($para[0] == 'delete') and ($key != $para[1])) {	//削除オプション
		} else {	
			if ($key != $tantohsya_cd) {
				$html_val.=	'<option value="'.$key.'" >'.$key.":".$val.'</option>';
			}
		}
	}
	$html_val.='</select>';
	return $html_val;
}
// 連想配列[tantohsyai_cd] = 担当者名
function m_syukosj_ptn_array() {
try {
	$db88 = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);

	$sql='SELECT case_bara_kbn,patten_no,pattern_nm FROM m_syukosj_ptn WHERE 1 ';
	$stmt=$db88->prepare($sql);
	$stmt->execute();
	$code = array();
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		$code[$rec['case_bara_kbn']][$rec['case_bara_kbn']]=$rec['patten_nm'];
	}
	$db88=null;
	$_SESSION['m_syukosj_ptn']=$code;
	$_SESSION['m_syukosj_ptn'][Today]='';	//存在有無と日替わり時更新 20200402add michi

}	
	catch (Exception $e) {
		PHP_DBalert($e);
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		exit();
	}
}

//******************************************************************************
//		担当者マスタ
//******************************************************************************
// ●michi 20200309
// パラメータ(tantohsya_cd,$para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_tantohsya_read(担当者,$para);
function m_tantohsya_read($tantohsya_cd,$para) {
	//unset($_SESSION['m_tantohsya']);
	if(!isset($_SESSION['m_tantohsya'][Today])) {	//存在しなければ配列作成
		m_tantohsya_array();
	}
	$code = $_SESSION['m_tantohsya'];

	if(isset($code[$tantohsya_cd])) {
		$char=$code[$tantohsya_cd];
	} else {
		$char="?????";
	}
	if ($para[0] == "MIX") {
		$char=$tantohsya_cd.":".$char; 
	} 
	return $char;
}
// パラメータ(tantohsya_cd,$para')
// $para para[0]~[5]　必須
// ex <?php echo m_vendir_select(tantohsya_cd,$para);

function m_tantohsya_select($tantohsya_cd,$para) {
	//unset($_SESSION['m_tantohsya']);
	if(!isset($_SESSION['m_tantohsya'][Today])) {	//存在しなければ配列作成
		m_tantohsya_array();
	}
	$code = $_SESSION['m_tantohsya'];
	$html_val ='<select name="tantohsya_cd">';
	if ($para[0] == 'insert') {	//追加オプション
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	if(isset($code[$tantohsya_cd])) {
		$html_val.=	'<option value="sagyogroup_cd" select >'.$code[$tantohsya_cd].'</option>';
	} 
	if ($tantohsya_cd == null) {$tantohsya_cd = "9999999999999";}	// debug---------------------
	foreach ($code as $key => $val) {
		if (($para[0] == 'delete') and ($key != $para[1])) {	//削除オプション
		} else {	
			if ($key != $tantohsya_cd) {
				$html_val.=	'<option value="'.$key.'" >'.$key.":".$val.'</option>';
			}
		}
	}
	$html_val.='</select>';
	return $html_val;
}
// 連想配列[tantohsyai_cd] = 担当者名
function m_tantohsya_array() {
try {
	$db88 = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);

	$sql='SELECT tantohsya_cd,tantohsya_nm FROM m_tantohsya WHERE ? ';
	$stmt=$db88->prepare($sql);
	$data=array();
	$data[]=1;	//$_GET["wh"];
	$stmt->execute($data);
	$code = array();
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($code[$rec['tantohsya_cd']])) {
			$code[$rec['tantohsya_cd']]= $rec['tantohsya_nm'];
		}
	}
	$db88=null;
	$_SESSION['m_tantohsya']=$code;
	$_SESSION['m_tantohsya'][Today]='';	//存在有無と日替わり時更新 20200402add michi

}	
	catch (Exception $e) {
		PHP_DBalert($e);
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		exit();
	}
}

//******************************************************************************
//		ベンダーマスタ
//******************************************************************************
// ●michi 20200306
// パラメータ(vendor_cd,$para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_vendor_read(ベンダー,$para);
function m_vendor_read($vendor_cd,$para) {
	//unset($_SESSION['m_vendor']);
	if(!isset($_SESSION['m_vendor'][Today])) {	//存在しなければ配列作成
		m_vendor_array();
	}
	$code = $_SESSION['m_vendor'];

	if(isset($code[$vendor_cd])) {
		$char=$code[$vendor_cd];
	} else {
		$char="?????";
	}
	if ($para[0] == "MIX") {
		$char=$vendor_cd.":".$char; 
	} 
	return $char;
}
// パラメータ(vendor_cd,$para')
// $para para[0]~[5]　必須
// ex <?php echo m_vendir_select(vendor_cd,$para);

function m_vendor_select($vendor_cd,$para) {
	//unset($_SESSION['m_vendor']);
	if(!isset($_SESSION['m_vendor'][Today])) {	//存在しなければ配列作成
		m_vendor_array();
	}
	$code = $_SESSION['m_vendor'];
	//print_r($code);
	$html_val ='<select name="vendor_cd">';
	if ($para[0] == 'insert') {	//追加オプション
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	if(isset($code[$vendor_cd])) {
		$html_val.=	'<option value="sagyogroup_cd" select >'.$code[$vendor_cd].'</option>';
	} 
	if ($vendor_cd == null) {$vendor_cd = "9999999999999";}	// debug---------------------
	foreach ($code as $key => $val) {
		if ($key == Today) {continue;}
		if (($para[0] == 'delete') and ($key != $para[1])) {	//削除オプション
		} else {	
			if ($key != $vendor_cd) {
				$html_val.=	'<option value="'.$key.'" >'.$key.":".$val.'</option>';
			}
		}
	}
	$html_val.='</select>';
	return $html_val;
}
// 連想配列[vendori_cd] = ベンダー名
function m_vendor_array() {
try {
	$db88 = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);

	$sql='SELECT vendor_cd,vendor_nm FROM m_vendor WHERE ? ';
	$stmt=$db88->prepare($sql);
	$data=array();
	$data[]=1;	//$_GET["wh"];
	$stmt->execute($data);
	$code = array();
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($code[$rec['vendor_cd']])) {
			$code[$rec['vendor_cd']]= $rec['vendor_nm'];
		}
	}
	$db88=null;
	$_SESSION['m_vendor']=$code;
	$_SESSION['m_vendor'][Today]='';	//存在有無と日替わり時更新 20200402add michi

}	
	catch (Exception $e) {
		PHP_DBalert($e);
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		exit();
	}
}


//******************************************************************************
//		倉庫マスタ
//******************************************************************************
//	●michi 2020/02/05 倉庫選択用HTML設定
// $para para[0]=insert 追加 para[1] コード para[2]:表示
// 		para[0]=delete 削除 para[1] 削除コード
function m_souko_select($para) {
try {
	//$db = new PDO('mysql:dbname=acty_tokai;host=localhost',$_SESSION['db_user'],$_SESSION['db_password']);
	$db = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);
	//$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); 
	$data = array();
	$sql = 'SELECT souko_cd,souko_nm FROM m_souko WHERE ?';	
	$stmt = $db->prepare($sql);
	$data[]=1;
	$html_val ='<select name="souko_cd">';
	$in_cnt = 0;
	$stmt->execute($data);
	$code = array();
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($code[$rec['souko_cd']])) {
			$code[$rec['souko_cd']]= $rec['souko_nm'];
		}
		$in_cnt++;
	}		
     if (($in_cnt > 1) and ($para[0] == 'insert')) {
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	$in_cnt = 0;
	foreach ($code as $key => $val) {
		if ($in_cnt == 0) {
			$html_val.=	'<option value="'.$key.'" select >'.$val.'</option>';
		} else {
			$html_val.=	'<option value="'.$key.'" >'.$val.'</option>';
		}
		$in_cnt++;
	}
	$html_val.='</select>';
	$db=null;
	return $html_val;

}
	catch (Exception $e) {
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		PHP_DBalert($e);
		exit();
	}
}
//******************************************************************************
//		得意先権限マスタ
//******************************************************************************
// ●michi  20200303 得意先名称取得
// パラメータ(sagyogroup_cd,$para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_tokuisaki_read(得意先コード,届先コード,$para);
function m_tokuisaki_read($tokuisaki_cd,$todokesaki_cd,$para) {
	unset($_SESSION['m_tokuisaki']);
	if(!isset($_SESSION['m_tokuisaki'][Today])) {	//存在しなければ配列作成
		m_tokuisaki_array();
	}
	$code = $_SESSION['m_tokuisaki'];
	if(isset($code[$tokuisaki_cd][$todokesaki_cd])) {
		$char=$code[$tokuisaki_cd][$todokesaki_cd];
	} else {
		$char="?????";
	}
	if ($para[0] == "MIX") {
		$char=$todokesaki_cd.":".$char; 
	} 
	return $char;
}
// ●michi 20200301 得意先HTML設定  ????未テスト????????????????????????????
// パラメータ(sagyogroup_cd,$para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_sagyogroup_select(sagyogroup_cd,$para);

function m_tokuisaki_select($sagyogroup_cd,$para) {
	if(!isset($_SESSION['m_tokuisaki'][Today])) {	//存在しなければ配列作成
		m_sagyogroup_array();
	}
	$code = $_SESSION['m_tokuisaki'];
	$html_val ='<select name="tokuisaki_cd">';
	if ($para[0] == 'insert') {	//追加オプション
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	if(isset($code[$tokuisaki_cd][$tokuisaki_cd])) {
		$html_val.=	'<option value="sagyogroup_cd" select >'.$code[$sagyogroup_cd].'</option>';
	} 
	if ($sagyogroup_cd == null) {$sagyogroup_cd = "99999";}	// debug---------------------
	foreach ($code as $key => $val) {
		if (($para[0] == 'delete') and ($key != $para[1])) {	//削除オプション
		} else {	
			if ($key != $sagyogroup_cd) {
				$html_val.=	'<option value="'.$key.'" >'.$key.":".$val.'</option>';
			}
		}
	}
	$html_val.='</select>';
	return $html_val;
}
// ●michi 得意先配列作成 20200303 得意先配列作成
// 連想配列[tokuisaki_cd][todoke_cd] = 得意先名
function m_tokuisaki_array() {
try {
	$db88 = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);

	$sql='SELECT tokuisaki_cd,todokesaki_cd,tokuisaki_nm,tokuisaki_rk FROM m_tokuisaki WHERE ? ';
	$stmt=$db88->prepare($sql);
	$data=array();
	$data[]=1;	//$_GET["wh"];
	$stmt->execute($data);
	$code = array();
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($code[$rec['tokuisaki_cd']][$rec['todokesaki_cd']])) {
//			$code[$rec['tokuisaki_cd']][$rec['todokesaki_cd']]= $rec['tokuisaki_nm'];
			$code[$rec['tokuisaki_cd']][$rec['todokesaki_cd']]= $rec['tokuisaki_rk'];
		}
	}
	$db88=null;
	$_SESSION['m_tokuisaki']=$code;
	$_SESSION['m_tokuisaki'][Today]='';	//存在有無と日替わり時更新 20200402add michi

}	
	catch (Exception $e) {
		PHP_DBalert($e);
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		exit();
	}
}

//******************************************************************************
//		作業グループ権限マスタ
//******************************************************************************
// ●michi 20200301 作業グループコード名称取得
// パラメータ(sagyogroup_cd,$para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_sagyogroup_read(sagyogroup_cd,$para);
function m_sagyogroup_read($sagyogroup_cd,$para) {
	//unset($_SESSION['m_sagyogroup']);
	if(!isset($_SESSION['m_sagyogroup'][Today])) {	//存在しなければ配列作成
		m_sagyogroup_array();
	}
	$code = $_SESSION['m_sagyogroup'];

	if(isset($code[$sagyogroup_cd])) {
		$char=$code[$sagyogroup_cd];
	} else {
		$char="?????";
	}
	if ($para[0] == "MIX") {
		$char=$name_cd.":".$char; 
	} 
	return $char;
}
// ●作業グループコードHTML設定 20200301 michibata
// パラメータ(sagyogroup_cd,$para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_sagyogroup_select(sagyogroup_cd,$para);

function m_sagyogroup_select($sagyogroup_cd,$para) {
	//unset($_SESSION['m_sagyogroup']);
	if(!isset($_SESSION['m_sagyogroup'][Today])) {	//存在しなければ配列作成
		m_sagyogroup_array();
	}
	$code = $_SESSION['m_sagyogroup'];
	//print_r($code);
	$html_val ='<select name="sagyogroup_cd">';
	if ($para[0] == 'insert') {	//追加オプション
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	if(isset($code[$sagyogroup_cd])) {
		$html_val.=	'<option value="sagyogroup_cd" select >'.$code[$sagyogroup_cd].'</option>';
	} 
	if ($sagyogroup_cd == null) {$sagyogroup_cd = "99999";}	// debug---------------------
	foreach ($code as $key => $val) {
		if (($para[0] == 'delete') and ($key != $para[1])) {	//削除オプション
		} else {	
			if ($key != $sagyogroup_cd) {
				$html_val.=	'<option value="'.$key.':'.$val.'" >'.$key.":".$val.'</option>';
			}
		}
	}
	$html_val.='</select>';
	return $html_val;
}
// ●作業グループ権限配列作成 20200301 michibata
// 連想配列[sagyogroup_cd] = sagyogroup_nm
function m_sagyogroup_array() {
try {
	$db88 = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);

	$sql='SELECT sagyogroup_cd,sagyogroup_nm FROM m_sagyogroup WHERE ? ';
	$stmt=$db88->prepare($sql);
	$data=array();
	$data[]=1;	//$_GET["wh"];
	$stmt->execute($data);
	$code = array();
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($code[$rec['sagyogroup_cd']])) {
			$code[$rec['sagyogroup_cd']]= $rec['sagyogroup_nm'];
		}
	}
	$db88=null;
	$_SESSION['m_sagyogroup']=$code;
	$_SESSION['m_sagyogroup'][Today]='';	//存在有無と日替わり時更新 20200402add michi

}	
	catch (Exception $e) {
		PHP_DBalert($e);
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		exit();
	}
}
//******************************************************************************
//		コード名称マスタ
//******************************************************************************
// ●コード名称選択用HTML設定 20200229 michibata
// パラメータ(memo,name_cd,$para')
// $para para[0]~[5]　必須
// 		para[0]=MIX code:名称 で表示
// ex <?php echo m_codename_read("TMS基準オリコンフラグ",$in['tms_oricon_kjn_flg'],$para);

function m_codename_read($memo,$name_cd,$para) {
	//unset($_SESSION['m_codename']);
	if(!isset($_SESSION['m_codename'][Today])) {	//存在しなければ配列作成
		m_codename_array();
	}
	$code = $_SESSION['m_codename'];

	if(isset($code[$memo][$name_cd])) {
		$char=$code[$memo][$name_cd];
	} else {
		$char="?????";
	}
	if ($para[0] == "MIX") {
		$char=$name_cd.":".$char; 
	} 
	return $char;
}
// ●コード名称選択用HTML設定 20200215 michibata
// パラメータ(memo,select name,name_cd,$para')
// $para para[0]=insert 追加 para[1] コード para[2]:表示
// 		para[0]=delete 削除 para[1] 削除コード 
// ex <?php echo m_codename_select("TMS基準オリコンフラグ",html_name,$in['tms_oricon_kjn_flg'],$para);

function m_codename_select($memo,$html_name,$name_cd,$para) {
	//unset($_SESSION['m_codename']);
	if(!isset($_SESSION['m_codename'][Today])) {	//存在しなければ配列作成
		m_codename_array();
	}
	$code = $_SESSION['m_codename'];
	//print_r($_SESSION['m_sagyogroup']);
	//print $html_name."name_cd=".$name_cd; print_r($code);	//print_r($code);	//debug ------------------------------------
	$html_val ='<select name="'.$html_name.'">';
	if ($para[0] == 'insert') {	//追加オプション
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	if(isset($code[$memo][$name_cd])) {
		$html_val.=	'<option value="'.$name_cd.'" select >'.$code[$memo][$name_cd].'</option>';
	} 
	if ($name_cd == null) {$name_cd = "99999";}	// debug---------------------
	//if ($name_cd == null) {print "Null"; $name_cd = "*";}	// debug---------------------
	if (isset($code[$memo])) {
		foreach ($code[$memo] as $key => $val) {
			//print "key=".$key; print "para0".$para[0];	//--------------------------	
			if (($para[0] == 'delete') and ($key != $para[1])) {	//削除オプション
			//print "delete";	//-----------------------------------------------------
			} else {	
				//print "key=".$key."name_cd=".$name_cd;	//-----------------------------------------------------
				if ($key != $name_cd) {
				//print "not";	//-----------------------------------------------------
					$html_val.=	'<option value="'.$key.'" >'.$val.'</option>';
				//} else {
				//print "eqeq";	//-----------------------------------------------------
				}
			}
		}
	} else {
		$html_val.=	'<option value="" > </option>';	//Key 無かったらダミー作成
	}
	$html_val.='</select>';
	return $html_val;
}
function m_codename_select_test($memo,$html_name,$name_cd,$para) {
	//unset($_SESSION['m_codename']);
	if(!isset($_SESSION['m_codename']['OK'])) {	//存在しなければ配列作成
		m_codename_array();
	}
	$code = $_SESSION['m_codename'];
	print $html_name."name_cd=".$name_cd; print_r($para);	//print_r($code);	//debug ------------------------------------
	$html_val ='<select name="'.$html_name.'">';
	if ($para[0] == 'insert') {	//追加オプション
			$html_val.=	'<option value="'.$para[1].'" >'.$para[1].'</option>';
	}
	if(isset($code[$memo][$name_cd])) {
		$html_val.=	'<option value="'.$name_cd.'" select >'.$code[$memo][$name_cd].'</option>';
	} 
	foreach ($code[$memo] as $key => $val) {
		//print "key=".$key; print "para0".$para[0];	//--------------------------	
		if (($para[0] == 'delete') and ($key != $para[1])) {	//削除オプション
		print "delete";	//-----------------------------------------------------
		} else {	
			print "key=".$key."name_cd=".$name_cd;	//-----------------------------------------------------
			if ($key !=$name_cd) {
			print "not";	//-----------------------------------------------------
				$html_val.=	'<option value="'.$key.'" >'.$val.'</option>';
			} else {
			print "eqeq";	//-----------------------------------------------------
			}
		}
	}
	$html_val.='</select>';
	return $html_val;
}
// ●コード名称配列作成 20200215 michibata
// 連想配列[memo][name_cd] = cd_name
function m_codename_array() {
try {
	//print "m_code_name_array starr";
	$db88 = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password']);
	$data = array();
	$sql = 'SELECT name_cd,code_name,memo FROM m_codename WHERE ? ORDER BY name_cd ASC';	
	$data[]=1;
	$stmt = $db88->prepare($sql);
	$stmt ->execute($data);
	$code = array();
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($code[$rec['memo']][$rec['name_cd']])) {
			$code[$rec['memo']][$rec['name_cd']]= $rec['code_name'];
		}
	}
	$db88=null;
	$_SESSION['m_codename']=$code;		//配列格納
	$_SESSION['m_codename'][Today]='';	//存在有無と日替わり時更新 20200402add michi
	
	//print "m_code_name_array end";

}
	catch (Exception $e) {
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		PHP_DBalert($e);
		exit();
	}
}
// sprintf ゼロパディング
function sprintf_soko_cd($soko_cd) {
	return sprintf('%02d', $soko_cd);	//倉庫?　2バイト
}
// MTP_INFO から改名　20200306
//	● 20200212_OK マスタメンテ　共通情報
function fnc_mtp_info($in) {
	//print '<br />';
	print '<table cellspacing="0"  border="1" bgcolor="silver" style="margin-left:2em;">';
		print '<td>登録日時</td><td>修正日時</td><td>端末番号</td><td>プログラムID</td><td>作業者コード</td></tr>';
		print '<td><label id="entry_time">'.$in['entry_time'].'</label></td>';
		print '<td><label id=""renewal_time">'.$in['renewal_time'].'</label ></td>';
		print '<td><label id="terminal_id">'.$in['terminal_id'].'</label ></td>';
		print '<td><label id="program_id">'.$in['program_id'].'</label></td>';
		print '<td><label id="sagyosya_cd">'.$in['sagyosya_cd'].'</label></td>';
	print '</table>';
	print '<input type="hidden" name="save_updatetime" value="'.$in['renewal_time'].'">';
	print '<br /><input type="submit" value="マスタ更新">';
	print '<br />';
	print '<a href="#" onclick="window.close()">[処理キャンセル　閉じる]</a>';

}

// MTP_head_set から改名　20200306
//	● 20200215_OK マスタメンテ　共通情報セット
//	登録日時,修正日時,端末番号,プログラムID,作業者コード<
function fnc_mtp_head_set($entry_time) {
	global $data,$run_mode,$AP;
	//print_r($_POST);
	//$data[]=$_POST['entry_time'];
	if ($run_mode == "insert") {	//データ追加
		$data[]=date("Y-m-d H:i:s");
		$data[]=null;
	} else {
		$data[]=$entry_time;
		$data[]=date("Y-m-d H:i:s");
	}
	$data[]="pc";	//$_POST['terminal_id'];
	$ap_name = explode(".", $AP);
	$data[]=$ap_name[0];
	$data[]=$_SESSION['wms_syain_cd'];	//$;
}
// Download ファイル 処理
function PHP_download($file_name,$csv)	{	//michi 20200418 add
// ------------------- 出力ファイル処理 ----------------------------------------
	$dir_path=$_SESSION['wms_path'].'download/';
	if(!file_exists($dir_path)) {
	    //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
	   if(mkdir($dir_path,077,true)) {
//	   		print $dir_path.'OK';
	   	} else {
	   		print $dir_path.'path NG';
		print $dir_path.'作成できない　障害により大変ご迷惑をお掛けしております。';
	   		exit();
	   	}
	}
	//$file = .date("Ymd hhmmdd").$csv_file';	
	$file=$dir_path.$file_name;	//YYYYMMDD_HHMMSS_CSVファイル 
	if(!file_exists($file)) {
		$out1_f   = fopen($file, "w");
		fputs($out1_f,$csv);
		fclose($out1_f);
//		print "out_file=".$file;	//debug
	}
	unset($csv);
}
//------------------------------------------------------------------------------
// wms で使用予定だが　未検証
//------------------------------------------------------------------------------

function PHP_DBalert($Mesg) {
		$Mesg.="で\nDB障害により大変ご迷惑をお掛けしております。";
		PHP_errlog($Mesg);
		PHP_alert($Mesg);
}
//●wms	PHP_alert("err------------- \n bbbbbbbbbbbbbbbb \n cccccccccccccc") \nで改行
function PHP_alert($Mesg) {
	print '<font size="5" color="red">';
	print '<br />**************************************************** <br />';
	$Eval=explode("\n", $Mesg);
	foreach($Eval as $Key => $val) {
		print ' '.$val.'<br />';	
	}
	print '**************************************************** <br />';
	print '</font>';
	PHP_errlog($Mesg);
	}
// php 稼働log 処理
function PHP_PRINTMesg($AP)	{ //rm 20160902 add
	//$Mesg="<br />".date("Ymd").'-'.date('H:i:s').' AP='.$AP.' ';
	$Mesg="<br />".date("Y/m/d H:i:s").' AP='.$AP.' ';
	return $Mesg;
}
// php 稼働log 処理
function PHP_log($AP)	{ //rm 20160902 add
// ------------------- 出力ファイル処理 ----------------------------------------
	$dir_path=$_SESSION['wms_path'].'log/';
	if(!file_exists($dir_path)) {
	    //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
	   if(mkdir($dir_path,077,true)) {
	   		//print $dir_path.'OK';
	   	} else {
	   		print $dir_path.'path NG';
			print $dir_path.'作成できない　障害により大変ご迷惑をお掛けしております。';
	   		exit();
	   	}
	}
	$file = $dir_path.date("Ymd").'_php_sft.log';	//？月度店舗作業ファイル
	if(!file_exists($file)) {
		$out1_f   = fopen($file, "a");
		$Hcsv='稼働日,時間,host,業務,Login,Lognm,入力パラメータ'."\r\n";
		fputs($out1_f,$Hcsv);
		fclose($out1_f);
	}
	// 入力パラメータ取得
	$para='';
	if(isset($_POST) == true) {
		foreach ($_POST as $key => $val) {
			if ($para == '' ) {
				$para.=$key.'='.$val;
			} else {
				@$para.='&'.$key.'='.$val;
			}
		}
	
	}
	if(isset($_GET) == true) {
		foreach ($_GET as $key => $val) {
			if ($para == '' ) {
				$para.=$key.'='.$val;
			} else {
				@$para.='&'.$key.'='.$val;
			}
		}
	
	}
	$out1_f   = fopen($file, "a");
	$data=date("Ymd").','.date('H:i:s').','.gethostname().','.$AP.','.$_SESSION['wms_syain_cd'].','.$_SESSION['wms_syain_nm'].','.$para."\r\n";
	//$data=date("Ymd").','.date('H:i:s').','.gethostname().','.$AP.','.$_SESSION['wms_syain_cd'].','.$_SESSION['$_SESSION['wms_syain_nm']'].','.$para."\r\n";
	fwrite($out1_f,$data);
	fclose($out1_f);


}

// エラーlog 処理
function PHP_errlog($Mesg)	{	//rm 20160902 add
// ------------------- 出力ファイル処理 ----------------------------------------
	$dir_path=$_SESSION['wms_path'].'log/';
	if(!file_exists($dir_path)) {
	    //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
	   if(mkdir($dir_path,077,true)) {
	   		//print $dir_path.'OK';
	   	} else {
	   		print $dir_path.'path NG';
		print $dir_path.'作成できない　障害により大変ご迷惑をお掛けしております。';
	   		exit();
	   	}
	}
	$file = $dir_path.date("Ymd").'_php_err.log';	//？月度店舗作業ファイル
	if(!file_exists($file)) {
		$out1_f   = fopen($file, "a");
		$Hcsv='稼働日,時間,host,業務,Login,Lognm,入力パラメータ'."\r\n";
		fputs($out1_f,$Hcsv);
		fclose($out1_f);
	}
	$out1_f   = fopen($file, "a");
	$para='';
	$AP=date("Ymd").','.date('H:i:s').','.gethostname().','.$Mesg.','.$_SESSION['wms_syain_cd'].','.$_SESSION['wms_syain_nm'].','.$para."\r\n";
	fputs($out1_f,$AP);
	//if ($csv != '') {
	//	foreach ($csv as $key => $val) {
	//		fputcsv($inp1_f,$val);
	//	}
	//}
	fclose($out1_f);


}

// ロック??????
function LOCK($db,$LOCK) {
	$sql='LOCK TABLES '.$LOCK.'WRITE' ;
	$stmt = $db->prepare($sql);
	$dbsts=$stmt ->execute();
	//print $sql.' dbsts='.$dbsts;
}
// ロック解除 ????
function UNLOCK($db) {
	$sql='UNLOCK TABLES';
	$stmt = $db->prepare($sql);
	$dbsts=$stmt ->execute();
	//print $sql.' dbsts='.$dbsts;
}
//------------------------------------------------------------------------------
// tmsより共通分流用 ほぼ使わない　？？？？？？？
//------------------------------------------------------------------------------

// YYYYMMDDto to YYYY-MM-DDに変換 html date対応
function YYYYMMDDtoYYYY_MM_DD($symd) {
	$yyyy_mm_dd='';
	if (strlen($symd) == 8) {
		$YYYY=substr($symd,0,4);
		$MM=substr($symd,4,2);
		$DD=substr($symd,6,2);
		$yyyy_mm_dd=$YYYY.'-'.$MM.'-'.$DD;
	}
	//print '<br /> in='.$symd.'>'.$yyyymmdd;
	return $yyyy_mm_dd;
}
// YYYY_MM_DDto to YYYYMMDDに変換 html date対応
function YYYY_MM_DDtoYYYYMMDD($symd) {
	$yyyymmdd=0;
	if (strlen($symd) == 10) {
		$YYYY=substr($symd,0,4);
		$MM=substr($symd,4,2);
		$DD=substr($symd,6,2);
		$yyyymmdd=$YYYY.$MM.$DD;
	}
	//print '<br /> in='.$symd.'>'.$yyyymmdd;
	return $yyyymmdd;
}
// YYYYMMDDto to YYYY-MM-DDに変換 html date対応
function YYYYMMtoYYYY_MM($sym) {
	$yyyy_mm='';
	if (strlen($sym) == 6) {
		$YYYY=substr($sym,0,4);
		$MM=substr($sym,4,2);
		$yyyy_mm=$YYYY.'-'.$MM;
	}
	//print '<br /> in='.$symd.'>'.$yyyymmdd;
	return $yyyy_mm;
}
// YYYY-MM-DD YYYYMMDDに変換 html date対応
function YYYY_MMtoYYYYMM($sym) {
	$yyyymm=0;
	if (strlen($sym) == 7) {
		$YYYY=substr($sym,0,4);
		$MM=substr($sym,4,2);
		$yyyymm=$YYYY.$MM;
	}
	//print '<br /> in='.$symd.'>'.$yyyymmdd;
	return $yyyymm;
}
// HHMM to HH:MMに変換 html 対応
function HHMMtoHH_MM($str) {
	$hh_mm='';
	if (strlen($str) == 4) {
		$HH=substr($str,0,2);
		$MM=substr($str,2,2);
		$hh_mm=$HH.':'.$MM;
	}
	//print '<br /> in='.$symd.'>'.$yyyymmdd;
	return $hh_mm;
}
// HH:MM to HHMMに変換 html 対応
function HH_MMtoHHMM($str) {
	$hhmm=0;
	if (strlen($str) == 5) {
		$HH=substr($str,0,2);
		$MM=substr($str,3,2);
		$hhmm=$HH.$MM;
	}
	//print '********str='.$str.'>'.$hhmm;
	return $hhmm;
}
//	コード名所配列取得　 m_codenamme READm_codename($db,'地区コード')
function READm_codename($db,$str) {
	//print 'str='.$str;
	$CODEtbl=array();
	$data = array();
	$sql = 'SELECT * FROM m_codename WHERE name_kbn=? ORDER BY name_cd ASC';	
	$data[]=$str;
	$stmt = $db->prepare($sql);
	$stmt ->execute($data);
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		$CODEtbl[$rec['name_cd']]=$rec['code_name'];
	}
	//print_r($CODEtbl);
	return $CODEtbl;
}
//	ラジオボタン生成 RADIO_MAKE($db,'地区コード','chiku_cd',$in['chiku_cd'])
function RADIO_MAKE($db,$str,$label,$data)  {
	$CODEtbl=READm_codename($db,$str);
	//print $data;
	foreach($CODEtbl as $key => $val) {
		print '<input type="radio" required name="'.$label.'" value="'.$key.'"';
		if ($data==$key) {print 'checked="checked"';}
		print '>'.$val;
	}
}
//	SELECT 生成 SELECT_MAKE($db,'地区コード','chiku_cd',$in['chiku_cd'])
function SELECT_MAKE($db,$str,$label,$data)  {
	$CODEtbl=READm_codename($db,$str);
	//print $data;
	print '<select id="'.$label.'" name="'.$label.'">';
	foreach($CODEtbl as $key => $val) {
		print '<option  value="'.$key.'"';
		if ($data==$key) {print 'delected';}
		print '>'.$val.'</option>';
	}
	print '</select>';
}
//	届け先名取得($db,得意先,届け先) 2017/10/06
function TODOKESAKI_GET($db,$tokuisaki_cd,$todokesaki_cd) {
	//print 'str='.$str;
	$data = array();
	$sql = 'SELECT * FROM m_tokuisaki WHERE tokuisaki_cd=? and todokesaki_cd=?';	
	$data[]=$tokuisaki_cd;
	$data[]=$todokesaki_cd;
	$stmt = $db->prepare($sql);
	$stmt ->execute($data);
	$rec=$stmt->fetch(PDO::FETCH_ASSOC);
	return $rec;
}
//	SEQ番号取得($db,'キー') 2017/10/05

function SEQ_NO_GET($db,$str) {
	//print 'str='.$str;
	$seq_no='';
	$data = array();
	$sql = 'SELECT * FROM m_seqno WHERE key_id=?';	
	$data[]=$str;
	$stmt = $db->prepare($sql);
	$stmt ->execute($data);
	$rec=$stmt->fetch(PDO::FETCH_ASSOC);
	if ($rec==false) {
	//		break;
	} else {
		if ($rec['seq'] == $rec['max']) {
			$seq_no = $rec['mini'];
		} else {
			$seq_no = $rec['seq'] + 1;
		}		
	// 	番号１加算更新
		$sql = 'UPDATE m_seqno SET renewal_time=?,seq=? WHERE id=?';
		$stmt = $db->prepare($sql);
		$data=array();
		$data[]=date('Y-m-d H:i:s');
		$data[]=$seq_no;
		$data[]=$rec['id'];
		$dbsts=$stmt ->execute($data);
	}
	//print_r($CODEtbl);
	return $seq_no;
}

// 売上区分名　取得
function urikbn_name($cd) {
	Global $GBLtbl;
	$str='';
	if (isset($GBLtbl['売上区分'][$cd])) {
		$str=$GBLtbl['売上区分'][$cd];
	}
	return $str;
}
// 売上分類名　取得
function uribunrui_name($cd) {
	Global $GBLtbl;
	$str='';
	if (isset($GBLtbl['売上分類'][$cd])) {
		$str=$GBLtbl['売上分類'][$cd];
	}
	return $str;
}

// デレクトリー削除　20161010追加　ネットから
function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
} 	

// 平成99年99月99日 to YYYYMMDDに変換 給与奉行から
function WAREKItoYMD($symd) {
	$yyyymmdd=0;
	if ($symd != '') {
		$reki=substr($symd,0,4);
		$wval=substr($symd,4);
		$wval=str_replace ("年","/",$wval);
		$wval=str_replace ("月","/",$wval);
		$wval=str_replace ("日","/",$wval);
		@list($Y,$M,$D)=explode("/", $wval);
		$Y=intval($Y);
		$M=intval($M);
		$D=intval($D);
		if ($reki == '平成') {
			$sy=1988+$Y;
		} else {
			$sy=1925+$Y;
		}
		$yyyymmdd=($sy*10000)+($M*100)+$D;
	}
	//print '<br /> in='.$symd.'>'.$yyyymmdd;
	return $yyyymmdd;
}

// YYYY/MM/DD to YYYYMMDDに変換
function sYMDtoYMD($mdy) {
	@list($Y,$M,$D)=explode("/", $mdy);
	$yyyymmdd=($Y*10000)+($M*100)+$D;
	return $yyyymmdd;
}
// MM/DD/YYYY to YYYYMMDDに変換
function MDYtoYMD($mdy) {
	@list($M,$D,$Y)=explode("/", $mdy);
	$yyyymmdd=($Y*10000)+($M*100)+$D;
	return $yyyymmdd;
}
//曜日算出 INは9(8)リターンは漢字曜日 20160921追加
function YMDtoYOUBI($yyyymmdd) {
	$days = array('日', '月', '火', '水', '木', '金', '土');
	$date = strtotime($yyyymmdd);
	$w = intval(date('w', $date));
	$youbi=$days[$w];
	return $youbi;
}
//　処理年月,締日で　開始日と終了日返す (給与データなど
//999999,999999,99,999999:曜日/999999:曜日/999999:曜日/...999999:曜日,５日の配列番号
//開始日,終了日,日数,日日:曜日(日~土)....,５日の番号
//	$ftdd=YMD_FT(201608,20);
//	list($date,$dateEND,$Dcnt,$Dtbl,$D5)=explode(",",$ftdd);

function YMD_FT($ym,$simedd) {
	//print $ym;	//$ym = '201608';
	//print $simedd;	//$simedd='31';
	if ($simedd == 31) {
		$yyyymm=substr($ym,0,4).'-'.substr($ym,4,2);
		$date = strtotime('first day of ' . $yyyymm);
		$date = strtotime('-1 day', $date);
		$dateEND=strtotime('last day of ' . $yyyymm);
	} else {
		$date = strtotime($ym . $simedd);
	//	$date = strtotime('+1 day', $date);
		$date = strtotime('-1 month',$date);
		$dateEND = strtotime($ym . $simedd);
	}
	$days = array('日', '月', '火', '水', '木', '金', '土');
	$Sday=date('Ymd', strtotime('+1 day', $date));
	$Eday=date('Ymd', $dateEND);
	$Dtbl='';
	$Dcnt=0;
	do {
		$date = strtotime('+1 day', $date);
		$w = intval(date('w', $date));
		//print date('Ymd', $date) . '(' . $days[$w] .$w. ')<br />';
		$tday=date('Ymd', $date);
		$wD5=date('d', $date);
		if ($wD5 == 5) {$D5=$Dcnt;}	//５日が何番目かセット
		if ($Dtbl != '') {
			$Dtbl=$Dtbl.'/'.$tday.':'.$days[$w];
		} else {
			$Dtbl=$Dtbl.$tday.':'.$days[$w];
		}
		$Dcnt++;
//		$date = strtotime('+1 day', $date);
	} while ($date !== $dateEND);
	return $Sday.','.$Eday.','.$Dcnt.','.$Dtbl.','.$D5;
	//999999,999999,99,999999:曜日/999999:曜日/999999:曜日/...999999:曜日,５日の配列番号
	}
// ハッピーマンデー と　祝日もとめる　該当日なら　'祝' 返す + 振休
function HAPPY_MDAYplus($yyyymmdd) {
	$Aday = strtotime($yyyymmdd);	//前日処理
	$Aday = strtotime('-1 day', $Aday);
	$Aw = intval(date('w', $Aday));
	$Asyuku=HAPPY_MDAY(date('Ymd',$Aday));
	//print date('Ymd',$Aday).$Aw.$Asyuku.'???';
	$HP=HAPPY_MDAY($yyyymmdd);
	if ($HP == '') {	//平日
		if (($Aw == 0) and ($Asyuku == '祝')) {$HP='休';}	//振休
	}
	return $HP;
}
// ハッピーマンデー と　祝日もとめる　該当日なら　'祝' 返す
function HAPPY_MDAY($yyyymmdd) {
	$SD='';
	$arr = array('0101','0211','0321','0429','0503','0504','0505','0811','0922','0923','1103','1123','1223');
	foreach ($arr as $key => $val) {$SD[$val]='';}
	$mm=substr($yyyymmdd,4,2);
	$q_md=substr($yyyymmdd,4,4);
	$week=0;
	if (($mm == 1) or ($mm ==10)) {$week = 2;}
	if (($mm == 7) or ($mm == 9)) {$week = 3;}
	if ($week != 0 ) {	//ハッピーマンデー 月
		$ym = substr($yyyymmdd,0,6);
		$yyyymm=substr($ym,0,4).'-'.substr($ym,4,2);
		$date = strtotime('first day of ' . $yyyymm);
		$dateEND=strtotime('last day of ' . $yyyymm);
		$m_cnt=0;	//月曜カウンター
		//print $yyyymm.'>'.date('Ymd',$date).'~'.date('Ymd',$dateEND);	//debug
		do {
			$w = intval(date('w', $date));
			//print date('Ymd',$date).'w='.$w.'<br />';	//debug
			if ($w == 1) {$m_cnt++;}	//月カウンタ加算
			if ($week == $m_cnt) {	//該当日
				$md=date('md', $date);
				$SD[$md]='';
				break;
			}
			$date = strtotime('+1 day', $date);
		} while ($date !== $dateEND);
	}
	//print_r($SD);
	//print '<br />'.$yyyymmdd.'-'.'md='.$q_md.'<br/>';
	if (array_key_exists($q_md,$SD)) {
		return '祝';
	} else {
		return '';
	}
}

//　勤務時間算出　99:99-99:99,99:99-99:99,99:99-99:99(開始時分-終了時分,休憩開始時分-休憩終了時分,休憩開始時分2-休憩終了時分2
function TIME_DAY($time) {
	@list($kinmuFT,$kyukeiFT,$kyukeiFT2)=explode(",",$time);
	$kinmuMM=TIME_FT($kinmuFT);
	$kyukeiMM=TIME_FT($kyukeiFT);
	$kyukeiMM2=TIME_FT($kyukeiFT2);
	$AtimeMM=$kinmuMM-$kyukeiMM-$kyukeiMM2;
	//print '--------------TIME_FT='.$time.'>'.$AtimeMM.'<br />';
	return $AtimeMM;
	}
// 99:99-99:99(開始時分-終了時分の分数返す）
function TIME_FT($timexx) {
	@list($Stime,$Etime)=explode("-",$timexx);
	$StimeMM=TIME_MM($Stime);
	$EtimeMM=TIME_MM($Etime);
	if ($Etime == 0) {
		$AtimeMM=0;
	} else {
		$AtimeMM=$EtimeMM-$StimeMM;
	}
	//print '--TIME_FT='.$timexx.'>'.$AtimeMM.'<br />';
	return $AtimeMM;
	}
//　時間を分数換算
function TIME_MM($timeMM) {
	//error_reporting(E_ALL ^ E_NOTICE);
	//$a=substr_count($timeMM,':');
	//print 'count='.$a.'<br />';
	@list($thh,$tmm)=explode(":",$timeMM);
	$tmm=intval($tmm);
	//if (isset($timeXX[1])) {$tmm=$timeXX[1];}
	$tmm_total=($thh*60)+$tmm;
	//error_reporting(E_ALL);
	//print 'TIME_MM='.$timeMM.'>'.$tmm_total.'<br />';
	return $tmm_total;
	}
//　分数を時間(99:99形式に)
function MMtoHM($qMM) {
	$h=floor($qMM/60);
	$m=$qMM%60;
	$AtimeHM=$h.':'.$m;
	return $AtimeHM;
	}
// 店舗作業コードの配列作成　$Work[]=作業ＮＯ,作業名,略記号 20160811追加
// 引数なし　global配列作成
function Workdata() {
	$Work='';
	global $Work;
	$file = '../SFTjoblist.txt';
	$inp1_f   = fopen($file, "r");
	$gyo_cnt=0;
	if ($inp1_f) { //file open 正常なら
		while (!feof($inp1_f)) {
	  		$csvdata = fgetcsv($inp1_f);
		    if (($gyo_cnt == 0) or ($csvdata == '')) {
		        // タイトル行 ダミー
		        $gyo_cnt++;
		        continue;
		    }
			//@list($jobno,$jobmei,$joba)=explode(",", $data);
			$Work[$csvdata[0]]=$csvdata[0].','.$csvdata[1].','.$csvdata[2]; 
		}
	} else {
		print '<br />'.$file.' が見つかりません？？？？？ <br />';
	}
	if ($gyo_cnt != 0) {
		fclose($inp1_f);
	//	print_R($Work);
	}
}
?>