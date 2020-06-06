<?php
/****************************
システム名 販売・物流管理
サブシステム名 マスタ（PC)
業務名 ロケーションラベル印字処理
作成者
AIVS)秦　純一
更新履歴
2009.08.19 新規作成
2009.10.20 ロケバーコードには、ハイフンなしのロケコードをセットする
           商品名のサイズを40に変更
2009.12.09 ロケーション商品ラベルのフォント修正
*****************************/
require_once 'jp/co/acty_kitakanto/Pdf.php';

// バーコードサイズ
//define("VARCODE_WIDTH", 40);
//define("VARCODE_HIGHT", 20);

class MTP170PR extends Pdf
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
//	private $_dataWidth = 50;
	private $_dataWidth;

	// データフォント
//	private $_dataFont = 12;
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

	/**
	 * ヘッダ部設定処理
	 * (non-PHPdoc)
	 * @see tcpdf/TCPDF#Header()
	 */
	public function Header() {

		Zend_Registry::get('log')->debug('MTP170PR Header start');

		$this->SetFontSize(9);

	//	// 「作成」ラベル印字
	//	$this->SetXY(200, 5);
	//	$this->MultiCell(30, 0, "作成", 0, 'L', 0, 0);
	//
	//	// 印刷日時印字
	//	$this->SetX(145);
	//	$this->MultiCell(50, 0, $this->_dateTime, 0, 'R', 0, 0);

		Zend_Registry::get('log')->debug('MTP170PR Header end');
	}

	/**
	 * フッタ部設定処理
	 * (non-PHPdoc)
	 * @see tcpdf/TCPDF#Footer()
	 */
	public function Footer() {

		Zend_Registry::get('log')->debug('MTP170PR Footer start');

		$this->SetFontSize(9);

		// グループ単位で取得関数が異なる。
		if (empty($this->pagegroups)) {
			$this->_page = $this->l['w_page'].' '.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
		} else {
			$this->_page = $this->l['w_page'].' '.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
		}

		// 「ページ」ラベル印字
		$this->SetXY(72, 290);
		$this->MultiCell(30, 0, "ページ", 0, 'R', 0, 0);

		// ページ数印字
		$this->SetX(75);
		$this->MultiCell(50, 0, $this->_page, 0, 'R', 0, 0);

		Zend_Registry::get('log')->debug('MTP170PR Footer end');
	}

	/**
	 * ロケーションラベル印字処理
	 * @param $param
	 * @return unknown_type
	 */
	public function CreatePdf($params,$prnKbn){

		Zend_Registry::get('log')->debug('MTP170PR CreatePdf start');

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

			$this->AddPage();

			// 印刷区分別初期値設定
			if ($prnKbn == 1)
			{
				// ロケーション商品ラベル

				//バーコードサイズ
				$this->VARCODE_WIDTH = 70;
				$this->VARCODE_HIGHT = 15;

				// 印字開始位置の設定
				$this->SetY(10);
				$this->SetX(10);

				//データ縦サイズ
				$this->_dataHight = 0;

				//データ横サイズ
				$this->_dataWidth = 120;
			}
			else
			{
				// ロケーションラベル

				//バーコードサイズ
				$this->VARCODE_WIDTH = 37;
				$this->VARCODE_HIGHT = 15;

				// 印字開始位置の設定
				$this->SetY(10);
				$this->SetX(10);

				//データ縦サイズ
				$this->_dataHight = 40;

				//データ横サイズ
				$this->_dataWidth = 97;
			}

			// 初期値X
			$y = $this->GetY();
			// 初期値Y
			$x = $this->GetX();

			// 設定するX値
			$nextX = $x;
			$nextX2 = $x;
			// 設定するY値
			$nextY = $y;

			$this->SetAutoPageBreak(false,0);

			// 印刷可能位置(ページ縦幅 - 余白部 - フッタ部幅)
			$printHight = $this->getPageHeight() - $this->getBreakMargin() - $this->getFooterMargin();

			// 引数よりデータを取得する。
			$value = $params;

			$Once = 0;

			// 取得したデータ数分繰り返す。
			foreach($value as $childValue)
			{

				if ($Once != 0)
				{
					// 縦幅に追加できるかチェックする。
//					$logger->log($this->getPageHeight(), Zend_Log::DEBUG);
//					$logger->log($printHight, Zend_Log::DEBUG);
//					$logger->log($nextY + (VARCODE_HIGHT * $barcodeCount) + ($Adjustment * 2) +1, Zend_Log::DEBUG);
//					$logger->log('', Zend_Log::DEBUG);
					if(($printHight < ($nextY + ($this->VARCODE_HIGHT * $barcodeCount) + ($Adjustment * 2) +1)) ||
					   ($prnKbn == 1))
					{
						// 横幅に追加できるかチェックする。(現在位置から印字した後にもうひとつ印字できるか。)
						// ロケーション商品ラベルの場合は無条件で改ページ
						if (($this->getPageWidth() < ($nextX2 + (($this->_dataWidth + $this->VARCODE_WIDTH) * 2) + 1)) ||
						    ($prnKbn == 1))
						{
							// 追加できない場合、次ページへ印刷する。
							Zend_Registry::get('log')->debug('ページ追加');
							Zend_Registry::get('log')->debug('$this->getPageWidth()：'.$this->getPageWidth());
							Zend_Registry::get('log')->debug('$nextX：'.$nextX);
							Zend_Registry::get('log')->debug('$this->_dataWidth：'.$this->_dataWidth);
							$this->AddPage();
							// 印字開始位置を初期化する。
							$this->SetXY($x,$y);
							$nextX = $x;
							$nextX2 = $x;
							$nextY = $y;
						}
						else
						{
							Zend_Registry::get('log')->debug('************************');
							Zend_Registry::get('log')->debug('$this->getPageWidth()：'.$this->getPageWidth());
							Zend_Registry::get('log')->debug('$nextX：'.$nextX);
							Zend_Registry::get('log')->debug('$this->_dataWidth：'.$this->_dataWidth);
							// 横に印字できる為、開始位置を設定する。
							$this->SetXY($nextX + $this->_dataWidth + $this->VARCODE_WIDTH + 10, $y);
							$nextX2 = $nextX + $this->_dataWidth + $this->VARCODE_WIDTH + 10;
							$nextY = $y;
						}
					}
					else
					{
						$this->SetY($nextY + 1);
						$nextY = $nextY + 1;
					}
				}
				else
				{
					$Once = 1;
				}

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

				if ($prnKbn == 1)
				{
					// ロケーション商品ラベル

					$this->SetFontSize(30);
					// ロケーションコードを表示
					$this->MultiCell($this->_dataWidth, 0, $childValue["loca_cd"], $this->_debug, 'L', 0, 1);
					$this->SetFontSize($this->_dataFont);

					// 印字後の位置を設定
					//$nextX = $nextX + $this->VARCODE_WIDTH;
					$nextX = $nextX + 120;
					$this->SetX($nextX);

					// バーコード印字 //091020take
					$this->write1DBarcode(str_replace('/', '', $childValue["loca_bar"]), 'C39', $nextX, $nextY, $this->VARCODE_WIDTH, $this->VARCODE_HIGHT, 0.4, $this->_style, 'N');

					// 印字後の位置を設定
					$nextX = $nextX - $this->VARCODE_WIDTH;
					$this->SetX($nextX);
					$nextY = $nextY + $this->VARCODE_HIGHT;
					$this->SetY($nextY);

					$barcodeCount = 2;

					// 商品コードを表示
					$this->SetFontSize(15);
					$this->MultiCell($this->_dataWidth, 0, 'JANコード', $this->_debug, 'L', 0, 1);
					//$this->SetFontSize($this->_dataFont);
					$this->SetY($nextY + 5);
					$this->SetFontSize(45);
					$this->MultiCell($this->_dataWidth, 0, $childValue["syohin_cd"], $this->_debug, 'L', 0, 1);

					// 印字後の位置を設定
					$nextX = $nextX + $this->VARCODE_WIDTH;
					$this->SetX($nextX);

					Zend_Registry::get('log')->debug('出力バーコード情報：'.$childValue["syohin_cd"]);
					$this->write1DBarcode($childValue["syohin_cd"], 'C39', $nextX, $nextY, $this->VARCODE_WIDTH, $this->VARCODE_HIGHT + 3, 0.4, $this->_style, 'N');

					// 印字後の位置を設定
					$nextX = $nextX - $this->VARCODE_WIDTH;
					$this->SetX($nextX);
					$nextY = $nextY + $this->VARCODE_HIGHT + 5;
					$this->SetY($nextY);

					// 自社コードを表示
					$this->SetFontSize(15);
					$this->MultiCell($this->_dataWidth, 0, '自社コード', $this->_debug, 'L', 0, 1);
					//$this->SetFontSize($this->_dataFont);
					$this->SetY($nextY + 5);
					$this->SetFontSize(25);
					$this->MultiCell($this->_dataWidth, 0, $childValue["jisya_cd"], $this->_debug, 'L', 0, 1);

					// 印字後の位置を設定
					$nextY = $nextY + $this->VARCODE_HIGHT;
					$this->SetY($nextY);

					// 商品名を表示
					$this->SetFontSize(50);
					$this->MultiCell(($this->_dataWidth + 70), 0, $childValue["syohin_nm"], $this->_debug, 'L', 0, 1);
					$Adjustment = $this->getY() - $nextY;
					$nextX = $this->getX();
					$nextY = $this->getY();
				}
				else
				{
					// ロケーションラベル

					if ($nextX != $nextX2)
					{
						$nextX = $nextX2;
						$this->SetX($nextX);
					}

					// ロケーションコードを表示
					$this->SetFontSize(50);
					$this->MultiCell($this->_dataWidth, $this->_dataHight, $childValue["loca_cd"], $this->_debug, 'L', 0, 1);

					// 印字後の位置を設定
					$nextX = $nextX + $this->_dataWidth;
					$this->SetX($nextX);

					// バーコード印字 //091020take
					$nextY = $nextY + 5;
					$this->write1DBarcode(str_replace('/', '', $childValue["loca_bar"]), 'C39', $nextX, $nextY, $this->VARCODE_WIDTH, $this->VARCODE_HIGHT, 0.4, $this->_style, 'N');
					$nextY = $nextY - 5;

					// 印字後の位置を設定
					$nextX = $this->GetX();
					$nextY = $nextY + $this->_dataHight;
				}

			}
			Zend_Registry::get('log')->debug('MTP170PR CreatePdf end');

			$this->Output(Zend_Registry::get('config')->global->tmp->path.$fileName, 'F');
		} catch (Exception $e) {
			Zend_Registry::get('log')->err('印刷失敗:'.$e->getMessage()."\n".$e->getTraceAsString());
			throw $e;
		}

		// URLもしくはファイル名を返す
		return 'http://'.$_SERVER['HTTP_HOST'].'/'.Zend_Registry::get('config')->global->tmp->name.'/'.$fileName;;

	}
}

