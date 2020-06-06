<?php
/****************************
システム名 販売・物流管理
サブシステム名 マスタ（PC)
業務名 ロケーションマスタ
作成者 CSE
更新履歴
2013.08.19 acty食品センター仕様に新規作成
2013.12.11 getSearchSqlString SELECTにレーン管理マスタ・部門を追加
2014.10.06 Gridのロケを入力時、出荷識別番号も自動採番し値を設定するよう修正
*****************************/

/**
 *
 * @author acty_syokuhin
 *
 */

require_once 'jp/co/acty_kitakanto/print/MTP170PR_1.php';
class MTP170A
{
	// CSVヘッダ情報
	private static $csvHeaderList = array(
										'倉庫コード',
										'ロケーション',
										'保管区分',
										'ケースバラ区分',
										'レーン区分',
										'商品コード',
										'商品名',
										'格納ケース',
										'ピック順',
										'入荷格納順',
										'出荷識別番号',
										'部門'
									);
	/**
	 * 検索処理
	 * @param $params
	 * @return unknown_type
	 */
	public function search($params) {

		Zend_Registry::get('log')->debug('MTP170A search start');

		// データベースへ接続
		$db = Zend_Registry::get('db');
		$db->setFetchMode(Zend_Db::FETCH_NUM);

		//SQL文取得
		$sql = $this->getSearchSqlString($db,$params,0);
		Zend_Registry::get('log')->debug('検索SQL文字列'.$sql);

		$list = array();
		try {
			$stmt = $db->query($sql);
			while($row = $stmt->fetch()) {
				$joinStr = join(DELIMITER_COLUMN, $row);
				array_push($list, $joinStr);
			}
		} catch (Exception $e) {
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}
		Zend_Registry::get('log')->debug('取得件数:'.count($list));
		if (count($list) < 1) {
			Zend_Registry::get('log')->debug('MTP170A search end');
			return new RetParameter('9', 'COM000001', NULL, $list);
		}

//		Zend_Registry::get('log')->debug(Utility::varDumpString($list));

		$listJoinStr = join(DELIMITER_ROW, $list);

		Zend_Registry::get('log')->debug('MTP170A search end');

		return new RetParameter('0', '', NULL, $listJoinStr);

	}

	/**
	 * 更新処理
	 * @param $params
	 * @return unknown_type
	 */
	public function update($params) {

		Zend_Registry::get('log')->debug('MTP170A update start');

		// データベースへ接続
		$db = Zend_Registry::get('db');

		//**** 棚卸チェック処理 ****
		try {
			if(Utility::chkTana($params->soukoCd, '') == 1){
				return new RetParameter('9', 'TNP010013', Array(''), NULL);
			}
		}catch (Exception $e){
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		// トラザクション処理の実行
		$db->beginTransaction();
		
		$retNum = '';
		$retMsg = '';
		$ret = array();

		$value = $params->arrayList;
		foreach($value as $childValue) {

			//Zend_Registry::get('log')->debug(Utility::varDumpString($childValue));

			// 変更可否フラグ
			$updateFlg = false;

			// 更新対象項目が変更されていれば登録/更新対象
			// 倉庫コード
			if($childValue->souko_cd != $childValue->b_souko_cd) {
				$updateFlg = true;
			}
			// ロケーション
			if($childValue->loca_cd != $childValue->b_loca_cd) {
				$updateFlg = true;
			}
			// レーンコード
			if($childValue->lane_cd != $childValue->b_lane_cd) {
				$updateFlg = true;
			}
			// 商品コード
			if($childValue->syohin_cd != $childValue->b_syohin_cd) {
				$updateFlg = true;
			}
			// 自社コード
			if($childValue->jisya_cd != $childValue->b_jisya_cd) {
				$updateFlg = true;
			}
			// 格納ケース数
			if($childValue->kakunoh_case != $childValue->b_kakunoh_case) {
				$updateFlg = true;
			}
			// ピック順
			if($childValue->pick_order != $childValue->b_pick_order) {
				$updateFlg = true;
			}
			// 入荷格納順
			if($childValue->nyuka_kakunoh != $childValue->b_nyuka_kakunoh) {
				$updateFlg = true;
			}
			// 出荷識別番号
			if($childValue->syuka_shikibetsu != $childValue->b_syuka_shikibetsu) {
				$updateFlg = true;
			}

			// 得意先コードが存在する場合を処理対象とする。
			if (strlen($childValue->souko_cd) > 0 or
					strlen($childValue->loca_cd) > 0) {

				$sql = 'CALL proc_mt_locakanri(';
				$sql .= Utility::sqlString($childValue->b_souko_cd).", ";						// 変更前倉庫コード
				$sql .= Utility::sqlString(str_replace("-","",$childValue->b_loca_cd)).", ";	// 変更前ロケーション
				$sql .= Utility::sqlString($childValue->b_lane_cd).", ";						// 変更前レーンコード
				$sql .= Utility::sqlString($childValue->b_syohin_cd).", ";						// 変更前商品コード
				$sql .= Utility::sqlString($childValue->b_jisya_cd).", ";						// 変更前自社コード
				$sql .= Utility::sqlString($childValue->b_kakunoh_case).", ";					// 変更前格納ケース数
				$sql .= Utility::sqlString($childValue->b_pick_order).", ";						// 変更前ピック順
				$sql .= Utility::sqlString($childValue->b_nyuka_kakunoh).", ";					// 変更前入荷格納順
				$sql .= Utility::sqlString($childValue->b_syuka_shikibetsu).", ";				// 変更前出荷識別番号
				$sql .= Utility::sqlString("PC").", ";											// 端末番号
				$sql .= Utility::sqlString("MTP170").", ";										// プログラムID
				$sql .= Utility::sqlString($params->gTantohsya_cd).", ";						// 作業者コード

				// 新規登録時は倉庫コードをログイン情報より取得する。
				if (strlen($childValue->souko_cd) > 0 ) {
					$sql .= Utility::sqlString($childValue->souko_cd).", "; 					// 倉庫コード
				} else {
					$sql .= Utility::sqlString($params->soukoCd).", ";							// 倉庫コード
				}

				$sql .= Utility::sqlString(str_replace("-","",$childValue->loca_cd)).", ";		// ロケーション
				$sql .= Utility::sqlString($childValue->lane_cd).", ";							// レーンコード
				$sql .= Utility::sqlString($childValue->syohin_cd).", ";						// 商品コード
				$sql .= Utility::sqlString($childValue->syohin_cd).", ";							// 自社コード
				$sql .= Utility::sqlString($childValue->kakunoh_case).", ";						// 格納ケース数
				$sql .= Utility::sqlString($childValue->pick_order).", ";						// ピック順
				$sql .= Utility::sqlString($childValue->nyuka_kakunoh).", ";					// 入荷格納順
				$sql .= Utility::sqlString($childValue->syuka_shikibetsu).", ";					// 出荷識別番号

				// 削除チェックフラグの判定
				if ((int)$childValue->checkbox == 1) {
					// 削除
					$sql .= Utility::sqlString("1").", ";	// 更新/削除フラグ
				} else {
					// 登録/更新
					$sql .= Utility::sqlString("0").", ";	// 更新/削除フラグ
				}
				$sql .= "@strRet, ";	// 結果値
				$sql .= "@strMsg ";		// エラーメッセージ
				$sql .= ")";

				// 削除か更新されている場合に処理実行する。
				if (((int)$childValue->checkbox == 1) || $updateFlg) {
					try {
						Zend_Registry::get('log')->debug('更新SQL文字列'.$sql);

						$db->query($sql);

						$retNum = $db->query("select ifnull(@strRet,-1)")->fetchColumn();
						$retMsg = $db->query("select ifnull(@strMsg,'')")->fetchColumn();

						Zend_Registry::get('log')->debug("@strRet:".$retNum);
						Zend_Registry::get('log')->debug("@strMsg:".$retMsg);

						// ストアド内でのチェックエラー
						if ($retNum != '0') {
							// ロールバック処理
							$db->rollBack();

//							if ($retNum == '1') {
//								return new retParameter('9', 'NYP010001', array($childValue->loca_cd), null);
//							}

							$ret = Utility::splitData($retMsg);
							return new retParameter('9', $ret[0], $ret[1], null);
						}
					} catch (Exception $e) {
						// ロールバック処理
						$db->rollBack();
						Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
						throw $e;
					}
				}
			}
		}

		// コミット処理
		try {
			$db->commit();
		} catch(Exception $e) {
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		Zend_Registry::get('log')->debug('MTP170A update end');

		return new retParameter('0', 'COM000004', null, null);
	}

	/**
	 * ダウンロード処理
	 * @param $params
	 * @return unknown_type
	 */
	public function download($params) {

		Zend_Registry::get('log')->debug('MTP170A download start');

		// データベースへ接続
		$db = Zend_Registry::get('db');
		$db->setFetchMode(Zend_Db::FETCH_NUM);

		//SQL文取得
		$sql = $this->getSearchSqlString($db,$params,0);
		Zend_Registry::get('log')->debug('検索SQL文字列'.$sql);

		$list = array();
		try {
			$stmt = $db->query($sql.$sort);
			while($row = $stmt->fetch()) {
				$rowStr =
				'"'.$row[0].'",'	// 倉庫コード
				.'"'.$row[1].'",'	// ロケーション
				.'"'.$row[2].'",'	// 保管区分
				.'"'.$row[3].'",'	// ケースバラ区分
				.'"'.$row[4].'",'	// レーン区分
				.'"'.$row[5].'",'	// 商品コード
				.'"'.$row[6].'",'	// 商品名
				.'"'.$row[7].'",'	// 格納ケース
				.'"'.$row[8].'",'	// ピック順
				.'"'.$row[9].'",'	// 入荷格納順
				.'"'.$row[10].'",'	// 出荷識別番号
				.'"'.$row[17].'"'	// 部門
				."\r\n";

				array_push($list, $rowStr);
			}
		} catch (Exception $e) {
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		Zend_Registry::get('log')->debug('取得件数:'.count($list));

		// データが取得できなかったときは処理を終了する
		if(count($list) < 1) {
			return new RetParameter('9', 'COM000001', NULL, $list);
		}

		// 年月日日時秒を付けてファイルを作成する。
		$fileName = 'MTP170_'.date('YmdHis').'.csv';

		try {
			$listJoinStr = '"'.join('","', self::$csvHeaderList).'"'."\r\n";
			$listJoinStr .= join('', $list);
			$listJoinStr = Utility::csvString($listJoinStr);
			$handle = fopen(Zend_Registry::get('config')->global->tmp->path.$fileName, 'w');
			if (fwrite($handle, $listJoinStr) === FALSE) {
				Zend_Registry::get('log')->err('ファイル書き込みに失敗しました');
			}

			fclose($handle);
		} catch (Exception $e) {
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		Zend_Registry::get('log')->debug('MTP170A download end');

		return new RetParameter('0', '', null, 'http://'.$_SERVER['HTTP_HOST'].'/'.Zend_Registry::get('config')->global->tmp->name.'/'.$fileName);
	}

	/**
	 * ゾーン区分取得
	 * @param $params
	 * @return unknown_type
	 */
/*
	public function getZoneKbn($params) {

		Zend_Registry::get('log')->debug('MTP170A getZoneKbn start');

		// データベースへ接続
		$db = Zend_Registry::get('db');

		$codes = $params->keyCode;


		// 画面入力に表示しているハイフンをクリアした状態にて検索を行う。
		$loca_cd = $codes[1];
		$loca_cd = str_replace("-","",$loca_cd);
				
		$sql = ' select CONCAT(MC.name_cd, \' - \', MC.code_name) AS zone_kbn, ';
		$sql .= ' fnc_getZoneCd('. Utility::sqlString($codes[0]) .', '. Utility::sqlString($loca_cd) .') AS zone_cd, ';
		$sql .= ' UPPER(fnc_loca_hyoji('. Utility::sqlString($codes[0]) .', '. Utility::sqlString($loca_cd) .')) AS loca_cd ';
		$sql .= ' from m_codename MC, m_zone MZ ';
		$sql .= ' where MC.name_kbn = '.Utility::sqlString('ZK001');
		$sql .= ' AND MC.name_cd = MZ.zone_kbn ';
		$sql .= ' AND MZ.zone_cd = fnc_getZoneCd('. Utility::sqlString($codes[0]) .','. Utility::sqlString($loca_cd) .')';
		$sql .= ' AND MZ.souko_cd = '.Utility::sqlString($codes[0]);

		Zend_Registry::get('log')->debug('実行SQL：'.$sql);

		try {
			// コンボボックス用多次元配列SQLにて検索結果を取得する。
			$retRecord = $db->fetchALL($sql);

			if (count($retRecord) < 1)
			{
				// ゾーン区分が取得出来なかった時---エラー
				$sql = ' select';
				$sql .= ' fnc_getZoneCd('.Utility::sqlString($codes[0]) .','. Utility::sqlString($loca_cd) .') AS zone_cd ';
				$retRecord = $db->fetchALL($sql);
				$ret = Utility::splitData('COM000013:ゾーン管理マスタ:'.$codes[0].'-'.$retRecord[0]["zone_cd"]);
				return new retParameter('9', $ret[0], $ret[1], null);
			}
			else
			{
				$retStr[0] = $params->rowIndex;
				$retStr[1] = $params->setColumName;
				$retStr[2] = $retRecord[0]["zone_kbn"];
				$retStr[3] = $retRecord[0]["zone_cd"];
				$retStr[4] = $retRecord[0]["loca_cd"];
			}
		} catch (Exception $e) {
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		Zend_Registry::get('log')->debug('MTP170A getZoneKbn end');

		return new retParameter('0', '', NULL, $retStr);
	}
*/

	/**
	 * 検索時SQL
	 * @param $params	クライアントからの情報
	 * @param $flag		0:検索時 1:印刷時
	 * @return String	
	 */
	 private function getSearchSqlString($db,$params,$flag) {
	 	
	 	$keta = $this->getLocaKeta($params->soukoCd, $db, 0);
	 	$sql = '';
	 	$where = '';

//		if($params->prnKbn == '0' || $params->prnKbn == '1')
//		{
			//ロケーションラベル
			$sql = ' SELECT '
				.  '	 ML.souko_cd'
				.  '	,fnc_loca_hyoji(ML.souko_cd, ML.loca_cd) AS loca_cd'
				.  '	,fnc_code_name(\'AK002\',MA.hokan_kbn, \'0\') AS hokan_kbn'
				.  '	,fnc_code_name(\'AK003\',MA.hht_bara, \'0\') AS hht_bara'
				.  '	,fnc_code_name(\'RK001\',MLN.lane_kbn, \'0\') AS lane_kbn'
				.  '	,MS.syohin_cd'
				.  '	,MS.syohin_nm'
				.  '	,ML.kakunoh_case'
				.  '	,ML.pick_order'
				.  '	,ML.nyuka_kakunoh'
				.  '	,ML.syuka_shikibetsu'
				.  '	,ML.lane_cd'
				.  '	,ML.jisya_cd'				
				.  '	,MA.hokan_kbn AS hokan_kbn_cd'
				.  '	,MA.hht_bara AS hht_bara_cd'
				.  '	,MS.area_cd AS syohin_area_cd'
				.  '	,MLN.lane_kbn AS lane_kbn_cd'
				.  '	,fnc_code_name(\'RK002\',MLN.bumon, \'0\') AS bumon'
				.  '	,MA.area_cd'
				.  '	,MA.reserve_kbn'
				.  '	,MA2.bara_syuka_area_cd'
				.  ' FROM m_locakanri ML'
				.  '	LEFT JOIN m_area MA'
				.  '	ON ML.souko_cd = MA.souko_cd'
				.  '	AND fnc_getAreaCd(ML.souko_cd, ML.loca_cd) = MA.area_cd'
				.  '	LEFT JOIN m_lane MLN'
				.  '	ON ML.souko_cd = MLN.souko_cd'
				.  '	AND ML.lane_cd = MLN.lane_cd'
				.  '	LEFT JOIN m_syohin MS'
				.  '	ON ML.jisya_cd = MS.jisya_cd'
				.  '	AND ML.syohin_cd = MS.syohin_cd'
				.  '	LEFT JOIN	m_area MA2'
				.  '	ON	ML.souko_cd	= MA2.souko_cd'
				.  '	AND	MS.area_cd	= MA2.area_cd'
			;

			$where.=' WHERE ML.souko_cd = '.Utility::sqlString($params->soukoCd);

			// ロケーションコード 検索条件
			if (strlen($params->locaCdFrom) > 0) {
				$where .= ' AND '
					   .  ' ML.loca_cd >= Rpad('.Utility::sqlString(str_replace("-","",$params->locaCdFrom)).', '.$keta.', "0")';
			}

			// ロケーションコード 検索条件
			if (strlen($params->locaCdTo) > 0) {
				$where .= ' AND '
//					   .  ' ML.loca_cd <= Rpad('.Utility::sqlString(str_replace("-","",$params->locaCdTo)).', '.'10'.', "Z")';
					   .  ' ML.loca_cd <= Rpad('.Utility::sqlString(str_replace("-","",$params->locaCdTo)).', '.$keta.', "ﾝ")';
			}

			// 商品コード 検索条件
			if ($params->syohinCd > 0) {
				$where .= ' AND ML.syohin_cd = '.$db->quote($params->syohinCd);
			}

			// where条件
			$sql .= $where;

			// order by
			$sql .= ' ORDER BY ML.souko_cd'
				 .	'		 , ML.loca_cd'
				 .	'		 , ML.syohin_cd';
/*
		}
		else
		{
			// 商品ロケラベル
			if($flag == 0){
				// 検索時
				$sql = ' SELECT '
					.  '		 ML.souko_cd'
					.  '		,fnc_loca_hyoji(ML.souko_cd, ML.loca_cd) AS loca_cd'
					.  '		,ML.zone_cd'
					.  '		,fnc_code_name(\'ZK001\',MZ.zone_kbn, \'0\') AS zone_kbn'
					.  '		,MK.loca_kbn'
					.  '		,\'\''
					.  '		,\'\''
					.  '		,\'\''
					.  '		,\'\''
				;
			}else{
				// ラベル発行時
				$sql = ' SELECT '
					.  '		 ML.souko_cd'
					.  '		,fnc_loca_hyoji(ML.souko_cd, ML.loca_cd) AS loca_cd'
					.  '		,ML.zone_cd'
					.  '		,fnc_code_name(\'ZK001\',MZ.zone_kbn, \'0\') AS zone_kbn'
					.  '		,MK.loca_kbn'
					.  '		,MS.jisya_cd'
					.  '		,MS.syohin_cd'
					.  '		,MS.syohin_nm'
					.  '		,MS.case_irisu'
				;
			}
			$sql.= ' FROM	m_locazaiko ML'
				.  '	LEFT JOIN  m_zone MZ'
				.  '	ON ML.souko_cd = MZ.souko_cd'
				.  '	AND ML.zone_cd = MZ.zone_cd'
				.  '	LEFT JOIN  m_locakanri MK'
				.  '	ON ML.souko_cd = MK.souko_cd'
				.  '	AND ML.loca_cd = MK.loca_cd'
				.  '	LEFT JOIN  m_syohin MS'
				.  '	ON ML.jisya_cd = MS.jisya_cd'
				.  '	AND ML.syohin_cd = MS.syohin_cd'
			;
			// 検索条件の有無
			// 倉庫コード 検索条件（完全一致）
			$where .= ' WHERE '
				   .  ' ML.souko_cd = '.Utility::sqlString($params->soukoCd);

			// ロケーションコード 検索条件
			if (strlen($params->locaCdFrom) > 0) {
				$where .= ' AND '
					   .  ' ML.loca_cd >= '.Utility::sqlString(str_replace("-","",$params->locaCdFrom));
			}

			// ロケーションコード 検索条件
			if (strlen($params->locaCdTo) > 0) {
				$where .= ' AND '
					   .  ' ML.loca_cd <= '.Utility::sqlString(str_replace("-","",$params->locaCdTo));
			}

			// 登録日 検索条件
			if (strlen($params->EntryDateFrom) > 0) {
				$where .= ' AND '
					   .  ' REPLACE(DATE(MK.entry_time),"-","") >= REPLACE('.Utility::sqlString($params->EntryDateFrom).', "/", "")';
			}

			// 登録日 検索条件
			if (strlen($params->EntryDateTo) > 0) {
				$where .= ' AND '
					   .  ' REPLACE(DATE(MK.entry_time),"-","") <= REPLACE('.Utility::sqlString($params->EntryDateTo).', "/", "")';
			}

			// where条件
			$sql .= $where;
			// 商品ロケラベル 検索の場合
			if($params->prnKbn == '2' && $flag == 0) {
				$sql .= ' GROUP BY '
						.	'  ML.souko_cd'
						.	' ,ML.loca_cd'
						.	' ,ML.zone_cd'
						.	' ,MZ.zone_kbn'
						.	' ,MK.loca_kbn'
				;
				$sql .= ' ORDER BY '
					 .	'	 ML.loca_cd'
					 .	'	,MZ.zone_kbn'
					.	'	,MK.loca_kbn'
				;
			}
			// 商品ロケラベル 印刷の場合
			else if($params->prnKbn == '2' && $flag == 1){
				$sql .= ' GROUP BY '
						.  '	 ML.souko_cd'
						.  '	,ML.loca_cd'
						.  '	,ML.zone_cd'
						.  '	,MZ.zone_kbn'
						.  '	,MK.loca_kbn'
						.  '	,MS.jisya_cd'
						.  '	,MS.syohin_cd'
						.  '	,MS.syohin_nm'
						.  '	,MS.case_irisu'
				;
				// order by
				$sql .= ' ORDER BY loca_cd'
					 .	'		 , jisya_cd'
					 .	'		 , syohin_cd';
			}

		}
*/
		return $sql;
	 }

	/**
	 * ラベル発行
	 * @param $params
	 * @return unknown_type
	 */
	public function lblPrint($params){

		Zend_Registry::get('log')->debug('MTP170A lblPrint start');

		$value = array();
		$j = 0;

		// データベースへ接続
		$db = Zend_Registry::get('db');

		//SQL文取得
/* 		$sql = $this->getSearchSqlString($db,$params,1);
		Zend_Registry::get('log')->debug('検索SQL文字列'.$sql);

		try {
			//実行
			$value = $db->fetchAll($sql);
			if(count($value) < 1){
				return new retParameter('9', 'COM000001', NULL, NULL);
			}
		} catch (Exception $e) {
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}
*/
		//**************************************
		//	印刷処理
		//**************************************
		$retUrl = "";

		try{
			Zend_Registry::get('log')->debug("MTP170A pdf printing ...");
			// ピッキングリストを設定するクラスを生成し印刷処理を行う。
			$pdf = new MTP170PR_1('L', PDF_UNIT, PDF_PAGE_FORMAT);
			//$pdf = new ZKP020PR('L', PDF_UNIT, PDF_PAGE_FORMAT);
			$retUrl = $pdf->CreatePdf($params->arrayList, $params->prnKbn);
		}catch(Exception $e){
			Zend_Registry::get('log')->err("印刷エラー:".$e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}
		if (strlen($retUrl) < 1) {
			Zend_Registry::get('log')->err("印刷処理に失敗しました。");
			throw new Exception("印刷処理に失敗しました。");
		}

		Zend_Registry::get('log')->debug('MTP170A lblPrint end');
		return new retParameter('0', '', null, $retUrl);

		/*
		foreach($value as $childValue)
		{
			// =================================
			if($params->prnKbn == '0')
			{
				// ロケラベル(大)
				// 印字設定---------------------
				// STXを設定します
				$EditWk .= $STX;
				// データ送信の開始を設定します
				$EditWk .= $ESC.'A';
				// 用紙サイズを設定します。
				// 縦(480ﾄﾞｯﾄ)横(735)
				$EditWk .= $ESC.'A105040759';
				// 基点補正
				// 縦(0ﾄﾞｯﾄ)横(170ﾄﾞｯﾄ)
				$EditWk .= $ESC.'A3V+000H+040';
				// 漢字コードをShiftJISに設定します。
				$EditWk .= $ESC.'KC1';
				// -----------------------------

				// ｢ ロケーション｣の編集--------------------
				// 横位置(40ﾄﾞｯﾄ),縦位置(140ﾄﾞｯﾄ),文字ピッチ(0ﾄﾞｯﾄ),横倍率(1倍),縦倍率(2倍) ※CODE39はデータにより長さが可変する
				$EditWk .= $ESC.'V0090'.$ESC.'H0040'.$ESC.'P00'.$ESC.'L0404';
				//$EditWk .= $ESC.'K9B'.'A01-01-1';
				$EditWk .= $ESC.'K9B'.$childValue["loca_cd"];

				// バーコードの編集-------------------------
				// 横位置(40ﾄﾞｯﾄ),縦位置(200ﾄﾞｯﾄ);
				$EditWk .= $ESC.'V0230'.$ESC.'H0040';
				// バーコード種類(CODE39),バー幅拡大率(4倍),バー天地寸法(70ﾄﾞｯﾄ)
				$EditWk .= $ESC.'D104160'.'*'.str_replace('-','',$childValue["loca_cd"]).'*';
			}else if($params->prnKbn == '1'){
				// ロケラベル(小)

				// 印字設定---------------------
				// STXを設定します
				$EditWk .= $STX;
				// データ送信の開始を設定します
				$EditWk .= $ESC.'A';
				// 用紙サイズを設定します。
				// 縦(480ﾄﾞｯﾄ)横(735)
				$EditWk .= $ESC.'A102320759';
				// 基点補正
				// 縦(0ﾄﾞｯﾄ)横(170ﾄﾞｯﾄ)
				$EditWk .= $ESC.'A3V+000H+020';
				// 漢字コードをShiftJISに設定します。
				$EditWk .= $ESC.'KC1';
				// -----------------------------

				// ｢ ロケーション｣の編集--------------------
				// 横位置(40ﾄﾞｯﾄ),縦位置(140ﾄﾞｯﾄ),文字ピッチ(0ﾄﾞｯﾄ),横倍率(1倍),縦倍率(2倍) ※CODE39はデータにより長さが可変する
				$EditWk .= $ESC.'V0020'.$ESC.'H0040'.$ESC.'P00'.$ESC.'L0303';
				//$EditWk .= $ESC.'K9B'.'A01-01-1';
				$EditWk .= $ESC.'K9B'.$childValue["loca_cd"];

				// バーコードの編集-------------------------
				// 横位置(40ﾄﾞｯﾄ),縦位置(200ﾄﾞｯﾄ);
				$EditWk .= $ESC.'V0100'.$ESC.'H0040';
				// バーコード種類(CODE39),バー幅拡大率(4倍),バー天地寸法(70ﾄﾞｯﾄ)
				$EditWk .= $ESC.'D104110'.'*'.str_replace('-','',$childValue["loca_cd"]).'*';
			}else if($params->prnKbn == '2'){
				// 商品ロケラベル

				// 印字設定---------------------
				$EditWk .= $STX;
				// データ送信の開始を設定します
				$EditWk .= $ESC.'A';
				// 用紙サイズを設定します。
				// 縦(232ﾄﾞｯﾄ)横(759)
				$EditWk .= $ESC.'A102320759';
				// 基点補正(縦のマイナス補正ができない？)
				// 縦(0ﾄﾞｯﾄ)横(145ﾄﾞｯﾄ)
				$EditWk .= $ESC.'A3V+000H+020';
				// 漢字コードをShiftJISに設定します。
				$EditWk .= $ESC.'KC1';
				// -----------------------------

				// ｢ ロケーション ｣の編集-------------------
				// 横位置(20ﾄﾞｯﾄ),縦位置(20ﾄﾞｯﾄ),文字ピッチ(0ﾄﾞｯﾄ),横倍率(2倍),縦倍率(2倍)
				$EditWk .= $ESC.'V0050'.$ESC.'H0030'.$ESC.'P00'.$ESC.'L0202';
				$EditWk .= $ESC.'K9B'.$childValue["loca_cd"];
				//$EditWk .= $ESC.'K9B'.$childValue["loca_cd"];

				// ｢ 1段目バーコード(ロケ)｣の編集-----------
				// 横位置(500ﾄﾞｯﾄ),縦位置(20ﾄﾞｯﾄ)
				//$EditWk .= $ESC.'V0020'.$ESC.'H0400';//出からベル
				$EditWk .= $ESC.'V0050'.$ESC.'H0400';//細ラベル
				// バーコード種類(CODE39),バー幅拡大率(2倍),バー天地寸法(50ﾄﾞｯﾄ)
				//$EditWk .= $ESC.'D102050'.'*'.str_replace('-','',$childValue->loca_cd).'*';
				$EditWk .= $ESC.'D102050'.'*'.str_replace('-','',$childValue["loca_cd"]).'*';

				// ｢ 2段目バーコード(自社コード)｣の編集-----
				// 横位置(30ﾄﾞｯﾄ),縦位置(80ﾄﾞｯﾄ)
				$EditWk .= $ESC.'V0110'.$ESC.'H0030';
				// バーコード種類(CODE39 1:3),バー幅拡大率(1倍),バー天地寸法(30ﾄﾞｯﾄ)
				$EditWk .= $ESC.'D101030'.'*'.substr($childValue["jisya_cd"],0,6).'*';

				// ｢ 商品名 1段目｣の編集------------------------
				// 横位置(150ﾄﾞｯﾄ),縦位置(80ﾄﾞｯﾄ),横倍率(2倍),縦倍率(2倍),16*16
				$EditWk .= $ESC.'V0110'.$ESC.'H0150'.$ESC.'P00'.$ESC.'L0202';
				$EditWk .= $ESC.'K8B'.mb_substr($childValue["syohin_nm"],0,18,'utf-8');

				// ｢ 商品名 2段目｣の編集------------------------
				// 横位置(150ﾄﾞｯﾄ),縦位置(80ﾄﾞｯﾄ),横倍率(2倍),縦倍率(2倍),16*16
				$EditWk .= $ESC.'V0150'.$ESC.'H0150'.$ESC.'P00'.$ESC.'L0202';
				$EditWk .= $ESC.'K8B'.mb_substr($childValue["syohin_nm"],18,18,'utf-8');
				//$EditWk .= $ESC.'K8B'.'あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをん';

				// ｢ 自社コード ｣の編集--------------------
				// 横位置(30ﾄﾞｯﾄ),縦位置(120ﾄﾞｯﾄ),横倍率(1倍),縦倍率(1倍)
				$EditWk .= $ESC.'V0150'.$ESC.'H0030'.$ESC.'P00'.$ESC.'L0101';
				$EditWk .= $ESC.'K9B'.substr($childValue["jisya_cd"],0,6);

				// ｢ 商品コード ｣の編集--------------------
				// 横位置(150ﾄﾞｯﾄ),縦位置(120ﾄﾞｯﾄ),横倍率(1倍),縦倍率(1倍)
				$EditWk .= $ESC.'V0190'.$ESC.'H0150'.$ESC.'P00'.$ESC.'L0101';
				$EditWk .= $ESC.'K9B'.$childValue["syohin_cd"];

				// ｢ ケース入数 ｣の編集--------------------
				// 横位置(650ﾄﾞｯﾄ),縦位置(120ﾄﾞｯﾄ),横倍率(1倍),縦倍率(1倍)
				$EditWk .= $ESC.'V0190'.$ESC.'H0650'.$ESC.'P00'.$ESC.'L0101';
				$EditWk .= $ESC.'K9B'.'('.$childValue["case_irisu"].')';
			}

			// 印字設定---------------------
			// カット単位指定(とりあえず切らない)
			$EditWk .= $ESC.'CT0';
			// 枚数を設定(1枚)します
			$EditWk .= $ESC.'Q1';
			// データ送信の終了を設定します
			$EditWk .= $ESC.'Z';
			// ETXを設定します
			$EditWk .= $ETX;
			// -----------------------------

			return new retParameter('9', $errCode, NULL, NULL);
		}

		Zend_Registry::get('log')->debug('MTP170A lblPrint end');

		return new retParameter('0', '', NULL, NULL);
		*/
	}

	/*******************************************************************
	 *
	 *  ロケーション桁数を取得
	 *
	 *******************************************************************/
	public function getLocaKeta($soukoCd,$db,$kbn){
		Zend_Registry::get('log')->debug('MTP170A getLocaKeta start');

		$keta = 0;

		$sql = ' SELECT	loca_ketasu '
			.  ' FROM	m_souko '
			.  ' WHERE	souko_cd = '.$db->quote($soukoCd);

		Zend_Registry::get('log')->debug('MTP170A getLocaKeta 取得SQL:'.$sql);

		try{
			$stmt = $db->fetchOne($sql);  		
			Zend_Registry::get('log')->debug('MTP170A getLocaKeta loca_ketasu len:'.strlen($stmt));
			
			if(strlen($stmt) < 1){
				return -1;
			}else{
				if(is_numeric(substr($stmt,$i,1)))
				{
					//ロケ桁数取得
					for($i = 0; $i < strlen($stmt); $i++){
						$keta = $keta + (int)substr($stmt,$i,1);
						
						if ($i === 1 && $kbn === 1) {
							// エリア+連の桁数
							break;
						}
					}
				}
				Zend_Registry::get('log')->debug('MTP170A getLocaKeta loca_ketasu:'.$keta);
			}
		}catch(Exception $e){
			Zend_Registry::get('MTP170A getLocaKeta 例外エラー:')->err($e->getTraceAsString());
		}

		Zend_Registry::get('log')->debug('MTP170A getLocaKeta end');

		return $keta;
	}

	/*******************************************************************
	*
	*  ラベルプリンタIPアドレス取得
	*
	********************************************************************/
	public function getLabelKbn($soukoCd) {

		Zend_Registry::get('log')->debug('MTP170A getLabelKbn start');

		// 倉庫コードが設定されていない場合は検索する必要はない。
		if (strlen($soukoCd) < 1)
		{
			Zend_Registry::get('log')->debug('検索必要なし');
			$ret[0] = $params->rowIndex;
			$ret[1] = $soukoCd;
			return $ret;
		}

		$db = Zend_Registry::get('db');
		//$db->setFetchMode(Zend_Db::FETCH_NUM);

		// プリンタマスタよりIPアドレスを取得する。
		$sql = ' SELECT'
			 . ' printer_nm,'
			 . ' ipaddress'
			 . ' FROM m_printer'
			 . ' WHERE souko_cd = '.Utility::sqlString($soukoCd)
			 . ' AND printer_kbn = \'2\'';

		Zend_Registry::get('log')->debug('検索SQL文字列'.$sql);

			$list = array();
		try {
			// create souko.xml
			$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n";
			$xml .= "<data>\r\n";
			try {
				Zend_Registry::get('log')->debug('検索SQL文字列'.$sql);
				$result = $db->fetchALL($sql);
			} catch (Exception $e) {
				Zend_Registry::get('log')->err("DB検索エラー:".$e->getMessage()."\n".$e->getTraceAsString());
				throw $e;
			}

			foreach($result as $value) 
			{
				$xml .= "\t<combobox>\r\n";
				$xml .= "\t\t<code>".$value["ipaddress"]."</code>\r\n";
				$xml .= "\t\t<label>".$value["printer_nm"]." : ".$value["ipaddress"]."</label>\r\n";
				$xml .= "\t</combobox>\r\n";
			}

			$xml .= "</data>\r\n";
		} catch (Exception $e) {
			Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		Zend_Registry::get('log')->debug('MTP170A getLabelKbn end');
		return $xml;
	}

	/*******************************************************************
	 *
	 *  マスタレコードチェック処理（入力値:loca_cd）
	 *
	 *******************************************************************/
	public function ChkMaster($params) {

		Zend_Registry::get('log')->debug('MTP170A ChkMaster start');
		//Zend_Registry::get('log')->debug(Utility::varDumpString($params));

		$value = $params->keyRecord;
		//Zend_Registry::get('log')->debug(Utility::varDumpString($value));

		$ret[0] = 0;
		$ret[1] = $params->rowIndex;
		$ret[2] = $params->slctype;

		Zend_Registry::get('log')->debug('MTP170A ChkMaster slctype '. $params->slctype);

		$db = Zend_Registry::get('db');

		$keta = $this->getLocaKeta($params->soukoCd, $db, 0);

		if ($params->slctype == 0) {

			// ロケのレーン以降に文字が含まれているかチェック
			$chk = $this->chkLocaString($db, $params->soukoCd, $value->loca_cd);
			Zend_Registry::get('log')->debug('ret:'.$chk);
			
			if ($chk === 0) {
				// 保管区分、ケースバラ区分、レーン区分を取得
				$sql = ' SELECT '
					.  '	 fnc_code_name(\'AK002\',hokan_kbn, \'0\') AS hokan_kbn'
					.  '	,fnc_code_name(\'AK003\',hht_bara, \'0\') AS hht_bara'
					.  '	,hokan_kbn AS hokan_kbn_cd'
					.  '	,hht_bara AS hht_bara_cd'
					.  '	,fnc_get_loca_seq(souko_cd, Rpad('.Utility::sqlString(str_replace("-","",$value->loca_cd)).', '.$keta.', "0")) AS syuka_shikibetsu'
					.  '	,area_cd'
					.  '	,reserve_kbn'
					.  ' FROM m_area'
					.  ' WHERE '		
					.  '	souko_cd = '.Utility::sqlString($params->soukoCd)
					.  ' AND '
					.  '	area_cd = fnc_getAreaCd('.Utility::sqlString($params->soukoCd).', Rpad('.Utility::sqlString(str_replace("-","",$value->loca_cd)).', '.$keta.', "0"))'
				;
			}
			else {
				// 保管区分、ケースバラ区分、レーン区分を取得
				$sql = ' SELECT '
					.  '	 fnc_code_name(\'AK002\',hokan_kbn, \'0\') AS hokan_kbn'
					.  '	,fnc_code_name(\'AK003\',hht_bara, \'0\') AS hht_bara'
					.  '	,hokan_kbn AS hokan_kbn_cd'
					.  '	,hht_bara AS hht_bara_cd'
					.  '	,\'999\' AS syuka_shikibetsu'
					.  '	,area_cd'
					.  '	,reserve_kbn'
					.  ' FROM m_area'
					.  ' WHERE '		
					.  '	souko_cd = '.Utility::sqlString($params->soukoCd)
					.  ' AND '
					.  '	area_cd = fnc_getAreaCd('.Utility::sqlString($params->soukoCd).', Rpad('.Utility::sqlString(str_replace("-","",$value->loca_cd)).', '.$keta.', "0"))'
				;
			}

			Zend_Registry::get('log')->debug('MTP170A ChkMaster 検索SQL文字列'.$sql);

			try {
				// SQLにて検索結果を取得する。
				$list = $db->fetchAll($sql);
				// 検索結果のデバッグ
				//Zend_Registry::get('log')->debug(Utility::varDumpString($list));
			} catch (Exception $e) {
				Zend_Registry::get('log')->err($e->getTraceAsString());
				throw $e;
			}

			// 取得した情報で上書きする。
			$ret[0] = count($list);
			if ($ret[0] > 1) {
				Zend_Registry::get('log')->debug('MTP170A ChkMaster 検索結果なし');
				$ret[0] = 1;
				$value->shikibetsu_chk = $chk;
				$ret[3] = $value;
				return $ret;
			} else if($ret[0] == 1) {
				$value->loca_cd   = $value->loca_cd;
				$value->hokan_kbn = $list[0]['hokan_kbn'];
				$value->hht_bara  = $list[0]['hht_bara'];
				$value->hokan_kbn_cd = $list[0]['hokan_kbn_cd'];
				$value->hht_bara_cd  = $list[0]['hht_bara_cd'];
				$value->syuka_shikibetsu  = $list[0]['syuka_shikibetsu'];
				$value->area_cd  = $list[0]['area_cd'];
				$value->reserve_kbn  = $list[0]['reserve_kbn'];
				$value->shikibetsu_chk = $chk;
			}

			// レーン区分を取得
			$sql2 = ' SELECT '
				.  '	 lane_cd'
				.  '	,fnc_code_name(\'RK001\',lane_kbn, \'0\') AS lane_kbn'
				.  '	,lane_kbn AS lane_kbn_cd'
				.  '	,fnc_code_name(\'RK002\',bumon, \'0\') AS bumon'
				.  ' FROM m_lane'
				.  ' WHERE '		
				.  '	souko_cd = '.Utility::sqlString($params->soukoCd)
				.  ' AND '
				.  '	lane_cd = fnc_getLaneCd('.Utility::sqlString($params->soukoCd).', Rpad('.Utility::sqlString(str_replace("-","",$value->loca_cd)).', '.$keta.', "0"))'
			;

			Zend_Registry::get('log')->debug('MTP170A ChkMaster 検索SQL文字列(レーン区分)'.$sql2);

			try {
				// SQLにて検索結果を取得する。
				$list2 = $db->fetchAll($sql2);
				// 検索結果のデバッグ
				//Zend_Registry::get('log')->debug(Utility::varDumpString($list2));
			} catch (Exception $e) {
				Zend_Registry::get('log')->err($e->getTraceAsString());
				throw $e;
			}

			// 取得した情報で上書きする。
			$ret[0] = count($list2);
			if ($ret[0] > 1) {
				Zend_Registry::get('log')->debug('MTP170A ChkMaster レーン区分 検索結果なし');
				$ret[0] = 1;
				$ret[3] = $value;
				return $ret;
			} else if($ret[0] == 1) {
				$value->lane_cd = $list2[0]['lane_cd'];
				$value->lane_kbn = $list2[0]['lane_kbn'];
				$value->lane_kbn_cd = $list2[0]['lane_kbn_cd'];
				$value->bumon = $list2[0]['bumon'];
			}

		// 商品コード、商品名、自社コードを取得
		} else if ($params->slctype == 1) {
			$sql = ' SELECT '
				.  '	 MS.syohin_cd'
				.  '	,MS.jisya_cd'
				.  '	,MS.syohin_nm'
				.  '	,MS.area_cd'
				.  '	,MA.bara_syuka_area_cd'
				.  ' FROM m_syohin MS'
				.  ' LEFT JOIN	m_area MA'
				.  '	ON	MS.area_cd	= MA.area_cd'
				.  '	AND	MA.souko_cd	= '.Utility::sqlString($params->soukoCd)
				.  ' WHERE '		
				.  '	MS.syohin_cd = '.Utility::sqlString($value->syohin_cd)
			;

			Zend_Registry::get('log')->debug('ChkMaster syohin 検索SQL文字列'.$sql);

			try {
				// SQLにて検索結果を取得する。
				$list = $db->fetchAll($sql);
				// 検索結果のデバッグ
				//Zend_Registry::get('log')->debug(Utility::varDumpString($list));
			} catch (Exception $e) {
				Zend_Registry::get('log')->err($e->getTraceAsString());
				throw $e;
			}

			// 取得した情報で上書きする。
			$ret[0] = count($list);
			if ($ret[0] > 1) {
				Zend_Registry::get('log')->debug('MTP170A ChkMaster 検索結果なし');
				$ret[0] = 1;
				$ret[3] = $value;
				return $ret;
			} else if($ret[0] == 1) {
				$value->syohin_cd = $list[0]['syohin_cd'];
				$value->jisya_cd  = $list[0]['jisya_cd'];
				$value->syohin_nm = $list[0]['syohin_nm'];
				$value->syohin_area_cd = $list[0]['area_cd'];
				$value->syohin_bara_area_cd = $list[0]['bara_syuka_area_cd'];
			}			
		}

		// ロケーション桁数を格納
		$value->loca_keta = $keta;

		$ret[3] = $value;

		Zend_Registry::get('log')->debug('MTP170A ChkMaster end');

		return $ret;
	}
	
	/*******************************************************************
	 *
	 *  レーン以降文字チェック
	 *
	 *******************************************************************/
	public function chkLocaString($db, $souko_cd, $loca_cd) {
		$keta = $this->getLocaKeta($souko_cd, $db, 1);
		// レーン以降のロケを取得
		$str = mb_substr($loca_cd, $keta, strlen($loca_cd) - $keta, 'UTF-8');
		// 数値チェック
		if (!(is_numeric($str))) {
			return 1;
		}
		return 0;
	}

	/*******************************************************************
	 *
	 *  ロケーション桁数を取得
	 *
	 *******************************************************************/
	public function KetaSu($soukoCd){
		Zend_Registry::get('log')->debug('MTP170A getLocaKeta2 start');

		// データベースへ接続
		$db = Zend_Registry::get('db');
		$db->setFetchMode(Zend_Db::FETCH_NUM);

		$keta = 0;

		$sql = ' SELECT	loca_ketasu '
			.  ' FROM	m_souko '
			.  ' WHERE	souko_cd = '.$db->quote($soukoCd);

		Zend_Registry::get('log')->debug('MTP170A getLocaKeta2 取得SQL:'.$sql);

		try{
			$stmt = $db->fetchOne($sql);  		
			Zend_Registry::get('log')->debug('MTP170A getLocaKeta loca_ketasu len:'.strlen($stmt));
			
			if(strlen($stmt) < 1){
				return -1;
			}else{
				if(is_numeric(substr($stmt,$i,1)))
				{
					//ロケ桁数取得
					for($i = 0; $i < strlen($stmt); $i++){
						$keta = $keta + (int)substr($stmt,$i,1);
					}
				}
				Zend_Registry::get('log')->debug('MTP170A getLocaKeta loca_ketasu:'.$keta);
			}
		}catch(Exception $e){
			Zend_Registry::get('MTP170A getLocaKeta 例外エラー:')->err($e->getTraceAsString());
		}

		Zend_Registry::get('log')->debug('MTP170A getLocaKeta2 end');

		//return $keta;
		$listJoinStr[] = $keta;
		//Zend_Registry::get('log')->debug('MTP170A getLocaKeta2 end1111:'.Utility::varDumpString($listJoinStr));
		return new RetParameter('0', '', NULL, $listJoinStr);

	}
}
