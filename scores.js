$(function () {
	$("#score_form").submit(function(e) {
		e.preventDefault();
		updateScore();
	});

	$("#event").change(function () {
		$("#status").text("");
		getScore();
	});

	$("#club").change(function () {
		$("#status").text("");
		getTeams();
	});

	$("#team").change(function () {
		$("#status").text("");
		getScore();
	});

	$("#score").keydown(function () {
		$("#status").text("");
	}).change(function () {
		$("#status").text("");
	});
});

function getTeams() {
	$("#team").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\"></option>").val("");
	$("#score").prop("disabled", true).val("");
	$("#submit").prop("disabled", true);

	if ($("#club").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "get_teams",
				club_id: $("#club").val(),
				_csrf_token : $("#csrf_token").val()
			},
			function (teams) {
				teams.forEach(function (team) {
					$("#team").append($("<option>").val(team.id).text(team.name));
				});
				$("#team").prop("disabled", false);
			},
			"json"
		).fail(function() {
			alert("An error occurred.");
		});
	}
}

function getScore() {
	$("#score").prop("disabled", true).val("");
	$("#submit").prop("disabled", true);

	if ($("#event").val() !== null && $("#club").val() !== null && $("#team").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "get_score",
				team_id: $("#team").val(),
				event_id: $("#event").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (score) {
				$("#score").val(score).prop("disabled", false);
				$("#submit").prop("disabled", false);
			},
			"text"
		).fail(function () {
			alert("An error occurred.");
		});
	}
}

function updateScore() {
	if ($("#event").val() !== null && $("#club").val() !== null && $("#team").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "update_score",
				team_id: $("#team").val(),
				event_id: $("#event").val(),
				score: $("#score").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function () {
				$("#status").text("Saved");
			}
		).fail(function () {
			alert("An error occurred.");
		});
	}
}
