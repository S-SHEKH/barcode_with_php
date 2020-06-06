<?php
header("Content-Type: text/html; charset=utf-8");
//include 'TANA_comm0.php';
//	LOG_CSV('TANA メニュー/'.basename(__FILE__));
	session_start();
	require_once('../wms_comm0.php');
	login_check();	//ログインチェック

?>

<!DOCTYPE html>
<html>
<head>
<title>ロケーションラベル印刷</title>
<link rel="shortcut icon" href="../favicon.ico">
<!script src="../../js/wms-comm-1.js"></script>
<script src="../../js/jquery-1.11.3.min.js"></script>
<style type="text/css">
<!--
.leftmargin {
	margin-left:30px;
}
-->
</style>
<script type="text/javascript">

function check() {
		errMesg[0] = ""; 
		//console.log('check vendor='+ document.myf.vendorcd.value);
		//console.log('check xdate='+ document.myf.xdate.value);
		var flag = 0;
		if ((document.myf.loca_cd.value == "") && (document.myf.syohin_cd.value == "")) { 
			errMesg[0] = "検索条件未入力です";
		}
//		document.getElementById("errMesg").innerText = errMesg_scr();
		if(document.getElementById("errMesg").innerText != ""){
			return false; // 送信を中止
		}
		else{
			document.getElementById("send").style.visibility = "hidden";
			document.getElementById("info").style.visibility = "visible";	//処理中Gif 表示		
			return true; // 送信を実行
		}
	}
function doc_clear() {
/*
	console.log('doc_clear');
	document.myf.vendorcd.value = "";
	//document.myf.wh.value = "";
	document.myf.xdate.value = "";
	document.myf.cyohyo.value = "1";
*/
}	

function openSubWindow() {
  var rtn = window.open('../Search/MTP040_Search.php' , 'window','width=1000,height=500');
}

</script>
<?php 
// ヘッダー処理 ----------------------------------------------------------------
	$AP="HTP172N.php";		//プログラム名
	$midashi = "ロケーションラベル印刷";
	fnc_head_display($AP,$midashi,0,"../");
?>
<body style="background-color:<?php echo $_SESSION['background_color'] ?>">
<div style="padding-ieft:30px">
<form class="form-horizontal" name="myf" method=post enctype="multipart/form-data" target="sample" action="HTP172N_display.php" onSubmit="return check()">
<font color="red"><div id="errMesg" style="border: 2px solid #000000;width:100%;height: 24px;"></div></font>
<input type="radio" name="action" value="0" >固定ラベル
<input type="radio" name="action" value="1" checked="checked">フリーラベル</br>
<table cellspacing="0" style="margin-left:2em;">
<tr><td><font color="blue">倉庫コード </font><td><?php echo $_SESSION['souko_cd0']; ?>
</td></tr>
<u><font color="blue"><td>ロケーションコード</td>
<td><input type="text" name="loca_cd" size=15 value="" >最小でもレーン３文字指定</td></tr>
<td>商品コード</td></font></u>
<td><input type="number" name="syohin_cd" value=""  style="font-size:large;width:200px">
<input type="button" value="検索" onclick="openSubWindow()"></td>
<td><input type="text" readonly name="syohin_nm" maxsize=20></td></tr>

</td>
</table>
<input type="submit" id="send" value="検索 " >
<input type="reset" value="クリア" onclick="doc_clear()">
 <iframe srcdoc="条件指定して　検索OKボタン押してください" src="#" name="sample" width="100%" height="560"></IFRAME> 
</form>
</div>
</body>

</html>
