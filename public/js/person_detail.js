$(document).ready(function() {

	$('[data-person-info]').click(function() {
		$.getJSON('api/osoba/' + this.dataset.personInfo, function(response) {
			console.log(response);
			alert(response.first_name + ' ' + response.last_name + '\r\n' + response.nickname);
		});
	});

});