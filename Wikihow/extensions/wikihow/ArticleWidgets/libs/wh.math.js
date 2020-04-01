function percentage(total,part) {
    return parseFloat(parseFloat(part * 100 / total).toFixed(2));
}

function dec2bin(value) { 
    x = parseInt(value);
    return x.toString(2);
}

function bin2dec(value) { 
    x = parseInt(value,2);
    return x.toString(10);
}

function dec2hex(value) { 
    x = parseInt(value);
    return x.toString(16).toUpperCase();
}

function hex2dec(value) {
    x = parseInt(value,16);
    return x.toString(10);
}
