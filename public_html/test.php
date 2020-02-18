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
    <form method="POST" action="<?= $api_url ?>/dfb3a0ca53084eded993002903d6c1c602405ba50a130a8ba6f9c3dd291a0ec9" enctype="multipart/form-data">
        <label for="inputfile">Upload File</label>
        <input type="file" id="inputfile" name="inputfile">
        </br>
        <input type="submit" value="Click To Upload">
    </form>
</body>
</html>