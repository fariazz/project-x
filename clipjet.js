var ClipjetObj = new Object();

function getFrameID(id){
    var elem = document.getElementById(id);
    if (elem) {
        if(/^iframe$/i.test(elem.tagName)) return id; //Frame, OK
        // else: Look for frame
        var elems = elem.getElementsByTagName("iframe");
        if (!elems.length) return null; //No iframe found, FAILURE
        for (var i=0; i<elems.length; i++) {
           if (/^https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com(\/|$)/i.test(elems[i].src)) break;
        }
        elem = elems[i]; //The only, or the best iFrame
        if (elem.id) return elem.id; //Existing ID, return it
        // else: Create a new ID
        do { //Keep postfixing `-frame` until the ID is unique
            id += "-frame";
        } while (document.getElementById(id));
        elem.id = id;
        return id;
    }
    // If no element, return null.
    return null;
}

// Define YT_ready function.
var YT_ready = (function() {
    var onReady_funcs = [], api_isReady = false;
    /* @param func function     Function to execute on ready
     * @param func Boolean      If true, all qeued functions are executed
     * @param b_before Boolean  If true, the func will added to the first
                                 position in the queue*/
    return function(func, b_before) {
        if (func === true) {
            api_isReady = true;
            while (onReady_funcs.length) {
                // Removes the first func from the array, and execute func
                onReady_funcs.shift()();
            }
        } else if (typeof func == "function") {
            if (api_isReady) func();
            else onReady_funcs[b_before?"unshift":"push"](func); 
        }
    }
})();
// This function will be called when the API is fully loaded
function onYouTubePlayerAPIReady() {
   YT_ready(true)
}

// Load YouTube Frame API
(function() { // Closure, to not leak to the scope
  var s = document.createElement("script");
  s.src = (location.protocol == 'https:' ? 'https' : 'http') + "://www.youtube.com/player_api";
  var before = document.getElementsByTagName("script")[0];
  before.parentNode.insertBefore(s, before);
})();

var ClipjetPlayer; //Define a player object, to enable later function calls, without
            // having to create a new class instance again.

ClipjetObj.started = false;
ClipjetObj.ended = false;

// Add function to execute when the API is ready
YT_ready(function(){
    var frameID = getFrameID("clipjet-video");
    if (frameID) { //If the frame exists
        ClipjetPlayer = new YT.Player(frameID, {
            events: {
                "onStateChange": ClipjetObj.checkClipjetVideoStatus
            }
        });
    }
});

ClipjetObj.checkClipjetVideoStatus = function(event) {
    if(event.data === 0 && !ClipjetObj.ended) {        
        ClipjetObj.ended = true;
        window.clearInterval(ClipjetObj.timer);
        ClipjetObj.notifyVideoUpdate(1);
    }
    else if(event.data === 1) {
        ClipjetObj.timer = setInterval(ClipjetObj.notifyVideoUpdate, 10000);
        
        if(!ClipjetObj.started) {
            ClipjetObj.started = true;           
            ClipjetObj.notifyVideoStarted();
        }        
    }  
    else {
        window.clearInterval(ClipjetObj.timer);
    }
};

ClipjetObj.makeid = function() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for( var i=0; i < 25; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    return text;
}

ClipjetObj.token = '';
//ClipjetObj.url = 'http://www.clipjet.co';
ClipjetObj.url = 'http://3m6u.localtunnel.com';


ClipjetObj.notifyVideoStarted = function() {
    var img = document.createElement('img');
    var body = document.getElementsByTagName('body');
    var articleUrl = document.URL;
    ClipjetObj.token = ClipjetObj.makeid();
    //console.log('started');
    //var apiUrl = ClipjetObj.url+"/hit/create?email="+document.getElementById("clipjet-email").innerHTML+'&article_url='+encodeURIComponent(articleUrl)+'&advertiser_id='+document.getElementById("clipjet-advertiser").innerHTML+'&token='+ClipjetObj.token;
    //console.log('api:'+apiUrl);
    img.src = ClipjetObj.url+"/hit/create?email="+document.getElementById("clipjet-email").innerHTML+'&article_url='+encodeURIComponent(articleUrl)+'&advertiser_id='+document.getElementById("clipjet-advertiser").innerHTML+'&token='+ClipjetObj.token;
    //console.log(apiUrl);
    body[0].appendChild(img);
};

//ClipjetObj.notifyVideoFinished = function() {
//    var img = document.createElement('img');
//    var body = document.getElementsByTagName('body');    
//    img.src = "http://www.clipjet.co/hit/update?token="+ClipjetObj.token;
//    body[0].appendChild(img);
//};

ClipjetObj.notifyVideoUpdate = function(endOfVideo) {
    endOfVideo = endOfVideo ? 1 : 0;
    var img = document.createElement('img');
    var body = document.getElementsByTagName('body');    
    var currTime = ClipjetPlayer.getCurrentTime();
    //console.log('update');
    img.src = ClipjetObj.url+"/hit/update?token="+ClipjetObj.token+'&elapsed_time='+currTime+'&end_of_video='+endOfVideo;
    body[0].appendChild(img);
};

