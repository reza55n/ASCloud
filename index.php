<?PHP
require('config/includes/inc.php');

$LTRMark = '‎'; //For RTL languages

$ascloudVersion = '0.2';
$minLangVersion = 2;

$ascloudSession = 'ascloud_ses';
$ascloudSessionPass = 'ascloud_pass';


if (file_exists('user.php'))
{
	require('user.php');
	loadLanguage();
}
else
{
	$language = isset($_POST['chooseLang']) ? $_POST['chooseLang'] : 'en';
	$theme = isset($_POST['chooseTheme']) ? $_POST['chooseTheme'] : 'default';
	loadLanguage();

	if (!isset($_POST['pass1']))
		$_POST['pass1'] = ''; //Prevent error when filling value

	else if (empty($_POST['pass1']))
		$errMsg = $_l_['reg_empty_pass'];
	
	else if (!isset($_POST['pass2']) || $_POST['pass2'] !== $_POST['pass1'])
		$errMsg = $_l_['reg_unmatch'];
	
	else //Success
	{
		$buffer = "<?PHP \$language = '$language'; \$theme = '$theme'; \$pass = '"
				. hash('md5', $_POST['pass1']) . "'; ?>";
		file_put_contents('user.php', $buffer);
		header('Location:.');
		die();
	}

	$langs  = glob("config/langs/*.json");
	$themes = glob("config/themes/*.css");

    generateHeads();

	echo "";

    ?><form action="." method="post" class="frm">
		<?PHP
		echo '<h3>' . sprintf($_l_['welcome'], $ascloudVersion) . '</h3>';
		if (isset($errMsg))
			echo "<div class='form warning'>$errMsg</div>";
			?>
		<div class="form">
		  <label for="chooseLang" style="vertical-align:top"><?=$_l_['reg_choose_lang']?>:</label>
		  <select id="chooseLang" name="chooseLang" size="4" style="width:100%" required>
			<?PHP
			foreach($langs as $langFor)
			{
				$langDecoded = json_decode(file_get_contents($langFor), true);
				
				if ($langDecoded['version'] >= $minLangVersion)
				{
					$langName = $langDecoded['name'];
					$langShort = substr($langFor, 13, strlen($langFor) - 18);
					echo '<option value="' . $langShort . '"' .
						($language == $langShort ? ' selected' : '') . ">$langName</option>";
				}
			}
			?>
		  </select>
		</div>

		<div class="form">
		  <label for="chooseTheme" style="vertical-align:top"><?=$_l_['reg_choose_theme']?>:</label>
		  <select id="chooseTheme" name="chooseTheme" size="3" style="width:100%" required>
			<?PHP
			foreach($themes as $themeFor)
			{
				$themeShort = substr($themeFor, 14, strlen($themeFor) - 18);
				echo '<option value="' . $themeShort . '"' . ($theme == $themeShort ? ' selected' : '') . ">$themeShort</option>";
			}
			?>
		  </select>
		</div>
		
		<div class="form"><?=$_l_['reg_pass']?>:
        <input type="password" value="<?=$_POST['pass1']?>" id="pass1" name="pass1" style="width:100%" required></div>
		
		<div class="form"><?=$_l_['reg_pass_reenter']?>:
		<input type="password" value="" id="pass2" name="pass2" style="width:100%" autocomplete="off" required></div>
		
        <div class="form" style="text-align:<?= $langJson['dir'] == 'rtl' ? 'left' : 'right' ?>">
			<input type="submit" value="<?=$_l_['reg_register']?>"></div>
    </form><?PHP
    generateTails();
    die();
}


$parentDirClient = dirname($_SERVER['PHP_SELF']);

//Preview path is used both for direct preview (some formats) and storage.
//Download path is used only for downloading, since host doesn't make some formats (JPG, PDF, PNG, etc.) available for download.
//Put slash after none of these:
$filesPreviewServerPath = 'view'; //Can be relative or absolute; server-side
$filesPreviewClientPath = $parentDirClient . '/view'; //Only absolute starting with slash; client-side
$filesDownloadClientPath = $parentDirClient . '/dl'; //Only absolute starting with slash; client-side

$serverName = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$filesDownloadClientPathFull = $serverName . $filesDownloadClientPath;

session_set_cookie_params(31536000, $parentDirClient);
ini_set("session.gc_maxlifetime", 31536000); //60*60*24*365
ini_set("session.name", $ascloudSession);
ini_set('session.save_path', getcwd() . '/config/sessions'); //Prevent from resetting sessions in shared hosts
session_start();

if (isset($_POST['password']))
{
    $_SESSION[$ascloudSessionPass] = hash('md5', $_POST['password']);

    $params = session_get_cookie_params();
    if (isset($_POST['remember']))
        $cookieTime = time() + 31536000;
    else
        $cookieTime = 0; //Session cookie until closing the browser
    
    setcookie(session_name(), session_id(), $cookieTime,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]);

}
else if (isset($_GET['out']) && $_GET['out']=='1')
{
    unset($_SESSION[$ascloudSessionPass]);
    header('Location:.');
    die();
}

if (!isset($_SESSION[$ascloudSessionPass]) || $_SESSION[$ascloudSessionPass] != $pass)
{
    generateHeads();
    ?><form action="." method="post" class="frm">
		<div class="form">
        <input type="password" value="" id="password" name="password">
        <input type="submit" value="<?=$_l_['login']?>">
		</div>
		<div class="form">
        <input type="checkbox" id="remember" name="remember" value="remember">
        <label for="remember"><?=$_l_['remember']?></label>
		</div>
    </form><?PHP
    generateTails();
    die();
}

//######################## User logged in ########################


if (isset($_GET['reset']) && $_GET['reset']=='1')
{
    unlink('user.php');
    header('Location:.');
    die();
}

if (isset($_GET['del']) && !empty($_GET['del']))
{
    $txtPath = "texts/{$_GET['del']}.txt";

    if(file_exists($txtPath))
    {
        $content = file_get_contents($txtPath);
        $content2 = textSeparate($content);
        if (is_array($content2))
        {
            if (file_exists("$filesPreviewServerPath/{$content2[0]}") && unlink("$filesPreviewServerPath/{$content2[0]}"))
            {
                unlink($txtPath);
                header('Location:?offset=' . $_GET['offset']);
                die();
            }
            else
                $errMsg = $_l_['delete_attach_err'];
        }
        else
        {
            unlink($txtPath);
            header('Location:?offset=' . $_GET['offset']);
            die();
        }
    }
    else
        $errMsg = $_l_['msg_not_found'];
}

if (isset($_POST['notetext']))
{
    $txtt = '';
    
    if (isset($_FILES['file1']) && $_FILES['file1']['error'] == 0)
    {
        $fname = $_FILES['file1']['name'];
		
        if (strtolower(substr($fname, -4)) == '.php' || strtolower($fname) == '.htaccess')
            $fname .= '_';
        
		$fname = str_replace('#', '_', $fname);
		$fname = str_replace('%', '_', $fname);
		$fname = str_replace('?', '_', $fname);
		$fname = str_replace('&', '_', $fname);
		
        if (file_exists("$filesPreviewServerPath/$fname"))
        {
            $it = 2;
            $fname_parts = mb_pathinfo($fname);
            
            if (!empty($fname_parts['extension']))
                $fname_parts['extension'] = '.' . $fname_parts['extension'];
                
            while(file_exists("$filesPreviewServerPath/{$fname_parts['filename']}_$it{$fname_parts['extension']}"))
                $it++;
            
            $fname = "{$fname_parts['filename']}_$it{$fname_parts['extension']}";
        }

        if (move_uploaded_file($_FILES["file1"]["tmp_name"], "$filesPreviewServerPath/$fname"))
            $txtt = '<file>'.$fname.'</file>';
    }
    else if (isset($_FILES['file1']['error']) && $_FILES['file1']['error'] > 0)
        $errMsg = sprintf($_l_['upload_err_code'], $_FILES['file1']['error']);
        
    $txtt .= htmlentities($_POST['notetext']);
    
    if (!empty($txtt))
        file_put_contents('texts/' . time() . "_" . bin2hex(random_bytes(6)) . '.txt', $txtt);
}

generateHeads();

echo '<div class="frm"><a href="?out=1">' . $_l_['logout'] .
			'</a> | <a href="javascript:void(0)" onclick="if(confirm(' .
			createJSQuotedString($_l_['reset_settings_confirm']) . ")) window.location = '?reset=1';\">" .
			$_l_['reset_settings'] . '</a></div>';
$files = glob("texts/*.txt");

$pAttribs = 'dir="auto"';

$viewableExts = array('png','jpg','jpeg','gif','svg','ico',
						'pdf','json','yml','yaml','txt','md','htm','html','xml','css','js','ini','log',
						'mp3','wav','amr','wma','m4a',
						'mpg','avi','wmv','mkv','mp4','3gp','m4v');

foreach($files as $file) {
    $content = file_get_contents($file);
    $fileSpan = basename($file, '.txt');

    echo '<div class="textbox" id="' . $fileSpan . '">';

    $content2 = textSeparate($content);
    if (is_array($content2))
    {
        $pInfo = mb_pathinfo($content2[0]);
        
        echo "<div dir=\"ltr\"" . ($langJson['dir'] == 'ltr' ? ' style="text-align:right;"' : '') . " class=\"muted header\">{$_l_['pm_attachment']}: $LTRMark<a href=\"$filesDownloadClientPath/" .
					$content2[0] . '">' .
					((mb_strlen($content2[0]) > 20) ? (mb_substr($pInfo['filename'], 0, 17) . ' ... ' .
					(empty($pInfo['extension']) ? '' : '.' . $pInfo['extension']))
										: $content2[0]) . "</a>";
        
        if (file_exists("$filesPreviewServerPath/{$content2[0]}"))
        {
            echo $LTRMark . ' (' . formatBytes(filesize("$filesPreviewServerPath/{$content2[0]}")) . ')';
            
            if (in_array($pInfo['extension'], $viewableExts))
            {
                echo " | <a href=\"$filesPreviewClientPath/{$content2[0]}\" target=\"_blank\">{$_l_['pm_preview']}</a>";
            }
            
            echo $LTRMark . ' | <input type="text" id="link' . $fileSpan . '" class="linkinput" value="'.
                str_replace(' ', '%20', $filesDownloadClientPathFull . '/' . $content2[0]) . '" readonly>';
            echo ' <a href="javascript:void(0)" onclick="copyInput(\'link' . $fileSpan . '\')">' . $_l_['pm_copy'] . '</a>';
        }
        else
        {
            echo "$LTRMark ({$_l_['pm_not_found']})";
        }
        
        echo '</div>';
        $content = $content2[1];
    }

	echo '<div id="' . $fileSpan . '_text">';
	
	$content = str_replace("\r\n", "\n", $content);
	$content =   str_replace("\r", "\n", $content);
	$content_separated = explode("\n", $content);
	
	foreach ($content_separated as $paragraph)
	{
		if (empty($paragraph)) //To include empty paragraphs
			$paragraph = '&nbsp;';
		echo "<p $pAttribs>$paragraph</p>";
	}
	
	echo '</div>';

?><div class="muted footer"><?=$LTRMark . getTimeSpan(substr($fileSpan, 0, strpos($fileSpan,"_")))?>‏ - 
<a href="javascript:void(0)" onclick="copyText('<?=$fileSpan?>_text')"><?=$_l_['pm_copy']?></a> | 
<a href="javascript:void(0)" 
    onclick="confirmDelete('<?=$fileSpan?>',<?=is_array($content2) ? 'true' : 'false'?>)"><?=$_l_['pm_delete']?></a></div>
<?PHP
    echo '</div>';
    
}

generateTimeScript();
$antiFirefoxFill = bin2hex(random_bytes(8));
?>
    <div id="allfooters" style="text-align:<?= $langJson['dir'] == 'rtl' ? 'left' : 'right' ?>">
    <textarea id="notetext" class="notetext" name="<?=$antiFirefoxFill?>"></textarea>
    <div class="notefooter"><div id="progressf1" class="progressok"></div></div>
    <div class="notefooter">
        <input id="file1" type="file" style="display:none" name="<?=$antiFirefoxFill?>">
        <span id="progressf1label"><?=isset($errMsg) && !empty($errMsg)?
            "<span class=\"warning\">$errMsg</span>":''?></span>
        
        <button type="button" class="button" id="deletebutton" style="display:none"
            onclick='hideDl();'><?=$_l_['panel_attachment_delete']?></button>

        <button type="button" class="button" id="uploadbutton"
            onclick='showDl();'><?=$_l_['panel_attachment']?></button>
        		
        <button type="button" class="button" id="abortbutton" style="display:none"
            onclick="abortPost();"><?=$_l_['panel_abort']?></button>
            
        <button type="button" class="button" id="submitbutton"
            onclick="postFile()"><?=$_l_['panel_send']?></button>
    </div>
    </div>
    <script>
        var request = new XMLHttpRequest();

		function confirmDelete(fileSpan, hasAttachment)
		{
			if(confirm(hasAttachment?<?=createJSQuotedString($_l_['confirm_del_attach'])?>:<?=createJSQuotedString($_l_['confirm_del'])?>))
			{
				window.location = '?del=' + fileSpan + '&offset=' + window.pageYOffset;
			}
		}

		function bytesToSize(bytes) {
		   var sizes = ['bytes', 'KB', 'MB', 'GB', 'TB'];
		   if (bytes == 0) return '0 Byte';
		   var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
		   return (Math.round(bytes * 100 / Math.pow(1024, i)) / 100) + ' ' + sizes[i];
		}

		function copyText(id)
		{
		 var r = document.createRange();
		 r.selectNode(document.getElementById(id));
		 window.getSelection().removeAllRanges();
		 window.getSelection().addRange(r);
		 document.execCommand('copy');
		 window.getSelection().removeAllRanges();
		}

        function copyInput(idd) {
          /* Get the text field */
          var copyText = document.getElementById(idd);
        
          /* Select the text field */
          copyText.select();
          copyText.setSelectionRange(0, 99999); /*For mobile devices*/
        
          /* Copy the text inside the text field */
          document.execCommand("copy");
        }
        function hideDl() {
            $("#file1").val("")
            $('#file1').css('display', 'none');
            $('#deletebutton').css('display', 'none');
            $('#uploadbutton').css('display', 'inline-block');
        }
        function showDl(click = true) {
            $('#file1').css('display', 'inline-block');
            $('#deletebutton').css('display', 'inline-block');
            $('#uploadbutton').css('display', 'none');
			if (click)
				$('#file1').click();
        }
        function abortPost(err = ''){
            request.abort();
            
            if (err)
            {
                $('#progressf1').addClass('progressfail');
				$('#progressf1').removeClass('progressok');
                $('#progressf1').width('100%');
                $('#progressf1label').html(err);
            }
            else
            {
                $('#progressf1label').html('');
                $('#progressf1').width('0%');
            }
            $('#abortbutton').css('display', 'none');
            $('#submitbutton').css('display', 'inline-block');
            if($('#file1')[0].files.length < 1)
            {
                $('#uploadbutton').css('display', 'inline-block');
                $('#file1').css('display', 'none');
                $('#deletebutton').css('display', 'none');
            }
            else
            {
                $('#uploadbutton').css('display', 'none');
                $('#file1').css('display', 'inline-block');
                $('#deletebutton').css('display', 'inline-block');
            }
        }
        function postFile() {
            if ($('#file1')[0].files.length < 1 && $("#notetext").val() == '')
            {
                alert(<?=createJSQuotedString($_l_['upload_add_text_or_att'])?>);
                return;
            }

            if ($('#file1')[0].files.length > 1)
            {
                alert(<?=createJSQuotedString($_l_['upload_only_1_file'])?>);
                return;
            }
            
            $('#progressf1label').html(<?=createJSQuotedString($_l_['upload_sending'])?>);
            
        	request = new XMLHttpRequest();
        	request.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
				//Refresh page
                document.open();
                document.write(this.responseText);
                document.close();
            }
        	};
        
            var formdata = new FormData();
            formdata.append('notetext', $("#notetext").val());

            if($('#file1')[0].files.length > 0)
            {
                var file1Size = $('#file1')[0].files[0].size;
                
                var maxSize = <?PHP $maxSize = parse_size(ini_get('upload_max_filesize')); echo $maxSize;?>;
                
                if (file1Size>maxSize)
                {
                    alert(<?=createJSQuotedString(sprintf($_l_['upload_err_max_limit'], $LTRMark . formatBytes($maxSize)));?>);
                    $('#progressf1label').html('');
                    return;
                }
                $('#progressf1').addClass('progressok');
				$('#progressf1').removeClass('progressfail');
                $('#progressf1').width('0%');
                
                formdata.append('file1', $('#file1')[0].files[0]);
    
                request.onerror = function (e) {
                    abortPost(<?=createJSQuotedString($_l_['upload_onerror'])?>);
                };
                
                request.ontimeout = function (e) {
                    abortPost(<?=createJSQuotedString($_l_['upload_timeout'])?>);
                };
                
                request.upload.addEventListener('progress', function (e) {
            
                    var percent=0;
                    
                    if (e.loaded <= file1Size) {
                        percent = Math.round(e.loaded / file1Size * 100);
                    } 
                    if(e.loaded == e.total){
                        percent = 100;
                    }
                    $('#progressf1label').html(percent + '% (<?=$LTRMark?>' + bytesToSize(e.loaded) + ')');
                    $('#progressf1').width(percent + '%');
                });
            }

            request.open('post', 'index.php');
            $('#file1').css('display', 'none');
            $('#submitbutton').css('display', 'none');
            $('#deletebutton').css('display', 'none');
            $('#abortbutton').css('display', 'inline-block');
            request.send(formdata);
        }
        
        $('#notetext').bind('input propertychange', function() {
              if(this.value.length && $('#notetext').innerHeight() < 130){
                $('#notetext').innerHeight('130px');
              }
              else if(this.value.length == 0)
              {
                $('#notetext').innerHeight('45px');
              }
        });
        
    	var dropZone = document.getElementById('allfooters');
    
    	// Optional.   Show the copy icon when dragging over.  Seems to only work for chrome.
    	dropZone.addEventListener('dragover', function(e) {
    		e.stopPropagation();
    		e.preventDefault();
    		e.dataTransfer.dropEffect = 'copy';
    	});
    
    	// Get file data on drop
    	dropZone.addEventListener('drop', function(e) {
    		e.stopPropagation();
    		e.preventDefault();
    		showDl(false);
    		files = e.dataTransfer.files;
    		if (files.length > 1)
    		{
    		    alert(<?=createJSQuotedString($_l_['upload_only_1_file'])?>);
    		}
    		else
    		    $('#file1')[0].files = files;
    	});
        
        window.scrollTo(0, <?=isset($_GET['offset'])?$_GET['offset']:'document.body.scrollHeight'?>);

		document.addEventListener('copy', (event) => {
			var srcData = document.getSelection().toString();
			if (srcData == "") //Empty or when an input is selected
			    return;
			    
			var data = srcData.replace(/[\r\n]{2,}/g, "\n");
			event.clipboardData.setData('text', data);
			event.preventDefault();
		});
    </script>
<?PHP

generateTails();


// From https://api.drupal.org/api/drupal/includes%21common.inc/7.x
function parse_size($size) {
  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
  $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
  if ($unit) {
    // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
  }
  else {
    return round($size);
  }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('bytes', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

//return array: has file, string: no files
function textSeparate($content)
{
    if (substr($content, 0, 6) == '<file>')
    {
        $p2 = strpos($content, '</file>');
        if ($p2 !== false)
        {
            $fname = substr($content, 6, $p2 - 6);
            $content = substr($content, $p2 + 7);
            return array($fname, $content);
        }
    }
    return $content;
}

// By Pietro Baricco from https://www.php.net/manual/en/function.pathinfo.php#107461 with some changes:
function mb_pathinfo($filepath) {
    preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $filepath, $m);
    $ret['dirname'] =   isset($m[1]) ? $m[1] : null;
    $ret['basename'] =  isset($m[2]) ? $m[2] : null;
    $ret['extension'] = isset($m[5]) ? $m[5] : null;
    $ret['filename'] =  isset($m[3]) ? $m[3] : null;
    return $ret;
}

function createJSQuotedString($string, $double = false)
{
	if ($double)
	{
		$string = str_replace('"', '\"', $string);
		return '"' . $string . '"';
	}
	else
	{
		$string = str_replace("'", "\'", $string);
		return "'$string'";
	}
}

function loadLanguage()
{
	global $language;
	global $langJson;
	global $_l_;
	global $minLangVersion;
	
	$langJson = json_decode(file_get_contents("config/langs/$language.json"), true);
	
	$_l_ = $langJson['values'];
	
	if (($langJson['version'] < $minLangVersion))
		echo "Warning: outdated language version ({$langJson['version']}). " .
			"Please select another language with version at least {$minLangVersion}.";
}

function generateTails(){
    echo '</body></html>';
}
function generateHeads(){
	global $theme;
	global $langJson;
	global $_l_;
?><!DOCTYPE html>

<head>
    <title><?=$_l_['app_name']?></title>    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="config/favicon.ico">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.js"></script>
	<link rel="stylesheet" type="text/css" href="config/themes/<?=$theme?>.css">
</head>

<body dir="<?=$langJson['dir']?>">
<?PHP }