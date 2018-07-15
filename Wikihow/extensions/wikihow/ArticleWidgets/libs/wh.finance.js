//http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.quotes%20where%20symbol%20in%20(%22GOLD%22)&env=http://datatables.org/alltables.env&format=json

var gold_quote = 0;



function scrap_gold_value(weight, karat) {

    var price_per_gram = karat/24*gold_quote/31;
    return weight*price_per_gram;

}
