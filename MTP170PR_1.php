<?php
/****************************
システム名 販売・物流管理
サブシステム名 マスタ（PC)
業務名 ロケーションラベル印字処理
作成者 水川
更新履歴
2013.03.11 ロケラベル(大)、ロケラベル(小)、商品ロケラベルの作成処理追加
2014.01.11 固定ロケラベルに商品規格・格納ケース数の印字を追加
2014.10.06 固定ラベルのラベルレイアウトを変更（固定で且つケースエリア以外が対象）
2014.10.06 商品ラベルのレイアウトを変更
*****************************/
require_once 'jp/co/acty_kitakanto/Pdf.php';

class MTP170PR_1 extends Pdf
{
	
	//バーコードサイズ
	private $VARCODE_WIDTH;
	private $VARCODE_HIGHT;

	// 印刷時刻
	private $_dateTime;
	// ページ
	private $_page;

	// デバッグ変数（罫線の表示/非表示制御）
	private $_debug = 0;

	// 1データ高さ
	private $_dataHight;

	// 1データ幅
	private $_dataWidth;

	// データフォント
	private $_dataFont = 18;

	//string  $style['position']     バーコードの$wで指定する幅に対する相対位置の指定 [L:左寄(英語日本語などLTR言語での既定値) C:中央 R:右寄(ヘブライ語などRTL言語での既定値) S:ストレッチ]
	//boolean $style['border']       境界線を出力する場合にtrue
	//int     $style['padding']      バーコード周囲の余白
	//array   $style['fgcolor']      前景色
	//mixed   $style['bgcolor']      背景色、falseを指定すると透明
	//boolean $style['text']         バーコードの下にテキストを出力する場合true
	//string  $style['font']         出力テキストのフォント名
	//int     $style['fontsize']     出力テキストのフォントサイズ
	//int     $style['stretchtext']: 出力テキストの伸縮(ストレッチ)モード指定 [0:不可 1:場合により水平伸縮 2:常に水平伸縮 3:場合により空白挿入 4:常に空白挿入]
	// バーコードスタイル
	private $_style = array(
		'position' => 'S',
		'border' => false,
		'padding' => 2,
		'fgcolor' => array(0,0,0),
		'bgcolor' => false,
//		'text' => false,
		'font' => 'arialunicid0',
		'fontsize' => 6,
		'stretchtext' => 4
	);
	
	/* 相対配置は難しいのでここで先に絶対座標を定義する */

	/**
	 *  フリー用の定数
	 */
	// ロケーションコード
	private $s_cd_x = 2;
	private $s_cd_y = 25;
	private $s_cd_w = 110;
	private $s_cd_h = 30;
	private $s_cd_fs = 55;
	
	// ロケーションバーコード
	private $s_bar_x = 2;
	private $s_bar_y = 55;
	private $s_bar_w = 75;
	private $s_bar_h = 14;
	
	// レーンコード
	private $s_lane_x = 2;
	private $s_lane_y = 2;
	private $s_lane_w = 30;
	private $s_lane_h = 20;
	private $s_lane_fs = 40;
	
	// 識別番号
	private $s_shiki_x = 80;
	private $s_shiki_y = 50;
	private $s_shiki_w = 30;
	private $s_shiki_h = 22;
	private $s_shiki_fs = 40;
	
	/**
	 *  固定ロケ用の定数
	 */
	// ロケーションコード
	private $sl_cd_x = 2;
	private $sl_cd_y = 2;
	private $sl_cd_w = 82;
	private $sl_cd_h = 24;
	private $sl_cd_fs = 18;
	
	// ロケーションバーコード
	private $sl_bar_x = 50;
	private $sl_bar_y = 2;
	private $sl_bar_w = 40;
	private $sl_bar_h = 6;
	
	// 自社バーコード
	private $sl_jbar_x = 0;
	private $sl_jbar_y = 10;
	private $sl_jbar_w = 25;
	private $sl_jbar_h = 4;
	
	// 商品名 一段目
	private $sl_snm1_x = 2;
	private $sl_snm1_y = 10;
	private $sl_snm1_w = 88;
	private $sl_snm1_h = 24;
	private $sl_snm1_fs = 11;
	
	// 商品名 二段目
	private $sl_snm2_x = 2;
	private $sl_snm2_y = 15;
	private $sl_snm2_w = 88;
	private $sl_snm2_h = 24;
	private $sl_snm2_fs = 11;

	
	//発注単位
	private $sl_tan_x = 80;
	private $sl_tan_y = 20;
	private $sl_tan_w = 50;
	private $sl_tan_h = 24;
	private $sl_tan_fs = 8;
	
	
	//商品コード
	private $sl_scd_x = 1;
	private $sl_scd_y = 20;
	private $sl_scd_w = 60;
	private $sl_scd_h = 24;
	private $sl_scd_fs = 8;
	
	//JANコード
	private $sl_sjan_x = 24;
	private $sl_sjan_y = 20;
	private $sl_sjan_w = 29;
	private $sl_sjan_h = 4;
	private $sl_sjan_fs = 8;
	
	//ケース入数
	private $sl_isu_x = 50;
	private $sl_isu_y = 20;
	private $sl_isu_w = 50;
	private $sl_isu_h = 24;
	private $sl_isu_fs = 8;
	
	//格納ケース数
	private $sl_kcs_x = 64;
	private $sl_kcs_y = 20;
	private $sl_kcs_w = 50;
	private $sl_kcs_h = 24;
	private $sl_kcs_fs = 8;
	
	
	/**
	 *  固定ロケ用(3階)の定数
	 */
	
	// =========================================================
	//	ロケラベル
	// =========================================================
	// ロケーション
	private $k_lc_cd_x = 2;
	private $k_lc_cd_y = 1;
	private $k_lc_cd_w = 50;
	private $k_lc_cd_h = 35;
	private $k_lc_cd_fs = 25;
	
	// ロケーションBAR
	private $k_lc_bar_x = 2;
	private $k_lc_bar_y = 12;
	private $k_lc_bar_w = 50;
	private $k_lc_bar_h = 12;
	
	// 識別番号
	private $k_seq_no_x = 55;
	private $k_seq_no_y = 3;
	private $k_seq_no_w = 36;
	private $k_seq_no_h = 23;
	private $k_seq_no_fs = 40;
	
	// =========================================================
	//	商品ラベル
	// =========================================================
	// 商品名+規格(1行目)
	private $k_sh_nm_x = 2;
	private $k_sh_nm_y = 10;
	private $k_sh_nm_w = 97;
	private $k_sh_nm_h = 24;
	private $k_sh_nm_fs = 10;
	
	// 商品名+規格(2行目)
	private $k_sh_nm2_x = 2;
	private $k_sh_nm2_y = 15;
	private $k_sh_nm2_w = 97;
	private $k_sh_nm2_h = 24;
	private $k_sh_nm2_fs = 10;
	
	// メーカー名
	private $k_mk_nm_x = 2;
	private $k_mk_nm_y = 2;
	private $k_mk_nm_w = 35;
	private $k_mk_nm_h = 8;
	private $k_mk_nm_fs = 10;
	
	//ケース入数
	private $k_cisu_x = 38;
	private $k_cisu_y = 2;
	private $k_cisu_w = 50;
	private $k_cisu_h = 24;
	private $k_cisu_fs = 15;
	
	//発注単位
	private $k_htan_x = 68;
	private $k_htan_y = 2;
	private $k_htan_w = 50;
	private $k_htan_h = 24;
	private $k_htan_fs = 15;
	
	//ロケーション
	private $k_lc_cd2_x = 77;
	private $k_lc_cd2_y = 21;
	private $k_lc_cd2_w = 20;
	private $k_lc_cd2_h = 24;
	private $k_lc_cd2_fs = 9;
	
	/**
	 * ロケーションラベル印字処理
	 * @param $param
	 * @return unknown_type
	 */
	public function CreatePdf($params, $prnKbn){

		Zend_Registry::get('log')->debug('MTP170PR_1 CreatePdf start');

		try {
			// ファイル名の設定
			$fileName = 'MTP170'.date("YmdHis").'.pdf';

			$Adjustment = 0;
			$barcodeCount = 1;

			// 印刷日時の設定
			$date = new Zend_Date();
			$date->setLocale('ja');
			$this->_dateTime = $date->get(Zend_Date::DATES).' '.$date->get(Zend_Date::TIME_MEDIUM);

			// 初期設定値
			$this->SetFont('arialunicid0');
			$this->SetFontSize($this->_dataFont);

			
			$this->setPrintHeader(false);
			$this->setPrintFooter(false);
		

			$this->SetAutoPageBreak(false,0);
			
			// 引数よりデータを取得する。
			$value = $params;
//Zend_Registry::get('log')->debug('MTP170PR_1 Params:'.Utility::varDumpString($value));
			// 取得したデータ数分繰り返す。
			foreach($value as $childValue)
			{
				//データ取得
		 		$value = $this->getSearchSqlString($childValue);
				$hokanKbn = $value[0]["hokan_kbn"];
				$cb_kbn = $value[0]["hht_bara"];
				$laneKbn = $value[0]["lane_kbn"];
			    $BarcodeLoca = $value[1][0]["BarcodeLoca"];
			    $hyohjiLoca  = $value[1][0]["loca_cd"];

				Zend_Registry::get('log')->debug('出力バーコード情報：'.$BarcodeLoca);
				//$code  :出力するコード
				//$type  :バーコード種別
				//$x     :出力位置のX座標
				//$y     :出力位置のY座標
				//$w     :幅
				//$h     :高さ
				//$xres  :最小幅
				//$style :スタイル
				//$align :画像挿入後に移動するカーソルの垂直方向の位置、以下のいずれか:
				//        T: 右上(RTLの場合は左上)
				//        M: 右中(RTLの場合は左中)
				//        B: 右下(RTLの場合は左下)
				//        N: 次の行

				if ($hokanKbn == "00" && $laneKbn == "00")
				//if ($params->printKbn == "1")
				{
					//保管区分：固定
					$caseIrisu =  $value[1][0]["case_irisu"];
					$hacyuTani =  $value[1][0]["hacyu_tani"];
					$kakunohCase = $value[1][0]["kakunoh_case"];
					$syohinNm = $value[1][0]['syohin_nm'];
					$makerNm = $value[1][0]['maker_nm'];
					$SyukaShikibetsu =  $value[1][0]["syuka_shikibetsu"];
					
					// 新規にページを作成
					$this->AddPage("O", Array(95, 29));
					
//					$this->SetXY(0, 0);
//					$this->MultiCell(95, 29, '', 1, 'L', 0, 1);
					
					if ($cb_kbn != '00') {
						
						if ($prnKbn == '0') {
							// ロケラベル
							
							// ロケーション印字
							$this->SetXY($this->k_lc_cd_x, $this->k_lc_cd_y);
							$this->SetFontSize($this->k_lc_cd_fs);
							$this->MultiCell($this->k_lc_cd_w, $this->k_lc_cd_h, $hyohjiLoca, $this->_debug, 'L', 0, 1);
							
							// ロケーションバーコード印字
							$this->write1DBarcode($BarcodeLoca, 'C128A', $this->k_lc_bar_x, $this->k_lc_bar_y, $this->k_lc_bar_w, $this->k_lc_bar_h, 0.4, $this->_style, 'N');
							
							// 識別番号
							$this->SetTextColor(255,255,255);
							$this->SetXY($this->k_seq_no_x, $this->k_seq_no_y);
							//$this->SetFontSize($this->k_seq_no_fs, 'B');
							$this->SetFont('arialunicid0', 'B', $this->k_seq_no_fs);
							$this->MultiCell($this->k_seq_no_w, $this->k_seq_no_h, $SyukaShikibetsu, $this->_debug, 'C', 1, 1,'','',true,0,false,true,$this->k_seq_no_h,'M');
							$this->SetTextColor(0,0,0);
							$this->SetFont('arialunicid0', '');
							
						} else {
							// 商品ラベル
							
							// 商品名一段目印字
							$this->SetXY($this->k_sh_nm_x, $this->k_sh_nm_y);
							$this->SetFontSize($this->k_sh_nm_fs);
							$this->MultiCell($this->k_sh_nm_w, $this->k_sh_nm_h, mb_substr($syohinNm,0,25,'utf-8'), $this->_debug, 'L', 0, 1);
							
							// 商品名二段目印字
							$this->SetXY($this->k_sh_nm2_x, $this->k_sh_nm2_y);
							$this->SetFontSize($this->k_sh_nm2_fs);
							$this->MultiCell($this->k_sh_nm2_w, $this->k_sh_nm2_h, mb_substr($syohinNm,25,25,'utf-8'), $this->_debug, 'L', 0, 1);
		
							// メーカー名印字
							$this->SetXY($this->k_mk_nm_x, $this->k_mk_nm_y);
							$this->SetFontSize($this->k_mk_nm_fs);
							//$this->MultiCell($this->k_mk_nm_w, $this->k_mk_nm_h, $makerNm, $this->_debug, 'L', 0, 1);
							$this->CutCell($this->k_mk_nm_w, $this->k_mk_nm_h, $makerNm, 0, 'L', 'arialunicid0', $this->k_mk_nm_fs);
		
							// ケース入数印字
							$this->SetXY($this->k_cisu_x, $this->k_cisu_y);
							//$this->SetFontSize($this->k_cisu_fs, 'B');
							$this->SetFont('arialunicid0', 'B', $this->k_cisu_fs);
							$this->MultiCell($this->k_cisu_w, $this->k_cisu_h, "入数(" . $caseIrisu . ")", $this->_debug, 'L', 0, 1);

							// 発注単位印字
							$this->SetXY($this->k_htan_x, $this->k_htan_y);
							//$this->SetFontSize($this->k_htan_fs, 'B');
							$this->SetFont('arialunicid0', 'B', $this->k_htan_fs);
							$this->MultiCell($this->k_htan_w, $this->k_htan_h, "発単(" . $hacyuTani . ")", $this->_debug, 'L', 0, 1);
							$this->SetFont('arialunicid0', '');
							
							// ロケーション印字
							$this->SetXY($this->k_lc_cd2_x, $this->k_lc_cd2_y);
							$this->SetFontSize($this->k_lc_cd2_fs);
							$this->MultiCell($this->k_lc_cd2_w, $this->k_lc_cd2_h, $hyohjiLoca, $this->_debug, 'L', 0, 1);
						}
					}
					else
					{
						// 商品ラベル
						// ロケーションコード印字
						$this->SetXY($this->sl_cd_x, $this->sl_cd_y);
						$this->SetFontSize($this->sl_cd_fs);
						$this->MultiCell($this->sl_cd_w, $this->sl_cd_h, $childValue->loca_cd, $this->_debug, 'L', 0, 1);
						
						// ロケーションバーコード印字
						$this->write1DBarcode($BarcodeLoca, 'C128A', $this->sl_bar_x, $this->sl_bar_y, $this->sl_bar_w, $this->sl_bar_h, 0.4, $this->_style, 'N');
						
						Zend_Registry::get('log')->debug(mb_strlen($childValue->syohin_nm, 'utf-8'));
						
						// 商品名一段目印字
						$this->SetXY($this->sl_snm1_x, $this->sl_snm1_y);
						$this->SetFontSize($this->sl_snm1_fs);
						$this->MultiCell($this->sl_snm1_w, $this->sl_snm1_h, mb_substr($syohinNm,0,22,'utf-8'), $this->_debug, 'L', 0, 1);
						
						// 商品名二段目印字
						$this->SetXY($this->sl_snm2_x, $this->sl_snm2_y);
						$this->SetFontSize($this->sl_snm2_fs);
						$this->MultiCell($this->sl_snm2_w, $this->sl_snm2_h, mb_substr($syohinNm,22,22,'utf-8'), $this->_debug, 'L', 0, 1);
	
						// 商品コード印字
						$this->SetXY($this->sl_scd_x, $this->sl_scd_y);
						$this->SetFontSize($this->sl_scd_fs);
						$this->MultiCell($this->sl_scd_w, $this->sl_scd_h, $childValue->syohin_cd, $this->_debug, 'L', 0, 1);
	
						// ケース入数印字
						$this->SetXY($this->sl_isu_x, $this->sl_isu_y);
						$this->SetFontSize($this->sl_isu_fs);
						$this->MultiCell($this->sl_isu_w, $this->sl_isu_h, "入数(" . $caseIrisu . ")", $this->_debug, 'L', 0, 1);
						// 格納ケース数印字
						$this->SetXY($this->sl_kcs_x, $this->sl_kcs_y);
						$this->SetFontSize($this->sl_tan_fs);
						$this->MultiCell($this->sl_kcs_w, $this->sl_kcs_h, "格納数(" . $kakunohCase . ")", $this->_debug, 'L', 0, 1);
						// 発注単位印字
						$this->SetXY($this->sl_tan_x, $this->sl_tan_y);
						$this->SetFontSize($this->sl_tan_fs);
						$this->MultiCell($this->sl_tan_w, $this->sl_tan_h, "発単(" . $hacyuTani . ")", $this->_debug, 'L', 0, 1);
					}
				}
				else//elseif($params->printKbn == "0")
				{	//保管区分：フリー
//					$LaneCd =  $value[1][0]["lane_cd"];
//					$SyukaShikibetsu =  $value[1][0]["syuka_shikibetsu"];
//					//ロケの切り取り
//					$locaHyoji = $value[1][0]["loca_hyoji"];
//					$locaHyoji = substr($locaHyoji,0,1);
//					$kiritoriSu = 1 + (int)$locaHyoji;
//					Zend_Registry::get('log')->debug('kiritori:'.$kiritoriSu);
//					$locaCD    = mb_substr($hyohjiLoca,$kiritoriSu,20,"UTF-8");
//					Zend_Registry::get('log')->debug('kiritori1:'.$locaCD);
//					// 新規にページを作成
//					$this->AddPage("O", Array(115, 80));
//					
//					// ロケーションコード印字位置、フォントサイズをセット
//					$this->SetXY($this->s_cd_x, $this->s_cd_y);
//					$this->SetFontSize($this->s_cd_fs);
//					// 印字
//					$this->MultiCell($this->s_cd_w, $this->s_cd_h, $hyohjiLoca, $this->_debug, 'L', 0, 1);
//
//					// ロケーションバーコード印字
//					$this->write1DBarcode($BarcodeLoca, 'C128A', $this->s_bar_x, $this->s_bar_y, $this->s_bar_w, $this->s_bar_h, 0.4, $this->_style, 'N');
//					
////					// レーンコード印字
////					$this->SetXY($this->s_lane_x, $this->s_lane_y);
////					$this->SetFontSize($this->s_lane_fs);
////					$this->MultiCell($this->s_lane_w, $this->s_lane_h, $LaneCd, $this->_debug, 'L', 0, 1);
//					
//					// 識別番号印字
//					$this->SetTextColor(255,255,255);
//					$this->SetXY($this->s_shiki_x, $this->s_shiki_y);
//					$this->SetFontSize($this->s_shiki_fs);
//					$this->MultiCell($this->s_shiki_w, $this->s_shiki_h, $SyukaShikibetsu, $this->_debug, 'C', 1, 1,'','',true,0,false,true,$this->s_shiki_h,'M');
//					$this->SetTextColor(0,0,0);

					$LaneCd =  $value[1][0]["lane_cd"];
					$SyukaShikibetsu =  $value[1][0]["syuka_shikibetsu"];
					//ロケの切り取り
					$locaHyoji = $value[1][0]["loca_hyoji"];
					$locaHyoji = substr($locaHyoji,0,1);
					$kiritoriSu = 1 + (int)$locaHyoji;
					Zend_Registry::get('log')->debug('kiritori:'.$kiritoriSu);
					$locaCD    = mb_substr($hyohjiLoca,$kiritoriSu,20,"UTF-8");
					Zend_Registry::get('log')->debug('kiritori1:'.$locaCD);
					// 新規にページを作成
					$this->AddPage("O", Array(115, 80));
					
					// ロケーションコード印字位置、フォントサイズをセット
					$this->SetXY($this->s_cd_x, $this->s_cd_y);
					$this->SetFontSize($this->s_cd_fs);
					// 印字
					$this->MultiCell($this->s_cd_w, $this->s_cd_h, $locaCD, $this->_debug, 'L', 0, 1);

					// ロケーションバーコード印字
					$this->write1DBarcode($BarcodeLoca, 'C128A', $this->s_bar_x, $this->s_bar_y, $this->s_bar_w, $this->s_bar_h, 0.4, $this->_style, 'N');
					
					// レーンコード印字
					$this->SetXY($this->s_lane_x, $this->s_lane_y);
					$this->SetFontSize($this->s_lane_fs);
					$this->MultiCell($this->s_lane_w, $this->s_lane_h, $LaneCd, $this->_debug, 'L', 0, 1);
					
					// 識別番号印字
					$this->SetTextColor(255,255,255);
					$this->SetXY($this->s_shiki_x, $this->s_shiki_y);
					$this->SetFontSize($this->s_shiki_fs);
					$this->MultiCell($this->s_shiki_w, $this->s_shiki_h, $SyukaShikibetsu, $this->_debug, 'C', 1, 1,'','',true,0,false,true,$this->s_shiki_h,'M');
					$this->SetTextColor(0,0,0);
					
				}
//				else{
//					Zend_Registry::get('log')->err('保管区分取得失敗:'.$e->getMessage()."\n".$e->getTraceAsString());
//					throw $e;
//				}
/*				if($prnKbn == "2")
				{
					// 新規にページを作成
					$this->AddPage("O", Array(95, 29));
					
					// 商品ラベル
					// ロケーションコード印字
					$this->SetXY($this->sl_cd_x, $this->sl_cd_y);
					$this->SetFontSize($this->sl_cd_fs);
					$this->MultiCell($this->sl_cd_w, $this->sl_cd_h, $childValue->loca_cd, $this->_debug, 'L', 0, 1);
					
					// ロケーションバーコード印字
					$this->write1DBarcode(str_replace('-', '', $childValue->loca_cd), 'C39', $this->sl_bar_x, $this->sl_bar_y, $this->sl_bar_w, $this->sl_bar_h, 0.4, $this->_style, 'N');

					// 自社バーコード印字
					$this->write1DBarcode($childValue->jisya_cd, 'C39', $this->sl_jbar_x, $this->sl_jbar_y, $this->sl_jbar_w, $this->sl_jbar_h, 0.4, $this->_style, 'N');
					  
					
					// 商品名一段目印字
					$this->SetXY($this->sl_snm1_x, $this->sl_snm1_y);
					$this->SetFontSize($this->sl_snm1_fs);
					$this->StartTransform(); 
					$this->Scale(90, 100); 
					$this->MultiCell($this->sl_snm1_w, $this->sl_snm1_h, mb_substr($childValue->syohin_nm,0,18,'utf-8'), $this->_debug, 'L', 0, 1);
					$this->StopTransform();
					
					// 商品名二段目印字
					$this->SetXY($this->sl_snm2_x, $this->sl_snm2_y);
					$this->SetFontSize($this->sl_snm2_fs);
					$this->StartTransform(); 
					$this->Scale(90, 100); 
					$this->MultiCell($this->sl_snm2_w, $this->sl_snm2_h, mb_substr($childValue->syohin_nm,18,18,'utf-8'), $this->_debug, 'L', 0, 1);
					$this->StopTransform();

					// 自社コード印字
					$this->SetXY($this->sl_jcd_x, $this->sl_jcd_y);
					$this->SetFontSize($this->sl_jcd_fs);
					$this->MultiCell($this->sl_jcd_w, $this->sl_jcd_h, $childValue->jisya_cd, $this->_debug, 'L', 0, 1);

					// 商品コード印字
					$this->SetXY($this->sl_scd_x, $this->sl_scd_y);
					$this->SetFontSize($this->sl_scd_fs);
					$this->MultiCell($this->sl_scd_w, $this->sl_scd_h, $childValue->syohin_cd, $this->_debug, 'L', 0, 1);

					// 商品JANコード印字
					if(strlen($childValue["syohin_cd"]) == 13){
						$this->write1DBarcode(substr($childValue->syohin_cd,0,12), 'EAN13', $this->sl_sjan_x, $this->sl_sjan_y, $this->sl_sjan_w, $this->sl_sjan_h, 0.4, $this->_style, 'N'); 	// JANバーコード(13桁)
					}else{
						$this->write1DBarcode(substr($childValue->syohin_cd,0,7), 'EAN8', $this->sl_sjan_x, $this->sl_sjan_y, $this->sl_sjan_w, $this->sl_sjan_h, 0.4, $this->_style, 'N'); 	// JANバーコード(8桁)	
					}
					// ケース入数印字
					//$this->SetXY($this->sl_isu_x, $this->sl_isu_y);
					//$this->SetFontSize($this->sl_isu_fs);
					//$this->MultiCell($this->sl_isu_w, $this->sl_isu_h, "(" . $childValue["case_irisu"] . ")", $this->_debug, 'L', 0, 1);
					
				}
*/
			}
			Zend_Registry::get('log')->debug('MTP170PR_1 CreatePdf end');

			$this->Output(Zend_Registry::get('config')->global->tmp->path.$fileName, 'F');
		} catch (Exception $e) {
			Zend_Registry::get('log')->err('印刷失敗:'.$e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		// URLもしくはファイル名を返す
		return 'http://'.$_SERVER['HTTP_HOST'].'/'.Zend_Registry::get('config')->global->tmp->name.'/'.$fileName;;

	}
	
	/**
	 * プリント情報検索時SQL
	 * @param $params	クライアントからの情報
	 * @return String	
	 */
	 private function getSearchSqlString($params) {
	 	
	 	// データベースへ接続
		$db = Zend_Registry::get('db');
	 	
	 	$sql = '';
	 	$where = '';
	 	$souko = Utility::sqlString($params->souko_cd);
		$location = Utility::sqlString(str_replace("-","",$params->loca_cd));

		//ロケーションラベル
		$sql = ' SELECT '
			.  '	 hokan_kbn'
			.  '	,hht_bara'
			.  ' FROM m_area '
		;
		$where.=' WHERE souko_cd = '.$souko
			.	' AND	area_cd	 = fnc_getAreaCd('.$souko.', '.$location.')'
		;
				// where条件
		$sql .= $where;
		
		// レーン
		$sql2 = ' SELECT lane_kbn FROM m_lane';
		$where2.=' WHERE souko_cd = '.$souko
			.	' AND	lane_cd	 = fnc_getLaneCd('.$souko.', '.$location.')'
		;
		$sql2 .= $where2;
		
		$sql = 'SELECT A.hokan_kbn, A.hht_bara, B.lane_kbn FROM ('.$sql.') A, ('.$sql2.') B';
		Zend_Registry::get('log')->debug('印刷時保管区分検索SQL文字列:'.$sql);
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
		$hokankbn = $value[0]['hokan_kbn'];
		$lanekbn = $value[0]['lane_kbn'];
			
		if($hokankbn == '00' && $lanekbn == '00'){
		//固定の場合
			$sql = 'SELECT	fnc_loca_hyoji(ML.souko_cd, ML.loca_cd) AS loca_cd'
				 . '		, fnc_locacode_kana_su(ML.loca_cd) AS BarcodeLoca'
				 . '		, ML.syohin_cd'
				 . '		, CONCAT(SUBSTR(MS.syohin_nm,1,34), \' \', SUBSTR(IFNULL(MS.kikaku,\'\'),1,10)) AS syohin_nm'
				 . '		, MS.case_irisu'
				 . '		, MS.hacyu_tani'
				 . '		, MS.maker_nm'
				 . '		, ML.kakunoh_case'
				 . '		, ML.syuka_shikibetsu'
				 . ' FROM	m_locakanri ML LEFT JOIN m_syohin MS ON ML.syohin_cd = MS.syohin_cd AND ML.jisya_cd = MS.jisya_cd'
				 . ' WHERE	ML.souko_cd = '.$souko
				 . ' AND	ML.loca_cd = '.$location;
		//}elseif($hokankbn == '01'){
		} else {
		//フリーの場合
			$sql = 'SELECT	fnc_loca_hyoji(ML.souko_cd, ML.loca_cd) AS loca_cd'
		 	  	 . '		, fnc_locacode_kana_su(ML.loca_cd) AS BarcodeLoca'
		 	  	 . '		, fnc_getLaneCd(ML.souko_cd, ML.loca_cd) AS lane_cd'	 
		    	 . '		, ML.syuka_shikibetsu'
		    	 . '		, MS.loca_hyoji'
				 . ' FROM	m_locakanri ML LEFT JOIN m_souko MS ON ML.souko_cd = MS.souko_cd'
				 . ' WHERE	ML.souko_cd = '.$souko
				 . ' AND	ML.loca_cd = '.$location;
		}	
		
	 	Zend_Registry::get('log')->debug('印刷時保管区分検索SQL2文字列:'.$sql);
 		try {
				//実行
				$value2 = $db->fetchAll($sql);
				if(count($value) < 1){
					return new retParameter('9', 'COM000001', NULL, NULL);
				}
				
		} catch (Exception $e) {
				Zend_Registry::get('log')->err($e->getMessage()."\n".$e->getTraceAsString());
				throw $e;
		}
		
		array_push($value, $value2);
		
		return $value; 	
	 	
	 }
}

