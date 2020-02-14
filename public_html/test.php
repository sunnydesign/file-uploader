<?php echo DB_DRIVER ?>
<html>
<head>
    <title>File Upload</title>
</head>
<body>
<h1>File Upload</h1>
    <form method="POST" action="http://172.17.0.2:8000/cea13dab5163126ab721a994aa63c4c6ffa477214e7408de6828fee723933a11" enctype="multipart/form-data">
        <label for="inputfile">Upload File</label>
        <input type="file" id="inputfile" name="inputfile">
        </br>
        <input type="submit" value="Click To Upload">
    </form>
</body>
</html>