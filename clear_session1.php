<?php
	if( !empty($_GET['op']) && $_GET['op'] == 'clear' ){
		//清session
		session_start();
		session_unset();
		session_destroy();
		return;
	}
	
?>
<html>
<body>
	<button class="clear-session">clear-session</button>
	<script src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
	<script>
		$('.clear-session').click(function(){
			$.ajax({
				url:window.location.href+'?op=clear',
				type:"GET",
				complete:function(x){
					
				}
			})
		})
	</script>
</body>
</html>