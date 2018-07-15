This directory contains sample SOAP request/response pairs for the
Google Web APIs. They may be useful adjuncts to the WSDL file for
figuring out the SOAP API. Note that these samples have the
authentication key set to all 0s; your requests should fill in the
real key.

doSpellingSuggestion is a request to spellcheck "britney speers"
doSpellingSuggestionResponse contains the suggestion "britney spears"

doGetCachedPage is a request for a cached copy of http://www.google.com/
doGetCachedPageResponse contains the reply, base64 encoded HTML

doGoogleSearch is a query for "shrdlu winograd maclisp teletype".
doGoogleSearchResponse returns three search hits
