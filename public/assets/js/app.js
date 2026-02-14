$(document).ready(function () {
	$('.ui.sidebar')
        .sidebar({
            context: $('.pusher'),
            transition: 'overlay'
        });

    $('#sidebar-toggle').on('click',function(){
        $('.ui.sidebar').sidebar('toggle');
    });
	function showToast(type, message) {
		let color = "info";

		if (type === "success") color = "green";
		if (type === "error") color = "red";
		if (type === "warning") color = "orange";

		$("body").toast({
			class: color,
			message: message,
			position: "top right",
			displayTime: 3000,
			showProgress: "bottom",
		});
	}
});
