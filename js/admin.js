var API_BSC = "https://api.bscscan.com/api?";
var API_FA = "https://farm.army/api/v0/";
var w_beefy = "0x14677932e07aB98bcc9E5b6947264b244262BA38";
var far_FA = "farms/";
var beefy_bsc = "address=" + w_beefy;
var key_bsc = "apikey=UT4KW32KNE1YQ82PMQQWTQ58ZBQXVHRWIY";
var bal_bsc = API_BSC + "module=account&action=balance";


const xhttp = new XMLHttpRequest();
function aco(...args) {
	var url = arguments[0];
	for (var i = 1; i < arguments.length; i++) {
		url = url.concat("&" + arguments[i]);
	}
	console.log(url)
	xhttp.open("GET", url, true);
	xhttp.send();
}

aco(bal_bsc, beefy_bsc, key_bsc);
