$.fn.fullyInView = function(){
    var viewport = {};
    viewport.top = $(window).scrollTop() + 50;
    viewport.bottom = viewport.top + $(window).height();
    var element = {};
    element.top = $(this).offset().top;
    element.bottom = element.top + $(this).outerHeight();
    return ((element.top > viewport.top) && (element.bottom < viewport.bottom));
};

$(document).ready(function() {
    
    var synth = window.speechSynthesis;
    var texts = [];
    var voices = [];
    var read_status = 0;
    function injectVoices(voicesElement, speech_voices) {
        voices = speech_voices;
        voicesElement.append(speech_voices.map(function (voice) {
            var option = document.createElement('option');
            option.value = voice.lang;
            option.textContent = voice.name + (voice.default ? ' (default)':'');
            option.setAttribute('data-voice-name', voice.name);
            return option;
        }).map(function (option) {
            return option.outerHTML;
        }).join(''));
    }

    function logEvent(event)
    {
        console.log(event);
        read_status = event;
    }
    
    if (!('SpeechSynthesisUtterance' in window)) {
        $('.js-api-support').show();
        $('form#tts').find('button').each(function( index ) {
            $(this).attr('disabled', 'disabled');
        });
    } else {
    	var par = [];
        var voice = $('#voice');
        var rate = document.getElementById('rate');
        var pitch = document.getElementById('pitch');
        var readParagraph = function(p){
            if ( texts[p] !== undefined ) {
                var selectedOption = $( "#voice option:selected" );
                var paragraph = texts[p];
                if(read_status && (read_status != 'stopped' || read_status != 'finished')){
                    synth.cancel();
                }
                // Create the utterance object setting the chosen parameters
                var utterance = new SpeechSynthesisUtterance();

                utterance.text = paragraph.text();
                
                for(i = 0; i < voices.length ; i++) {
                    if(voices[i].name === selectedOption.attr('data-voice-name')) {
                        utterance.voice = voices[i];
                    }
                }

                utterance.lang = selectedOption.val();
                utterance.rate = rate.value;
                utterance.pitch = pitch.value;

                $(utterance).on('start', function() {
                    paragraph.addClass('read');
                    logEvent('started');
                });

                $(utterance).on('end', function() {
                    logEvent('finished');
                    paragraph.removeClass('read');
                    readParagraph(p+1);
                });
                synth.speak(utterance);
                if(!paragraph.fullyInView()){
                    $('html, body').stop().animate({
                        scrollTop: paragraph.offset().top - 50
                    }, 2000);
                }
            }
        };
        
        injectVoices(voice, synth.getVoices());

        synth.addEventListener('voiceschanged', function onVoiceChanged() {
            synth.removeEventListener('voiceschanged', onVoiceChanged);
            injectVoices(voice, synth.getVoices());
        });

        $("body").on('click','#button-stop',function () {
            texts = [];
            $('#epubbody *').contents().filter(function() { 
                return (this.nodeType == 3) && this.nodeValue.length > 0; 
              }).unwrap("<span class=\"tobe_read\"></span>");
            synth.cancel();
            $("#button-speak").show();
            par = [];
            $(this).hide();
            logEvent('stopped');
        });

        $("body").on('click','#button-pause',function () {
            synth.pause();
            $('#button-resume').show();
            $(this).hide();
            logEvent('paused');
        });

        $("body").on('click','#button-resume',function () {
            synth.resume();
            logEvent('resumed');
            $('#button-pause').show();
            $(this).hide();
        });


        $("#button-speak").click(function (e) {
            e.preventDefault();
            var i = 0;
            $('#epubbody *').contents().filter(function() { 
                return (this.nodeType == 3) && this.nodeValue.length > 0; 
              }).wrap("<span class=\"tobe_read\"></span>");
            
            $( "#epubbody" ).find('span.tobe_read').each(function( index ) {
                par[index] = $(this);
            });
            texts = par;
            readParagraph(i);
            $('#button-stop').show();
            $('#button-pause').show();
            $(this).hide();
        });
        
        $( "#epubbody" ).on('click','span.tobe_read',function(){
            var index = $( "span.tobe_read" ).index( this );
            readParagraph(index-1);
        });

    }
	
	// Login Form

	$(function() {
		var button = $('#loginButton');
		var box = $('#loginBox');
		var form = $('#loginForm');
		button.removeAttr('href');
		button.mouseup(function(login) {
			box.toggle();
			button.toggleClass('active');
		});
		form.mouseup(function() { 
			return false;
		});
		$(this).mouseup(function(login) {
			if(!($(login.target).parent('#loginButton').length > 0)) {
				button.removeClass('active');
				box.hide();
			}
		});
	});
});

