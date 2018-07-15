<p><?= wfMessage('unlinksocial-description')->text() ?></p>
<p><?= wfMessage('unlinksocial-warning')->text() ?></p>

<form id="user-search-form">
    <input type="text" id="user-search-username" class="input_med" placeholder="User name">
    <input type="submit" id="user-search-submit" class="button primary" value="Find user">
</form>

<div id="error-box" class="hidden">

    <h2>API error:</h2>

    <p id="error-msg">Test</p>

</div>

<div id="search-results">

    <h2>Search results</h2>

    <div class="search-result">
        <div>wikiHow username:</div>
        <div id="wikihow-name">Placeholder</div>
    </div>

    <div class="search-result">
        <div>wikiHow user ID:</div>
        <div id="wikihow-id">Placeholder</div>
    </div>

    <div class="search-result">
        <div>Google user ID:</div>
        <div id="google-id">Placeholder</div>
        <div id="google-unlink-div">(<a id="google-unlink-link">unlink</a>)</div>
    </div>

    <div class="search-result">
        <div>Facebook user ID:</div>
        <div id="facebook-id">Placeholder</div>
        <div id="facebook-unlink-div">(<a id="facebook-unlink-link">unlink</a>)</div>
    </div>

</div>
