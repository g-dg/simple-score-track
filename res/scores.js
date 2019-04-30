$(function () {
	$("#score_form").submit(function(e) {
		e.preventDefault();
		setScore();
	});

	$("#year").change(function () {
		$("#status").text("");
		getCompetitions();
		getClubs();
	});

	$("#competition").change(function () {
		$("#status").text("");
		getEvents();
		getTeams();
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

function getCompetitions() {
	$("#competition").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Competition --</option>").val("");
	$("#event").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Event --</option>").val("");
	$("#team").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Team --</option>").val("");
	$("#score").prop("disabled", true).val("");
	$("#submit").prop("disabled", true);

	if ($("#year").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "get_competitions",
				year_id: $("#year").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (competitions) {
				competitions.forEach(function (competition) {
					$("#competition").append($("<option>").val(competition.id).text(competition.name));
				});
				$("#competition").prop("disabled", false);
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the competitions.");
		});
	}
}

function getEvents() {
	$("#event").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Event --</option>").val("");
	$("#score").prop("disabled", true).val("");
	$("#submit").prop("disabled", true);

	if ($("#competition").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "get_events",
				competition_id: $("#competition").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (events) {
				events.forEach(function (event) {
					$("#event").append($("<option>").val(event.id).text(event.name));
				});
				$("#event").prop("disabled", false);
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the events.");
		});
	}
}

function getClubs() {
	$("#club").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Club --</option>").val("");
	$("#team").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Team --</option>").val("");
	$("#score").prop("disabled", true).val("");
	$("#submit").prop("disabled", true);

	if ($("#year").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "get_clubs",
				year_id: $("#year").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (clubs) {
				clubs.forEach(function (club) {
					$("#club").append($("<option>").val(club.id).text(club.name));
				});
				$("#club").prop("disabled", false);
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the clubs.");
		});
	}
}

function getTeams() {
	$("#team").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Team --</option>").val("");
	$("#score").prop("disabled", true).val("");
	$("#submit").prop("disabled", true);

	if ($("#club").val() !== null && $("#competition").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "get_teams",
				club_id: $("#club").val(),
				competition_id: $("#competition").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (teams) {
				teams.forEach(function (team) {
					$("#team").append($("<option>").val(team.id).text(team.name));
				});
				$("#team").prop("disabled", false);
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the teams.");
		});
	}
}

function getScore() {
	$("#score").prop("disabled", true).val("");
	$("#submit").prop("disabled", true);

	if ($("#event").val() !== null && $("#team").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "get_score",
				team_id: $("#team").val(),
				event_id: $("#event").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (response) {
				$("#score").val(response.score).prop("disabled", false);
				$("#submit").prop("disabled", false);
			},
			"text"
		).fail(function () {
			alert("An error occurred getting the score.");
		});
	}
}

function setScore() {
	if ($("#event").val() !== null && $("#team").val() !== null) {
		$.post(
			"scores.php",
			{
				action: "set_score",
				team_id: $("#team").val(),
				event_id: $("#event").val(),
				score: $("#score").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function () {
				$("#status").text("Saved");
			}
		).fail(function () {
			alert("An error occurred setting the score.");
		});
	}
}
