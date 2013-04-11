var ClipjetObj = new Object();
ClipjetObj.started = false;
ClipjetObj.ended = false;

ClipjetObj.isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
ClipjetObj.isSafari = /Safari/.test(navigator.userAgent) && /Apple Computer/.test(navigator.vendor);
ClipjetObj.isWebkit = ClipjetObj.isChrome || ClipjetObj.isSafari;

// Load YouTube Frame API
(function() { // Closure, to not leak to the scope
  var s = document.createElement("script");
  s.src = (location.protocol == 'https:' ? 'https' : 'http') + "://www.youtube.com/player_api";
  var before = document.getElementsByTagName("script")[0];
  before.parentNode.insertBefore(s, before);
})();

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

// This function will be called when the API is fully loaded
function onYouTubePlayerAPIReady() {
    console.log('YT ready');
    //setTimeout( function() {
    var initPlayer = function() {
        console.log('trying to init player');
        var frameID = getFrameID("clipjet-video");
        console.log(frameID);
        if (frameID) {
            
            var ytplayer = document.getElementById('clipjet-video');
            
            
            ClipjetObj.player = new YT.Player(frameID, {
                events: {
                    //"onStateChange": ClipjetObj.checkVideoStatus
                    onStateChange: ClipjetObj.checkVideoStatus,
                    onReady: function(e) {console.log('ready')}
                }
            });
            console.log(ClipjetObj.player);
        }
        else {
            setTimeout(initPlayer, 1000);
        }
    };
    initPlayer();
}

ClipjetObj.checkVideoStatus = function(event) {
    console.log('changed status');
    console.log(event.data);
    if(event.data === 0 && !ClipjetObj.ended) {        
        ClipjetObj.ended = true;
        
        if(!ClipjetObj.isWebkit) {
            window.clearInterval(ClipjetObj.timer);
        }
        ClipjetObj.notifyVideoUpdate(1);
    }
    else if(event.data === 1) {
        if(!ClipjetObj.isWebkit) {
            ClipjetObj.timer = setInterval(ClipjetObj.notifyVideoUpdate, 10000);
        }
        
        if(!ClipjetObj.started) {
            ClipjetObj.started = true;           
            ClipjetObj.notifyVideoStarted();
        }        
    }  
    else {
        if(ClipjetObj.timer) {
            window.clearInterval(ClipjetObj.timer);
        }        
    }
};

ClipjetObj.makeid = function() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for( var i=0; i < 25; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    return text;
};

ClipjetObj.token = '';
ClipjetObj.url = 'http://www.clipjet.co';
//ClipjetObj.url = 'http://3m6u.localtunnel.com';


ClipjetObj.notifyVideoStarted = function() {
    var img = document.createElement('img');
    var body = document.getElementsByTagName('body');
    var articleUrl = encodeURIComponent(document.URL);
    ClipjetObj.token = ClipjetObj.makeid();
    console.log('started');
    img.setAttribute('style','visibility:hidden;');
    img.src = ClipjetObj.url+"/hit/create?email="+document.getElementById("clipjet-email").innerHTML+'&article_url='+articleUrl+'&advertiser_id='+document.getElementById("clipjet-advertiser").innerHTML+'&token='+ClipjetObj.token;
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
    var currTime = ClipjetObj.player.getCurrentTime();
    console.log('update');
    img.setAttribute('style','visibility:hidden;');
    img.src = ClipjetObj.url+"/hit/update?token="+ClipjetObj.token+'&elapsed_time='+currTime+'&end_of_video='+endOfVideo;
    body[0].appendChild(img);
};

window.onbeforeunload = function() {
    if(!ClipjetObj.ended) {
        ClipjetObj.notifyVideoUpdate();
    }    
};