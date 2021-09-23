<a id="speakNext" href="#">&gt;</a>
<a id="speakButton" class="active">Narrate</a>
<a id="speakPrev" href="#">&lt;</a>
<div style="clear:both"></div>
<div id="speakBox">
    <p hidden class="js-api-support">API not supported</p>
    <form id="tts" action="" method="get">
        <fieldset id="tts_body">
            <fieldset class="field-wrapper">
                <select id="voice"></select>
            </fieldset>
            <fieldset class="field-wrapper">
                <label for="rate">Rate:</label>
                <input type="range" id="rate" min="0.5" max="2" value="1" step="0.1">
            </fieldset>
            <fieldset class="field-wrapper">
                <label for="pitch">Pitch:</label>
                <input type="range" id="pitch" min="0" max="2" value="1" step="0.1">
            </fieldset>
            <fieldset class="field-wrapper">
                <label for="volume">Volume:</label>
                <input type="range" id="volume" min="0" max="1" value="1" step="0.1">
            </fieldset>
        </fieldset>
        <fieldset class="buttons-wrapper">
            <button type="button" id="button-speak" class="button">Speak</button>
            <button type="button" id="button-stop" class="button">Stop</button>
            <button type="button" id="button-pause" class="button">Pause</button>
            <button type="button" id="button-resume" class="button">Resume</button>
        </fieldset>
    </form>
</div>