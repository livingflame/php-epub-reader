var InView = function(el){
    var viewport = {};
    viewport.top = $(window).scrollTop() + 50;
    viewport.bottom = viewport.top + $(window).height() - 100;
    var element = {};
    element.top = el.offset().top;
    element.bottom = element.top + el.outerHeight();
    return (element.bottom > viewport.bottom);
};
var isset = function (variable){
    if (typeof variable !== 'undefined' && variable !== null) {
        return true;
    }
    return false;
};
var isEmpty = function (variable) {
    return (!variable || 0 === variable.length);
};

$(document).ready(function() {
    
    var synth = window.speechSynthesis;
    var texts = [];
    var voices = [];
    var read_status = 'init';
    var speaking = false;
    window.utterances = [];
    function injectVoices(voicesElement, speech_voices) {
        voices = speech_voices;
        voicesElement.empty();
        $.each(voices, function (i, voice) {
            var option = document.createElement('option');
            option.value = voice.lang;
            option.textContent = voice.name + (voice.default ? ' (default)':'');
            option.setAttribute('data-voice-name', voice.name);
            voicesElement.append($(option));
        });
    }

    function logEvent(event)
    {
        read_status = event;
    }

    function currenLinkHighlight(url)
    {
        $('#aside').find('a').removeClass("active");
        $("#aside a").each(function(){
            if (this.href == url){
                $(this).addClass("active");
            }
        });
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
        
        function getWordAt(str, pos) {
            // Perform type conversions.
            str = String(str);
            pos = Number(pos) >>> 0;

            // Search for the word's beginning and end.
            var left = str.slice(0, pos + 1).search(/\S+$/),
                right = str.slice(pos).search(/\s/);

            // The last word in the string is a special case.
            if (right < 0) {
                return str.slice(left);
            }
            // Return the word, using the located bounds to extract it from the string.
            return str.slice(left, right + pos);
        }
        var readParagraph = function(p){
            if ( par[p] !== undefined ) {
                current_index = p;
                
                $('#epubbody').find('.word_highlight').removeClass('word_highlight');
                var selectedOption = $( "#voice option:selected" );
                var paragraph =  $(par[p]);
                if(read_status && (read_status != 'stopped' || read_status != 'finished' || read_status != 'done')){
                    synth.cancel();
                } 
                // Create the utterance object setting the chosen parameters
                var utterance = new SpeechSynthesisUtterance();
                var content =  paragraph.html();
                var text =  paragraph.text();
                var words   = text.split(" ");
                var words_occurence = {};
                var classWord = function(word){
                    word = word.replace(/[^a-z0-9\s]/gi, '');
                    if(isset(words_occurence[word])){
                        words_occurence[word] = words_occurence[word] + 1;
                        word = word +'_'+ words_occurence[word];
                    } else {
                        words_occurence[word] = 0;
                    }
                    return word;
                };

                paragraph.empty();
                $.each(words, function(i, v) { 
                    var cword = classWord(v);
                    paragraph.append($('<span class="word_speak_'+cword+'"></span>').text(v + ' '));
                });

                utterance.text = text;
                for(var i = 0; i < voices.length ; i++) {
                    if(voices[i].name === selectedOption.attr('data-voice-name')) {
                        utterance.voice = voices[i];
                    }
                }

                words_occurence = {};
                utterance.onboundary = function( e ) {
                    var word = getWordAt(text,e.charIndex);
                    word = classWord(word);
                    paragraph.find('span').removeClass('word_highlight');
                    paragraph.find(".word_speak_"+word).addClass('word_highlight');
                };

                utterance.onstart = function(event){
                    paragraph.addClass('read');
                };

                utterance.onend = function(event){
                    var text_length = event.utterance.text.length;
                    if(event.charIndex == text_length){
                        paragraph.html(content);
                        readParagraph(p+1);
                        logEvent('finished');
                    } else {
                        logEvent('paused');
                    }
                    paragraph.removeClass('read');
                };
                
                utterance.lang = selectedOption.val();
                utterance.rate = rate.val();
                utterance.pitch = pitch.val();

                synth.speak(utterance);

                if(read_status == 'paused'){
                    synth.pause();
                }

                if(paragraph && InView(paragraph)){
                    $('html, body').animate({
                        scrollTop: paragraph.offset().top - 50
                    }, 'fast');
                }
            } else {
                var next = $('a[title="next_page"]').get(0);
                $(next).click();
            }
        };

        var markAllTextToRead = function(){
            par = [];
            $('#epubbody, #epubbody *').contents().filter(function() {
                return this.nodeType === 3 && $.trim(this.nodeValue) !== '';
            }).each(function() { 
                var $this = $(this);
                if($this.closest('.tobe_read').length == 0){
                    if($this.closest('p').length > 0){
                        if($this.closest('p,h1,h2,h3,h4,h5,h6').not('.tobe_read')){
                            $this.closest('p,h1,h2,h3,h4,h5,h6').addClass('tobe_read');
                        }
                    } else {
                        if($this.closest('span').length > 0 && $this.closest('span').not('.tobe_read')){
                            $this.closest('span').addClass('tobe_read');
                        } else {
                            $this.wrap("<span class=\"tobe_read\"></span>");
                        }
                    }
                }
            });
            
            $( "#epubbody" ).find('.tobe_read').each(function( index ) {
                $(this).addClass('tobe_read_' + index);
                par[index] = '.tobe_read_' + index;
            });
        };

        var setTitleFromContent = function(){
            var title =  $('#epubbody, #epubbody *').contents().filter(function() {
                return this.nodeType === 3 && $.trim(this.nodeValue) !== '';
            }).first().text();
            $(".page_title").html( title );
            document.title = title;
        };

        $("body").delegate('a[title="next_page"],a[title="prev_page"]','click',function (e) {
            $("#button-resume,#button-pause,#speakPrev,#speakNext,#speakBox").hide();
            synth.cancel();
            par = [];
            e.preventDefault();
            var href = $(this).attr('href');
            $.get( href + "&ajax=true", function( data ) {
                currenLinkHighlight(href);
                history.pushState(data, data.title, href);
                $("#epubbody").html( data.content );
                $(".book_percent").html( data.percentage + "%" );
                $(".book_title").html( data.book );
                
                $(".nav_container").html( data.nav );
                $('head').find('link').not('#xui_style').remove();
                $('head').find('style').remove();
                $.each(data.css,function(i,css){
                    $('title').after(css);
                });
                $.each(data.styles,function(i,style){
                    $('title').after(style);
                });
                $("html, body").animate({ scrollTop: 0 }, "fast");
                markAllTextToRead();
                setTitleFromContent();
                if(speaking){
                    $("#button-speak").click();
                }
            });
        });

        injectVoices($('#voice'), synth.getVoices());
        if (synth.onvoiceschanged !== undefined) {
            synth.onvoiceschanged = injectVoices($('#voice'), synth.getVoices());
        };

        $("body").on('click','#button-stop',function () {
            speaking = false;
            synth.cancel();
            par = [];
            $("#button-speak").show();
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
            readParagraph(current_index-2);
        });

        $("body").on('click','#speakNext',function (e) {
            e.preventDefault();
            readParagraph(current_index+1);
        });

        $(document).keydown(function(e){
            //37 - left
            if (e.which == 37) {
                $('#speakPrev').click();
				e.preventDefault();
            }
            //39 - right   
            if (e.which == 39) {
                $('#speakNext').click();
				e.preventDefault();
            }
        });

        $("body").on('click','#button-speak',function (e) {
            logEvent('started');
            e.preventDefault();
            speaking = true;

            readParagraph(0);
            $('#button-stop,#button-pause,#speakPrev, #speakNext').show();
            $(this).hide();
        });
        currenLinkHighlight(window.location.href);
    }

	// Form
    $("body").on('click','#speakButton',function (event) {
        event.preventDefault();
        $('#speakBox').toggle();
    });
	var visible =  false;
	var top_offset = 0;
    $("body").on('click','a.toc',function(event) {
        $(this).toggleClass('on');
        event.preventDefault();
		$('#aside').toggleClass('on');
		setTimeout(function(){ 
			visible = !visible;
			if(visible){
				var $el = $("#aside").find('a.active').closest('li');
				var id = $el.attr('id');
				console.log(id);
				if(top_offset == 0){
					top_offset = $('#' + id).offset().top - 100;
				}
				$('#aside').animate({
					scrollTop: top_offset
				}, 300);
			}
		}, 100);

    });

    $("body").on('click',function(event) {
        if(!($(event.target).closest('#speakContainer').length > 0)) {
            $('#speakBox').hide();
        }
    });
    
    
    markAllTextToRead();
    setTitleFromContent();
});