<?php
$api_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
echo $api_url;
?>
<html>
<head>
    <title>File Upload</title>
</head>
<body>
<h1>File Upload</h1>
    <form method="POST" action="<?= $api_url ?>/0b24e362028471c7f6d3dfad7ee9f17ccafb6e6ba9f44a9a822cc6a6abd1a918" enctype="multipart/form-data">
        <label for="inputfile">Upload File</label>
        <input type="file" id="inputfile" name="inputfile">
        </br>
        <input type="submit" value="Click To Upload">
    </form>
</body>
</html>