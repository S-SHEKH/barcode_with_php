<?php
header("Content-Type: text/html; charset=utf-8");
//include 'TANA_comm0.php';
//	LOG_CSV('TANA メニュー/'.basename(__FILE__));
	session_start();
	require_once('../wms_comm0.php');
	require_once('../fnc/fnc_m_locakana_read.php');

?>
<!DOCTYPE html>
<html>
<head>
<title>ロケーションラベル印刷</title>
<!script src="../../js/wms-comm-1.js"></script>
<link rel="stylesheet" href="../css/handsontable.full.css">
<script src="../js/handsontable.full.js"></script>
<script src="../js/jquery-1.11.3.min.js"></script>

<script type="text/javascript">
		window.parent.document.getElementById("info").style.visibility = "hidden";	//ローデイングgif 非表示		
		window.parent.document.getElementById("send").style.visibility = "visible";	//検索button   f    表示		
function check() {
	if (document.myf.tablearea.value == "") {
		window.parent.document.getElementById("errMesg").innerText = "修正行ありません";	//errMesg;	//親ウインドウにエラー表示
		return false; // 送信を中止
	} else {
		window.parent.document.getElementById("errMesg").innerText = "";	//親ウインドウにエラー表示
		return true; // 送信
	}
}
</script>
</head>

<body style="background-color:<?php echo $_SESSION['background_color'] ?>">
<form  method="post" action="HTP172N_print.php" name="myf" onSubmit="return check()">
<div id="Mesg"></div>
<input type="checkbox" id="all" onclick="allCheck(this)" style="margin-left:60px;">全選択/解除(多少時間かかる) 
<div id="hst"></div>
<script type="text/javascript">
upcnt=0;
function hst_check(hstdata) {
	upcnt = 0;
	var rows = hstdata.countRows();
	var rowdata = "";
	var upcnt = 0;
	var errMesg = "";
//	var rowdata = hstdata.getData(); 2次元配列になる
	for (var i = 0; i < rows; i++) {
		Coldata = hstdata.getDataAtCell(i,0);
		//console.log('tabledata gyo='+ i + ' coldata=' + Coldata + "/");
		if(Coldata === true) {
			rowdata = rowdata + hstdata.getDataAtRow(i) + "\t";	//\t=tab
			upcnt++;
		}
	}
	document.myf.tablearea.value = rowdata;
	//console.log('??tablearea' + document.myf.tablearea.value);
}
// 全選択／全削除チェック処理
function allCheck(checkbox) {
//	console.log('allcheck exec');
    for (var i = 0; i < hstdata.countRows() ; i++) { 
        hstdata.setDataAtCell(i, 0, checkbox.checked) 
    }

//    var col = hstdata.propToCol(COL_SELECTConst.Select);
//    hstdata.populateFromArray(0, col, [[checkbox.checked]], hot.countRows() - 1, col, null, null, 'down');
}
function check() {
	if (document.myf.tablearea.value == "") {
		window.parent.document.getElementById("errMesg").innerText = "修正行ありません";	//errMesg;	//親ウインドウにエラー表示
		return false; // 送信を中止
	} else {
		window.parent.document.getElementById("errMesg").innerText = "";	//親ウインドウにエラー表示
		return true; // 送信
	}
}
</script>
<?php
function HDTBL_grid($Mesg) {
echo <<<EOM
	<script type="text/javascript">
	var data = [ $Mesg ];
	//  var data = rest_to_arry(Mesg);	//[ data ];

      var container = document.getElementById('hst'),
      
      hstdata = new Handsontable(container, {
                           data: data,
                           colHeaders: ['選択','ロケーション','バーコード','ケースバラ区分','レーン区分','商品コード','商品名','入数','発注単位','ボール入数'],
	                       
	                       colWidths: [60,100,100,1,1,140,280,60,70,90],

						    columns: [
					 		{data: '選択',type: 'checkbox'},
					        {readOnly: true},	//読み取り専用
					        {readOnly: true},	//読み取り専用
					        {readOnly: true},	//読み取り専用
					        {readOnly: true},	//読み取り専用
					        {readOnly: true},	//読み取り専用
					        {readOnly: true},	//読み取り専用
					        {readOnly: true, type: 'numeric', numericFormat: {pattern: '0,0'}},
					        {readOnly: true, type: 'numeric', numericFormat: {pattern: '0,0'}},
					        {readOnly: true, type: 'numeric', numericFormat: {pattern: '0,0'}}
						 	],
     					 	beforeChange: function(changes, source) {
								// change[0]の中に、変更した[0]行数、[1]列数、[2]変更前の値、[3}変更後の値が
        						if(source === 'edit') {
	        						onRow = changes[0][0];			// 変更行数の取得
	        						onData = changes[0][3];			// 変更後のデータ取得
	        						onDatax = (changes[0][2]);	// 変更前のカラムデータ取得
	        						onCol = (changes[0][1]) //	- 1;	// 変更のカラムデータ取得
		        						data[onRow][onCol] = onData		// 変更後の値に
        						hst_check(hstdata);	//--------------- deta セット～～～～～～
        						}
     					 	},

     					 	
    					 	enterMoves: { row: 0, col: 1 },	//Enterで列移動
							rowHeaders: true,	//行№表示
                           //columnSorting: true,
                           //disableVisualSelection: true,	//セル選択不可
                           sortIndicator: true,	//sort方向表示
                           fixedColumnsLeft: 3,	//列固定
                           manualColumnResize: true,	//列幅変更可
                           contextMenu: false,
                           copyPaste: true,
                           fillHandle: false,
                           wordWrap: false,
                           trimWhitespace: false,
                           width: 1000,
                           height: 500,

//                            renderAllRows: true,
// 							search: true  //検索有効
                         });
</script>
EOM;
}
//------------------------------------------------------------------------------
// ＰＨＰ　メイン処理
//------------------------------------------------------------------------------
try {
	$db = new PDO($_SESSION['db_dsn'],$_SESSION['db_user'],$_SESSION['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
	);			//ロケーションラベル
//				ml.loca_cd,		//.  '	,fnc_loca_hyoji(ml.souko_cd, ml.loca_cd) AS loca_cd'
//				ma.hokan_kbn,	//.  '	,fnc_code_name(\'AK002\',ma.hokan_kbn, \'0\') AS hokan_kbn'
//				ma.hht_bara,	//.  '	,fnc_code_name(\'AK003\',ma.hht_bara, \'0\') AS hht_bara'
//				nlN.lane_kbn,	//.  '	,fnc_code_name(\'RK001\',mlN.lane_kbn, \'0\') AS lane_kbn'
	$sql = ' SELECT
				ml.souko_cd,ml.loca_cd,
				ml.syohin_cd,
				ms.syohin_nm,ms.case_irisu,ms.hacyu_tani,
				ml.kakunoh_case,ms.boll_irisu

			FROM m_locakanri ml
			LEFT JOIN m_syohin ms
				ON ml.jisya_cd = ms.jisya_cd
				AND ml.syohin_cd = ms.syohin_cd';

			$sql.=' WHERE ml.souko_cd = '.$_POST['souko_cd'];

			// ロケーションコード 検索条件
			if ($_POST['loca_cd'] != "") {
				$sql.= ' and ml.loca_cd like "%'.$_POST['loca_cd'].'%"';
			}

			// 商品コード 検索条件
			if ($_POST['syohin_cd'] != "")  {
				$sql .= ' AND ml.syohin_cd = '.$_POST['syohin_cd'];
			}

			// order by
			$sql .= ' ORDER BY ml.souko_cd,ml.loca_cd, ml.syohin_cd';
//		print "<br/> sql=".$sql;
		$stmt = $db->prepare($sql);
		$stmt ->execute();
		$count = $stmt -> rowCount();
	$Mesg="";	
	$space="";
	while($rec=$stmt->fetch(PDO::FETCH_ASSOC)) {
		$loca_cd_display=fnc_loca_cd_hyoji($rec['loca_cd']);	//ケース　バラ計算
		if (strlen($rec['loca_cd']) > 7) {
			$cchr=mb_substr($rec['loca_cd'],1,1);
			$code=fnc_m_locakana_read($cchr);
//			print "<br/> kana=".$cchr." code=".$code; 
			$bar_code=substr($rec['loca_cd'],0,1).$code.mb_substr($rec['loca_cd'],2,5);
		} else {
			$bar_code=$rec['loca_cd'];
		}
		if ($Mesg != "") {$Mesg.=",";}
		$Mesg .= "[\"".$space."\",
			\"".$loca_cd_display."\",
			\"".$bar_code."\",
			\"".$space."\",
			\"".$space."\",
			\"".$rec['syohin_cd']."\",
			\"".$rec['syohin_nm']."\",
			\"".$rec['case_irisu']."\",
			\"".$rec['hacyu_tani']."\",
			\"".$rec['boll_irisu']."\"]";
	}
	$db=null;


	if ($Mesg != "") {
		HDTBL_grid($Mesg);
echo <<<EOM
<script type="text/javascript">
		
		

		Mesg.innerHTML = '<input type="submit" value="ラベル印刷"  style="background-color:#99ff33;width:10%;height: 30px;">';
		//<button><a href="example.php">ラベル印刷</a></button>
</script>
EOM;

	} else {
		print "該当データありません";
	}

}	
	catch (Exception $e) {
		PHP_DBalert($e);
		//print 'ただいま障害により大変ご迷惑をお掛けしております。';
		exit();
	}
?>
<input type="hidden" name="action" value="<?php echo $_POST["action"] ?>">
<input type="hidden" name="souko_cd" value="<?php echo $_POST["souko_cd"] ?>">
<input type="hidden" name="tablearea" value="">

</form>
</body>
</html>
