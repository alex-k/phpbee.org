ns4 = (document.layers)? true:false
mz = (document.getElementById)? true:false
ie4 = (document.all)? true:false
if (ie4) { mz=false; }
var x;
var y;
init();
function init() {
	if (ns4) {document.captureEvents(Event.MOUSEMOVE);}
	document.onmousemove=mousemove;
}
function mousemove(e) {
	if (mz) {var mouseX=e.pageX; var mouseY=e.pageY}
	if (ie4) {var mouseX=event.x; var mouseY=event.y}
	x=mouseX;
	y=mouseY;
}

function show_calendar(_year,_month,_day,_obj,cal_new,_gobj)
{
if (_gobj) {	_gobj.setAttribute("id", _obj); }
now=new Date(_year,_month-1,_day);
tmp=now;
nw=new Date();
months=new Array("Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь");
cyear=now.getYear();
year=(cyear<1000) ? 1900+cyear : cyear;
month=now.getMonth();
day=now.getDate();
str="<table width=150 cellpadding=0 cellspacing=0><tr><td align=right><a href=\"javascript:void(0);\" onClick=\"cal_close();\"><img src=images/cal_cl.gif title=\"Закрыть календарь\" border=0></a></td></tr></table>";
str+="<table class=cal_table width=150>";
str+="<tr><td><a href=\"javascript:void(0);\" onClick=\"show_calendar("+year+","+(month)+","+day+",'"+_obj+"',0,0);\" title=\"Предыдущий месяц\"><img src=images/al.gif border=0></a></td><td colspan=5 align=center class=cal_s12>"+months[month]+" "+year+"</td><td><a href=\"javascript:void(0);\" onClick=\"show_calendar("+year+","+(month+2)+","+day+",'"+_obj+"',0,0);\"  title=\"Следующий месяц\"><img src=images/ar.gif border=0></a></td></tr>";
str+="<tr><td class=cal_days>Пн</td><td class=cal_days>Вт</td><td class=cal_days>Ср</td><td class=cal_days>Чт</td><td class=cal_days>Пт</td><td class=cal_days>Сб</td><td class=cal_days>Вс</td></tr>";
k=1;
for (j=0;j<6;j++)
	{
	str+="<tr>";
	for (i=1;i<8;i++)
		{
		tmp.setDate(k);
		if (tmp.getDay()==((i<7) ? i : 0) && tmp.getMonth()==month)
			{
			cur_class=(tmp.getDate()==nw.getDate() && tmp.getMonth()==nw.getMonth() && tmp.getYear()==nw.getYear()) ? "cal_current" : "cal_cell";
			tmp_year=tmp.getYear();
			tmp_year=(tmp_year<1000) ? 1900+tmp_year : tmp_year;
			//str=str+"<td class="+cur_class+"><a href=\"javascript:void(0);\" onClick=\"insertDate('"+k+"."+(tmp.getMonth()+1)+"."+tmp_year+"','"+_obj+"');\">"+tmp.getDate()+"</a></td>";
			str=str+"<td class="+cur_class+"><a href=\"javascript:void(0);\" onClick=\"insertDate('"+tmp_year+"-"+(tmp.getMonth()+1)+"-"+k+"','"+_obj+"');\">"+tmp.getDate()+"</a></td>";
			k++;
			}
		else
			{
			str+="<td class=cal_cell>&nbsp;</td>";
			}
		}
	str+="</tr>";
	}
str+="</table>";

cal=document.getElementById("calendar");
if (!cal)
	{
	var cal = document.createElement("div");
	document.body.appendChild(cal);
	cal.setAttribute("id", "calendar");
	}
if (cal_new) { cal.style.cssText="position:absolute; left:"+x+"; top:"+y;	}
cal.innerHTML=str;
}

function cal_close()
{
elem=document.getElementById("calendar");
document.body.removeChild(elem);
}


function insertDate(date,_obj)
{
field_id=document.getElementById(_obj);
field_id.value=date;
elem=document.getElementById("calendar");
document.body.removeChild(elem);
}
