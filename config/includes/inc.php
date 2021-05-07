<?PHP

require_once("incDateTime.php");

function get404($resource = "requested")
{
    header('HTTP/1.0 404 Not Found');
    include("404.php");
}

function get403()
{
    header('HTTP/1.0 403 Forbidden');
    include("403.php");
}
