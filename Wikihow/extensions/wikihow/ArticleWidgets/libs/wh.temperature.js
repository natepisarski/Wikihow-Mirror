function c2f (value) {
    value = parseFloat(value);
    return (value * 1.8 + 32).toFixed(1);
}

function f2c (value) {
    value = parseFloat(value);
    return ((value - 32) / 1.8).toFixed(1);
}

function c2k (value) {
    value = parseFloat(value);
    return (value + 273.15).toFixed(1);
}

function k2c (value) {
    value = parseFloat(value);
    return (value - 273.15).toFixed(1);
}

function f2k (value) {
    value = parseFloat(value);
    return ((value+459.7) / 1.8).toFixed(1);
}

function k2f (value) {
    value = parseFloat(value);
    return (value*1.8 - 459.7).toFixed(1);
}