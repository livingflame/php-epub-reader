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
        read_status = event;
    }
    
    if (!('SpeechSynthesisUtterance' in window)) {
        $('.js-api-support').show();
        $('form#tts').find('button').each(function( index ) {
            $(this).attr('disabled', 'disabled');
        });
    } else {
    	var par = [];
    	var current_index = 0;
        var voice = $('#voice');
        var rate = $( "#rate" );
        var pitch = $( "#pitch" );
        var readParagraph = function(p){
            current_index = p;
            if ( par[p] !== undefined ) {
                var selectedOption = $( "#voice option:selected" );
                var paragraph = par[p];
                if(read_status && (read_status != 'stopped' || read_status != 'finished' || read_status != 'done')){
                    synth.cancel();
                } 

                // Create the utterance object setting the chosen parameters
                var utterance = new SpeechSynthesisUtterance();
                utterance.text = paragraph.text();
                for(var i = 0; i < voices.length ; i++) {
                    if(voices[i].name === selectedOption.attr('data-voice-name')) {
                        utterance.voice = voices[i];
                    }
                }

                utterance.lang = selectedOption.val();
                utterance.rate = rate.val();
                utterance.pitch = pitch.val();
                utterance.onstart = function(event){
                    paragraph.addClass('read');
                    logEvent('started');
                };
                utterance.onend = function(event){
                    var text_length = event.utterance.text.length;
                    if(event.charIndex == text_length){
                        readParagraph(p+1);
                        logEvent('finished');
                    } else {
                        logEvent('done');
                    }
                    paragraph.removeClass('read');
                };
                synth.speak(utterance);
                if(!paragraph.fullyInView()){
                    $('body').scrollTop(paragraph.offset().top - 50);
                }
            } else {
                $("#button-resume,#button-pause,#speakPrev,#speakNext,#speakBox").hide();
                $("#button-speak").show();
                $( 'a[title="next_page"' ).first().click();
            }

        };
        
        injectVoices(voice, synth.getVoices());
        if (synth.onvoiceschanged !== undefined) {
            synth.onvoiceschanged = injectVoices(voice, synth.getVoices());
        };
        $("body").on('click','#button-stop',function () {
            synth.cancel();
            $("#button-speak").show();
            par = [];
            $(this).hide();
            $("#button-resume,#button-pause,#speakPrev, #speakNext").hide();
            logEvent('stopped');
        });

        $("body").on('click','#button-pause',function (e) {
            e.preventDefault();
            synth.pause();
            $('#button-resume').show();
            $(this).hide();
            logEvent('paused');
        });

        $("body").on('click','#button-resume',function (e) {
            e.preventDefault();
            synth.resume();
            logEvent('resumed');
            $('#button-pause').show();
            $(this).hide();
        });

        $("body").on('click','#speakPrev',function (e) {
            e.preventDefault();
            readParagraph(current_index-1);
        });

        $("body").on('click','#speakNext',function (e) {
            e.preventDefault();
            readParagraph(current_index+1);
        });

        $("#button-speak").click(function (e) {
            e.preventDefault();

            $('#epubbody *').contents().each(function() { 
                var $this = $(this);
                if($this.closest('.tobe_read').length == 0){
                    if($this.closest('p').length > 0){
                        if($this.closest('p').not('.tobe_read')){
                            $this.closest('p').addClass('tobe_read');
                        }
                    } else {
                        $this.wrap("<span class=\"tobe_read\"></span>");
                    }
                }
            });
            
            $( "#epubbody" ).find('.tobe_read').each(function( index ) {
                par[index] = $(this);
            });
            readParagraph(0);
            $('#button-stop,#button-pause,#speakPrev, #speakNext').show();
            $(this).hide();
        });
        
    }
	
	// Form
    var speakButton = $('#speakButton');
    var box = $('#speakBox');
    speakButton.click(function(event) {
        box.toggle();
        event.preventDefault();
    });
    $('body').click(function(event) {
        if(!($(event.target).closest('#speakContainer').length > 0)) {
            box.hide();
        }
    });
});

