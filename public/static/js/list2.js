// 页面变量
var page = 1;
var rows = 10;
var desc = 1;
var order = 'id';
var loading = false;
var where = '';
var s_page = 1;
var isMain = true;
var isFull = false;
var _winHeight = $(window).height();

$('#action').on('click', function(event) {
	$('.ui-actionsheet').addClass('show');
});

$('#btnC').on('click', function(event) {
	$('.ui-actionsheet').removeClass('show');
});

$('.key').on('click',function(event) {
	order = $(this).attr('id');
	page = 1;
	$('.key i').remove();
	$(this).append("<i class=\"ui-icon-checked-s active\"></i>");
	$('#main li').remove();
	$('.ui-actionsheet').removeClass('show');
	getData("#main");
});

$('.order').on('click', function(event) {
	desc = $(this).attr('id');
	page = 1;
	$('.order i').remove();
	$(this).append("<i class=\"ui-icon-checked-s active\"></i>");
	$('#main li').remove();                
	$('.ui-actionsheet').removeClass('show');
	getData("#main");
});

// 搜索
$('.ui-searchbar').click(function(){
	$('.ui-container').css('border-top',0);
	$('header').addClass('hhide');
	isMain = false;
	$('.ui-notice').remove();
	$('.ui-searchbar-wrap').addClass('focus');
	$('#main').hide();
	$('#search').show();
	$('#searchText').focus();
});
// 取消搜索
$('.ui-searchbar-cancel').click(function(){
	$('header').removeClass('hhide');
	$('.ui-container').css('border-top','45px solid transparent');
	isMain = true;
	$('.ui-searchbar-wrap').removeClass('focus');
	$('#main').show();
	$('#search').hide();
	isFull = false;
	$('#searchText').blur();
});

$('.ui-searchbar .ui-searchbar-input input').on("keyup",function(){
	$('#search li').remove();
	$('.ui-notice').remove();
	s_page = 1;
	isFull = false;
	where = $('.ui-searchbar-input input').val();
	if (where != '')
	getData("#search");
});

$(document).ready(function(){
	getData("#main");
	$(document).on("scroll",function() {
		if ($('body').scrollTop() >= $(document).height() - $(window).height() ) {
		if(!isFull) {
		if (isMain)
			getData("#main");
		else
			getData("#search");
		}
		}
	});

});

function getData(s){
	if (loading)
	return false;

	loading = true;

	$.ajax({
	url: faceUrl ,
	type: 'GET' ,
	dataType: 'json' ,
	data: {'page': (s == "#main") ? page:s_page , 'rows':rows , 'desc':desc , 'where':(s == "#main") ? '':where, 'order':order},
	
	beforeSend:function(){
		$(s).append("<div class=\"ui-loading-wrap\"><p>加载中</p><i class=\"ui-loading\"></i></div>")
	},
	success:function(res) {
		// 请求成功
		if( res.code == 1){
			if (res.data == null){
				isFull = true;
				if (s_page == 1){
					$(s).append("<section class=\"ui-notice\">\
					<i></i>\
					<p>没有找到任何信息</p>\
					</section>");
				}
				$('.ui-loading-wrap').remove();
				loading = false;
				return false;
			}
			var data = res.data;
			var i = 0 ;
			$.each(data, function(index, val) {
				hot = val.read > 200?'#f74c31':'#b6cae0';
				$(s).append("\
					<li class=\"ui-border-t\" onclick=\"location.href=\'/admin/user/status?id=" + val.id + "\'\">\
	                    <div class=\"ui-list-info\">\
	                        <h4 class=\"ui-nowrap\"><span class=\"ui-txt-warning\">" + val.username + (val.status ? ' 在线' : '' )+ "</span></h4>\
	                        <p class=\"ui-nowrap\">" + val.start_time +" / " + ((val.bytes_received + val.bytes_sent)/1048576).toFixed(2) + "M</p>\
	                    </div>\
	                </li>\
				");
				i++;
			});
			if ( i < rows ) isFull = true;
			console.log("upload ok");
		}

		$('.ui-loading-wrap').remove();
			if (s == "#main"){
				page++;
			} else {
				s_page++;
			}
		// 加载完毕
		loading = false;
	},
		error:function(msg) {
		error('加载失败');
		console.log("error");
		},
	})
}

function error(m) {
	var e;
	e=$.tips({
	content:m,
	stayTime:2000,
	type:"warn"
	});
	$('.ui-loading-wrap').remove();
	loading = false;
}
