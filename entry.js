var coff = require('./coff.coffee');
var $ = require('jquery');
var kpiData = $.get('./toolbox.php/getStatistics/' + kpiName);

kpiData.done( function(data){
    console.log(data);
});

var select = function (selector) {return document.getElementById(selector);}

for (var i = 1; i <= 31; i++) {
    select('day-' + i + '-tr').style.fill = "green";
};
for (var i = 1; i <= 4; i++) {
    select('wk-' + i + '-tr').style.fill = "white";
};


select('nombre-mes').textContent = 'Noviembre'
select('kpi-nombre').textContent = 'OUTS OSAS'

select('dia-meta').textContent = '20'
select('dia-actual').textContent = '20'
select('dia-gap').textContent = '20'

select('sem-meta').textContent = '20'
select('sem-actual').textContent = '20'
select('sem-gap').textContent = '20'

select('mes-meta').textContent = '20'
select('mes-actual').textContent = '20'
select('mes-gap').textContent = '20'


