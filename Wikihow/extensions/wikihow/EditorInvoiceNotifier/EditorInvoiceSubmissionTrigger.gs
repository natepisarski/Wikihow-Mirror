/**
 * Submission trigger for the Content Invoice System
 */

var config = {
    isDev: false
};

if (config.isDev) {
    config.logSpreadsheet = '1pRUMV7piMUo-V2THIWg4oqaj-Q7EkzrNzXGAK3ISKQ4';
    config.backend = 'https://alber1.wikidogs.com';
} else {
    config.logSpreadsheet = '1oU0ms8ZSd3OZbJcC6ABj-l78f66oavjvUzoq9JBobDk';
    config.backend = 'https://www.wikihow.com';
}

// Logger = BetterLog.useSpreadsheet(config.logSpreadsheet);

function throwError(msg) {
    // Logger.log(msg);
    throw new Error(msg);
}

function onInstall() {
    var sheet = SpreadsheetApp.getActive();
    var triggers = ScriptApp.getUserTriggers(sheet);

    ScriptApp.newTrigger('onFormSubmit')
        .forSpreadsheet(sheet)
        .onFormSubmit()
        .create();

    PropertiesService.getScriptProperties().setProperty('active', sheet.getId());
}

function sendHttpPost(payload) {
    var headers = {};

    if (config.isDev) {
        var authEncoded = Utilities.base64Encode('wh:eng', Utilities.Charset.UTF_8);
        var headers = {
            'Authorization': 'Basic ' + authEncoded
        };
    }

    var options = {
        'method': 'post',
        'payload': payload,
        'headers': headers,
        'muteHttpExceptions': true
    };

    var postUrl = config.backend + '/Special:EditorInvoiceNotifier';
    return UrlFetchApp.fetch(postUrl, options);
}

function getUserInfo(nickname) {
    var spreadsheet = SpreadsheetApp.getActive();
    var sheet = spreadsheet.getSheetByName('Data');

    var nicknameColumn = 1;
    var nameColumn = 2;
    var emailColumn = 3;
    var passwordColumn = 6;
    var rowOffset = 2;

    var nicknames = sheet.getRange(rowOffset, nicknameColumn, sheet.getLastRow()).getValues();
    var searchResult = nicknames.findIndex(nickname);

    if (searchResult != -1) {
        var nameRange = sheet.getRange(searchResult + rowOffset, nameColumn);
        var emailRange = sheet.getRange(searchResult + rowOffset, emailColumn);
        var passwordRange = sheet.getRange(searchResult + rowOffset, passwordColumn);
        return {
            'name': nameRange.getValue(),
            'email': emailRange.getValue(),
            'password': passwordRange.getValue()
        };
    } else {
        return false;
    }
}

Array.prototype.findIndex = function(search) {
    if (search == '') return false;
    for (var i = 0; i < this.length; i++)
        if (this[i] == search) return i;

    return -1;
}

// Example of namedValues:
// namedValues={Notes?=[], URL?=[http://www.wikihow.com/Kiss], Code?=[12345], How long did you spend on this article? =[5:00:00], Name?=[George Bahij], Timestamp=[2/5/2016 15:33:44], Was this a revision? =[No]}, range=Range, source=Spreadsheet, triggerUid=2028711833601947701}

function onFormSubmit(e) {
    var nickname = e.namedValues['Name?'];
    var password = e.namedValues['Code?'];
    var url = e.namedValues['URL?'];
    var timeSpent = e.namedValues['How long did you spend on this article? '];
    var revision = e.namedValues['Was this a revision? '];
    var notes = e.namedValues['Notes?'];
    var timestamp = e.namedValues['Timestamp'];

    if (nickname == undefined || password == undefined || timeSpent == undefined || revision == undefined || timestamp == undefined) {
        throwError('Missing values' + JSON.stringify(e.namedValues));
    }

    e.namedValues['Code?'] = '***'; // Safer logging
    Logger.log('onFormSubmit called with values: ' + JSON.stringify(e.namedValues));

    if (notes == undefined) {
        notes = '';
    } else {
        notes = notes.toString();
    }

    var sheetId = PropertiesService.getScriptProperties().getProperty('active');

    if (!sheetId) {
        throwError('No sheet ID found for active spreadsheet.');
    }

    nickname = nickname.toString();
    password = password.toString();
    url = url.toString();
    timeSpent = timeSpent.toString();
    revision = revision.toString();
    timestamp = timestamp.toString();

    var userInfo = getUserInfo(nickname);

    if (!userInfo) {
        throwError(nickname + ' not found in Data sheet.');
    }

    var nameWithDetails = nickname + ' (real name: ' + userInfo['name'] + ')';

    if (!userInfo['email']) {
        throwError('No e-mail found for ' + nameWithDetails);
    }

    if (userInfo['password'] != password) {
        throwError(nameWithDetails + ' provided an incorrect password.');
    }

    var payload = {
        'action': 'send',
        'sheet': sheetId,
        'nickname': nickname,
        'name': userInfo['name'],
        'email': userInfo['email'],
        'url': url,
        'timespent': timeSpent,
        'timestamp': timestamp,
        'revision': revision,
        'notes': notes
    };

    var response = sendHttpPost(payload);

    Logger.log('Response headers: ' + response.getAllHeaders().toSource());

    if (response.getResponseCode() != 200) {;
        throwError('Response code:' + response.getResponseCode() + '. Content: ' + response);
    }

    Logger.log('Response content: ' + response.getContentText());

    var responseJson = JSON.parse(response.getContentText());
    if (responseJson['error']) {
        throwError('Back-end error: ' + responseJson['error']);
    }
}
