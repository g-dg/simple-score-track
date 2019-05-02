"use strict"

var score_type = null;

$(function () {
	$("#score_form").submit(function (e) {
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
		score_type = $("#event").find(":selected").length > 0 ? $("#event").find(":selected").data("event_type") : null;
		switch (score_type) {
			case "points":
				$("#team").prop("disabled", $("#club").val() == null);
				$("#score_points").show();
				$("#score_time").hide();
				$("#score_errors").hide();
				$("#individual_scores").empty().hide();
				break;
			case "timed":
				$("#team").prop("disabled", $("#club").val() == null);
				$("#score_points").hide();
				$("#score_time").show();
				$("#score_errors").show();
				$("#individual_scores").empty().hide();
				break;
			case "individual":
				$("#team").prop("disabled", true).val("");
				$("#score_points").hide();
				$("#score_time").hide();
				$("#score_errors").hide();
				$("#individual_scores").empty().show();
				break;
		}
		getScore();
	});

	$("#club").change(function () {
		$("#status").text("");
		getTeams();
		if (score_type == "individual") {
			getScore();
		}
	});

	$("#team").change(function () {
		$("#status").text("");
		getScore();
	});

	$("#score_points_value").keydown(function () {
		$("#status").text("");
	}).change(function () {
		$("#status").text("");
	});
	$("#score_time_value").keydown(function () {
		$("#status").text("");
	}).change(function () {
		$("#status").text("");
	});
	$("#score_errors_value").keydown(function () {
		$("#status").text("");
	}).change(function () {
		$("#status").text("");
	});
});

function getCompetitions() {
	$("#competition").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Competition --</option>").val("");
	$("#event").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Event --</option>").val("");
	$("#team").prop("disabled", true).find("option").remove().end().append("<option value=\"\" selected=\"selected\" disabled=\"disabled\">-- Select Team --</option>").val("");
	$("#score_points_value").prop("disabled", true).val("");
	$("#score_time_value").prop("disabled", true).val("");
	$("#score_errors_value").prop("disabled", true).val("");
	$("#individual_scores").empty().hide();
	$("#submit").prop("disabled", true);
	score_type = null;

	if ($("#year").val() !== null) {
		$.post(
			"scores.php?action=get_competitions",
			{
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
	$("#score_points_value").prop("disabled", true).val("");
	$("#score_time_value").prop("disabled", true).val("");
	$("#score_errors_value").prop("disabled", true).val("");
	$("#individual_scores").empty().hide();
	$("#submit").prop("disabled", true);
	score_type = null;

	if ($("#competition").val() !== null) {
		$.post(
			"scores.php?action=get_events",
			{
				competition_id: $("#competition").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (events) {
				events.forEach(function (event) {
					$("#event").append($("<option>").val(event.id).text(event.name).data("event_type", event.type));
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
	$("#score_points_value").prop("disabled", true).val("");
	$("#score_time_value").prop("disabled", true).val("");
	$("#score_errors_value").prop("disabled", true).val("");
	$("#individual_scores").empty().hide();
	$("#submit").prop("disabled", true);

	if ($("#year").val() !== null) {
		$.post(
			"scores.php?action=get_clubs",
			{
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
	$("#score_points_value").prop("disabled", true).val("");
	$("#score_time_value").prop("disabled", true).val("");
	$("#score_errors_value").prop("disabled", true).val("");
	$("#individual_scores").empty().hide();
	$("#submit").prop("disabled", true);

	if ($("#club").val() !== null && $("#competition").val() !== null) {
		$.post(
			"scores.php?action=get_teams",
			{
				club_id: $("#club").val(),
				competition_id: $("#competition").val(),
				_csrf_token: $("#csrf_token").val()
			},
			function (teams) {
				teams.forEach(function (team) {
					$("#team").append($("<option>").val(team.id).text(team.name));
				});
				if (score_type != "individual") {
					$("#team").prop("disabled", false);
				}
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the teams.");
		});
	}
}

function getScore() {
	$("#score_points_value").prop("disabled", true).val("");
	$("#score_time_value").prop("disabled", true).val("");
	$("#score_errors_value").prop("disabled", true).val("");
	$("#individual_scores").empty().hide();
	$("#submit").prop("disabled", true);

	switch (score_type) {
		case "points":
			if ($("#event").val() !== null && $("#team").val() !== null) {
				$.post(
					"scores.php?action=get_score",
					{
						team_id: $("#team").val(),
						event_id: $("#event").val(),
						_csrf_token: $("#csrf_token").val()
					},
					function (response) {
						$("#score_points_value").prop("disabled", false);
						if (response.points !== null) {
							$("#score_points_value").val(response.points);
						} else {
							$("#score_points_value").val("");
						}
						$("#submit").prop("disabled", false);
					}
				).fail(function () {
					alert("An error occurred getting the score.");
				});
			}
			break;
		case "timed":
			if ($("#event").val() !== null && $("#team").val() !== null) {
				$.post(
					"scores.php?action=get_score",
					{
						team_id: $("#team").val(),
						event_id: $("#event").val(),
						_csrf_token: $("#csrf_token").val()
					},
					function (response) {
						$("#score_time_value").prop("disabled", false);
						$("#score_errors_value").prop("disabled", false);
						if (response.time !== null) {
							$("#score_time_value").val(response.time);
						} else {
							$("#score_time_value").val("");
						}
						if (response.errors !== null) {
							$("#score_errors_value").val(response.errors);
						} else {
							$("#score_errors_value").val("");
						}
						$("#submit").prop("disabled", false);
					}
				).fail(function () {
					alert("An error occurred getting the score.");
				});
			}
			break;
		case "individual":
			if ($("#event").val() !== null && $("#club").val() !== null) {
				$.post(
					"scores.php?action=get_score",
					{
						club_id: $("#club").val(),
						event_id: $("#event").val(),
						_csrf_token: $("#csrf_token").val()
					},
					function (response) {
						response = {scores: {"1": {name: "foo", points: 10.0}, "2": {name: "bar", points: 20.0}, "3": {name: "baz", points: 30.0}}};
						Object.keys(response.scores).forEach(function (key) {
							$("#individual_scores").append($("<tr>").append($("<td colspan=\"2\">").append($("<hr />"))));
							$("#individual_scores").append($("<tr>").append($("<td>").text("Name:")).append($("<td>").append($("<input type=\"text\" />").data("score_entry_id", key).data("score_entry_field", "name").val(response.scores[key].name).keydown(function(){$("#status").text("");}).change(function(){$("#status").text("");}))));
							$("#individual_scores").append($("<tr>").append($("<td>").text("Points:")).append($("<td>").append($("<input type=\"number\" min=\"0\" step=\"0.01\" />").data("score_entry_id", key).data("score_entry_field", "points").val(response.scores[key].points).keydown(function () { $("#status").text(""); }).change(function () { $("#status").text(""); }))));
						});
						$("#individual_scores").append($("<tr>").append($("<td colspan=\"2\">").append($("<hr />"))));
						$("#individual_scores").append($("<tr>").append($("<td colspan=\"2\">").text("New entry:")));
						$("#individual_scores").append($("<tr>").append($("<td>").text("Name:")).append($("<td>").append($("<input type=\"text\" />").data("score_entry_id", "").data("score_entry_field", "name").keydown(function () { $("#status").text(""); }).change(function () { $("#status").text(""); }))));
						$("#individual_scores").append($("<tr>").append($("<td>").text("Points:")).append($("<td>").append($("<input type=\"number\" min=\"0\" step=\"0.01\" />").data("score_entry_id", "").data("score_entry_field", "points").keydown(function () { $("#status").text(""); }).change(function () { $("#status").text(""); }))));
						$("#individual_scores").show();
						$("#submit").prop("disabled", false);
					}
				).fail(function () {
					alert("An error occurred getting the score.");
				});
			}
			break;
	}
}

function setScore() {
	switch (score_type) {
		case "points":
			if ($("#event").val() !== null && $("#team").val() !== null) {
				$.post(
					"scores.php?action=set_score",
					{
						team_id: $("#team").val(),
						event_id: $("#event").val(),
						points: $("#score_points_value").val(),
						_csrf_token: $("#csrf_token").val()
					},
					function () {
						$("#status").text("Saved");
					},
					"text"
				).fail(function () {
					alert("An error occurred setting the score.");
				});
			}
			break;
		case "timed":
			if ($("#event").val() !== null && $("#team").val() !== null) {
				$.post(
					"scores.php?action=set_score",
					{
						team_id: $("#team").val(),
						event_id: $("#event").val(),
						time: $("#score_time_value").val(),
						errors: $("#score_errors_value").val(),
						_csrf_token: $("#csrf_token").val()
					},
					function () {
						$("#status").text("Saved");
					},
					"text"
				).fail(function () {
					alert("An error occurred setting the score.");
				});
			}
			break;
		case "individual":
			if ($("#event").val() !== null && $("#club").val() !== null) {
				var scores = {};
				$("#individual_scores>tr>td>input").each(function () {
					if (scores[$(this).data("score_entry_id")] === undefined) {
						scores[$(this).data("score_entry_id")] = {};
					}
					scores[$(this).data("score_entry_id")][$(this).data("score_entry_field")] = $(this).val();
				});
				$.post(
					"scores.php?action=set_score",
					{
						club_id: $("#club").val(),
						event_id: $("#event").val(),
						scores: JSON.stringify(scores),
						_csrf_token: $("#csrf_token").val()
					},
					function () {
						$("#status").text("Saved");
					},
					"text"
				)
			}
			break;
	}
}
