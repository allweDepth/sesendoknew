$(document).ready(function () {

	$('.ui.sidebar').sidebar({
		context: $('.ui.pushable'),
		transition: 'push'
	});

	$('#sidebar-toggle').on('click', function () {
		$('.ui.sidebar').sidebar('toggle');
	});

	// TOGGLE
	$("#sidebar-toggle").on("click", function () {
		$(".ui.sidebar").sidebar("toggle");
	});

	// TOAST
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