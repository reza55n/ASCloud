<?PHP

function getTimeSpan($time = 0)
{
    if (empty($time))
        $time = time();
        
    return '<span class="unixtime" style="display:none">' . $time . '</span>';
}

function generateTimeScript($genTag = true, $genDefinition = true, $genCall = true)
{
    if ($genTag)
        { ?><script><?PHP }
    
    if ($genDefinition)
    { ?>
    function generateTimeScript()
    {
        var elements = document.getElementsByClassName("unixtime");
        for(var i = 0; i < elements.length; i++) {
            t1 = Number(elements[i].innerHTML);
            
            var date = new Date(t1 * 1000); // Convert timestamp to milliseconds
    
            var year = date.getFullYear();
            var month = "0" + (date.getMonth() + 1); //It's from 0 to 11
            var day = "0" + date.getDate();
            var hours = "0" + date.getHours();
            var minutes = "0" + date.getMinutes();
            var seconds = "0" + date.getSeconds();
            
            var convdataTime = year + '-' + month.substr(-2) + '-' + day.substr(-2) + ' ' + hours.substr(-2) + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
            elements[i].innerHTML = convdataTime;
            elements[i].style = "";
        }
        while(elements.length > 0) {
            elements[0].classList.remove('unixtime');
        }
    }
    <?PHP }
    
    if ($genCall)
        echo 'generateTimeScript();';
    
    if ($genTag)
        { ?></script><?PHP }
}
