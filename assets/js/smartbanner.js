function getOS() {
    var userAgent = window.navigator.userAgent,
        platform = window.navigator.platform,
        macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'],
        windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
        iosPlatforms = ['iPhone', 'iPad', 'iPod'],
        os = null;

    if (macosPlatforms.indexOf(platform) !== -1) {
    os = 'Mac OS';
    } else if (iosPlatforms.indexOf(platform) !== -1) {
    os = 'iOS';
    } else if (windowsPlatforms.indexOf(platform) !== -1) {
    os = 'Windows';
    } else if (/Android/.test(userAgent)) {
    os = 'Android';
    } else if (!os && /Linux/.test(platform)) {
    os = 'Linux';
    }

    return os;
}

var currentos = getOS();
if(currentos == 'Android' || currentos == 'iOS') {
    document.addEventListener("DOMContentLoaded", function() {
        
        document.getElementById("smartbannerdiv").style.display = "block"; 
        var element = document.getElementById("smartbannerdiv");
        element.classList.toggle("open");
        
        if(currentos == 'iOS') {
            document.getElementById("iosbutton").style.display = "block"; 
            document.getElementById("androidbutton").style.display = "none"; 
        }else if(currentos == 'Android') {
            document.getElementById("androidbutton").style.display = "block"; 
            document.getElementById("iosbutton").style.display = "none"; 
        }
        
    });
}

function hidebanner() {
        var element = document.getElementById("smartbannerdiv");
        element.classList.toggle("open");
}