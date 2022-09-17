<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>2FA</title>
<style>
body{font-family:arial;background:#FFFFFF;text-align:center;}
#error{margin:1em auto;background:#FA4956;color:#FFFFFF;border:8px solid #FA4956;font-weight:bold;width:500px;text-align:center;position:relative;}
#entry{margin:2em auto;background:#fff;border:8px solid #eee;width:500px;text-align:left;position:relative;}
#entry a, #entry a:visited{color:#0283b2;}
#entry a:hover{color:#111;}
#entry h1{text-align:center;background:#8C1974;color:#fff;font-size:16px;padding:16px 25px;margin:0 0 1.5em 0;border-bottom:1px solid #007dab;}
#entry p{text-align:center;}
#entry div{margin:.5em 25px;background:#eee;padding:4px;text-align:right;position:relative;}
#entry label{float:left;line-height:30px;padding-left:10px;}
#entry .field{border:1px solid #ccc;width:280px;font-size:12px;line-height:1em;padding:4px;}
#entry div.submit{background:none;margin:1em 25px;text-align:center;}
#entry div.submit label{float:none;display:inline;font-size:11px;}
#entry button{border:0;padding:0 30px;height:30px;line-height:30px;text-align:center;font-size:16px;font-weight:bold;color:#fff;background:#8C1974;cursor:pointer;}
</style>
</head>
<body>

<!-- Place your logo here -->
        <P><IMG SRC="<?php echo $cfgProgDir ?>../images/logoKoningsKerk.png" height='200' ALT="phpSecurePages logo"></P>
<!-- Place your logo here -->

<?php
// check for error messages
if ($phpSP_message) {
	echo '<div id="error">'.$phpSP_message.'</div>';
}
?>


<form id="entry" action="<?php echo $_SERVER['REQUEST_URI'] ?>" METHOD="post" onSubmit="return checkData()">

<?php //if there are $_POST variables -- add them to the form...
   $pname=""; $pvalue="";
   foreach ($_POST as $pname => $pval) {
        if ($pname="entered_login" OR $pname="entered_password") continue;
        echo '<input type=hidden name="'.$pname.'" value="'.$pval.'">'."\n";
        }
?>
    <h1><?php echo $strLoginInterface; ?></h1>    
    <div>
            <label for="login_password">2FA-code</label>
            <input type="text" name="entered_2FA" class="field required" autofocus />
    </div>
    <div class="submit">
        <button type="submit">Ga door</button>


    </form>
    </div>


</body>
</html>
