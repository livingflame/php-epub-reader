<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" type="text/css" href="assets/css/style.css" />
<title>Redirection alert</title>
</head>
<body>
    <h1>Redirection alert</h1>
    <p>You are about to leave the epub reader</p>
    <p>Click on the link below if you are certain.</p>
    <p><a href="<?php echo $url; ?>?extok=true&ext=<?php echo $ext; ?>"><?php echo htmlspecialchars($ext); ?></a></p>
</body>
</html>