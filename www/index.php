<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<!-- HTML gratuitously stolen from Shiflett -->
<head>
  <title>Make Entities</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <style type="text/css" media="screen">
  body {
    margin: 2em;
    padding: 0;
    border: 0;
    font: 1em verdana, helvetica, sans-serif;
    color: #000;
    background: #fff;
  } 
  ol.code {
    width: 90%;
    margin: 0 5%;
    padding: 0;
    font-size: 0.75em;
    line-height: 1.8em;
    overflow: hidden;
    color: #939399;
    text-align: left;
    list-style-position: inside;
    border: 1px solid #d3d3d0;
  }
  ol.code li {
    float: left;
    clear: both;
    width: 99%;
    white-space: nowrap;
    margin: 0;
    padding: 0 0 0 1%;
    background: #fff;
  }
  ol.code li.even { background: #f3f3f0; }
  ol.code li code {
    font: 1.2em courier, monospace;
    color: #c30;
    white-space: pre;
    padding-left: 0.5em;
  }
  .code .comment { color: #939399; }
  .code .default { color: #44c; }
  .code .keyword { color: #373; }
  .code .string { color: #c30; }
  </style>
</head>

<body>
<form method="post">
<textarea cols="80" rows="20" name="input"><?php
if (isset($_POST['input'])) {
    echo htmlentities($_POST['input'], ENT_QUOTES, 'utf-8');
} ?></textarea><br />

<input type="submit" value="Entity" />
</form>
<?php
if (isset($_POST['input'])) {
    ?>
<br />
<textarea cols="80" rows="20"><?php
$dir = dirname(__FILE__);
require "$dir/../inc/Lexer.php";
require "$dir/../inc/Tokens.php";
echo htmlentities(LexEntity\Token\Set::getInstance(new LexEntity\Lexer($_POST['input'])), ENT_QUOTES, 'utf-8');
?></textarea>
<?php
}
?>
</body>
</html>