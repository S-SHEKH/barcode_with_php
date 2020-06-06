<html>
<head>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<style>
p.inline {display: inline-block;}
span { font-size: 13px;}
</style>
<style type="text/css" media="print">
    @page
    {
        size: auto;   /* auto is the initial value */
        margin: 0mm;  /* this affects the margin in the printer settings */

    }
</style>
</head>
<body onload="window.print();">
	<div style="margin-left: 5%;margin-top:30px">
		<?php

    include 'barcode128.php';

    $syohin_nm=$_POST['syohin_nm'];
    $loca_cd=$_POST['loca_cd'];
    $bar_code=$_POST['bar_code'];


	 $case_irisu = $_POST['case_irisu'];
    $hacyu_tani=$_POST['hacyu_tani'];
    $boll_irisu=$_POST['$boll_irisu'];


		for($i=1;$i<=$_POST['p1'];$i++){
			//echo "<p class='inline'><span ><b>$product_name</b></span>".bar128(stripcslashes($_POST['locabcode']))." ".bar128(stripcslashes($_POST['location']))." ".bar128(stripcslashes($_POST['quantity_t']))";


    ?>
    <table>
         <tr>
            <td><?php 
            echo "$syohin_nm"."<br/>"."<b>$loca_cd</b>"."<br> ".bar128(stripcslashes($_POST['bar_code']));
            ?>
            </td>
           
            <td><?php echo "入数: ".$case_irisu."<br>"."発単: ".$hacyu_tani." <br />ボール入数:".$boll_irisu; ?></td>
          </tr>
    </table>
            
   
   

	</div>
</body>
</html>
