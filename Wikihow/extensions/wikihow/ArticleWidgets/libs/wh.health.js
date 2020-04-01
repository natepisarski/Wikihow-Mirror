function target_heart_rate(value1,value2,value3,age) {
    var rhr = (value1+value2+value3) / 3;
    var hrmax = 220 - age - rhr;
    var lower_limit = hrmax * 0.6 + rhr;
    var upper_limit = hrmax * 0.8 + rhr;
    return parseInt((lower_limit + upper_limit) / 2);
}

function calories(weight, ft, inch, age, gender, activity_level,target) {

    var height  = (ft*16+inch)*2.54;
    var w = weight / 2.2;
    
    var rmr = 9.99 * w + 6.25 * height + 4.92 * age + 166 * gender - 161;
     rmr = parseFloat(activity_level) * rmr;
    var res;
    res = rmr-500*target;        
    return parseInt(res);
}

function calories_si(weight, m, cm, age, gender, activity_level,target) {
    var height = m*100+cm; 
    var rmr = 9.99 * weight + 6.25 * height + 4.92 * age + 166 * gender - 161;
     rmr = parseFloat(activity_level) * rmr;
    var res;
    res = rmr-500*target;        
    return parseInt(res);
}