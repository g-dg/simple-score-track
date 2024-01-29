"use strict"

var score_type = null;

$(function () {
	$("#score_form").submit(function (e) {
		e.preventDefault();
		setScore();
	});

	$("#year").change(function () {
		getCompetitions();
		getClubs();
	});

	$("#competition").change(function () {
		getEvents();
		getTeams();
	});

	$("#event").change(function () {
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
		getTeams();
		if (score_type == "individual") {
			getScore();
		}
	});

	$("#team").change(function () {
		getScore();
	});

	$(document).on("ajaxError", function () {
		alert("An error occurred.");
		$("#status").text("An error occurred!");
		$("#status").addClass("error");
	});

	$("#score_points_value").on("input", function() {
		$("#status").text("*** Unsaved changes! ***");
		$("#status").removeClass("error");
	});
	$("#score_time_value").on("input", function() {
		$("#status").text("*** Unsaved changes! ***");
		$("#status").removeClass("error");
	});
	$("#score_errors_value").on("input", function() {
		$("#status").text("*** Unsaved changes! ***");
		$("#status").removeClass("error");
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
	$("#status").text("Loading...");
	$("#status").removeClass("error");
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
				$("#status").text("Ready.");
				$("#status").removeClass("error");
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the competitions!");
			$("#status").text("An error occurred getting the competitions!");
			$("#status").addClass("error");
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
	$("#status").text("Loading...");
	$("#status").removeClass("error");
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
				$("#status").text("Ready.");
				$("#status").removeClass("error");
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the events!");
			$("#status").text("An error occurred getting the events!");
			$("#status").addClass("error");
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
	$("#status").text("Loading...");
	$("#status").removeClass("error");

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
				$("#status").text("Ready.");
				$("#status").removeClass("error");
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the clubs!");
			$("#status").text("An error occurred getting the clubs!");
			$("#status").addClass("error");
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
	$("#status").text("Loading...");
	$("#status").removeClass("error");

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
				$("#status").text("Ready.");
				$("#status").removeClass("error");
			},
			"json"
		).fail(function () {
			alert("An error occurred getting the teams!");
			$("#status").text("An error occurred getting the teams!");
			$("#status").addClass("error");
		});
	}
}

function getScore() {
	$("#score_points_value").prop("disabled", true).val("");
	$("#score_time_value").prop("disabled", true).val("");
	$("#score_errors_value").prop("disabled", true).val("");
	$("#individual_scores").empty().hide();
	$("#submit").prop("disabled", true);
	$("#status").text("Loading...");
	$("#status").removeClass("error");

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
						$("#status").text("Ready.");
						$("#status").removeClass("error");
					}
				).fail(function () {
					alert("An error occurred getting the score!");
					$("#status").text("An error occurred getting the score!");
					$("#status").addClass("error");
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
							var time_minutes = Math.floor(response.time / 60);
							var time_seconds = response.time - (time_minutes * 60);
							time_seconds = ("00" + time_seconds.toFixed(3)).slice(-6);
							if (time_minutes == 0) {
								var time = time_seconds + "";
							} else {
								var time = time_minutes + ":" + time_seconds;
							}
							$("#score_time_value").val(time);
						} else {
							$("#score_time_value").val("");
						}
						if (response.errors !== null) {
							$("#score_errors_value").val(response.errors);
						} else {
							$("#score_errors_value").val("");
						}
						$("#submit").prop("disabled", false);
						$("#status").text("Ready.");
						$("#status").removeClass("error");
					}
				).fail(function () {
					alert("An error occurred getting the score!");
					$("#status").text("An error occurred getting the score!");
					$("#status").addClass("error");
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
						Object.keys(response.scores).forEach(function (key) {
							$("#individual_scores").append($("<tr>").append($("<td colspan=\"2\">").append($("<hr />"))));
							$("#individual_scores").append($("<tr>").append($("<td>").text("Name:")).append($("<td>").append($("<input type=\"text\" maxlength=\"250\" />").data("score_entry_id", key).data("score_entry_field", "name").val(response.scores[key].name))));
							$("#individual_scores").append($("<tr>").append($("<td>").text("Points:")).append($("<td>").append($("<input type=\"number\" min=\"0\" step=\"0.01\" />").data("score_entry_id", key).data("score_entry_field", "points").val(response.scores[key].points))));
						});
						$("#individual_scores").append($("<tr>").append($("<td colspan=\"2\">").append($("<hr />"))));
						$("#individual_scores").append($("<tr>").append($("<td colspan=\"2\">").text("New entry:")));
						$("#individual_scores").append($("<tr>").append($("<td>").text("Name:")).append($("<td>").append($("<input type=\"text\" maxlength=\"250\" />").data("score_entry_id", "").data("score_entry_field", "name"))));
						$("#individual_scores").append($("<tr>").append($("<td>").text("Points:")).append($("<td>").append($("<input type=\"number\" min=\"0\" step=\"0.01\" />").data("score_entry_id", "").data("score_entry_field", "points"))));
						$("#individual_scores").show();
						$("#submit").prop("disabled", false);
						$("#status").text("Ready.");
						$("#status").removeClass("error");
					}
				).fail(function () {
					alert("An error occurred getting the score!");
					$("#status").text("An error occurred getting the score!");
					$("#status").addClass("error");
				});
			}
			break;
	}
}

function setScore() {
	$("#status").text("Saving...");
	$("#status").removeClass("error");

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
						$("#status").text("Saved.");
						$("#status").removeClass("error");
						getScore();
					},
					"text"
				).fail(function () {
					alert("An error occurred setting the score!");
					$("#status").text("An error occurred setting the score!");
					$("#status").addClass("error");
				});
			}
			break;
		case "timed":
			if ($("#event").val() !== null && $("#team").val() !== null) {
				var time_raw = $("#score_time_value").val();
				if (time_raw !== "") {
					var time_minutes = time_raw.indexOf(":") != -1 ? parseInt(time_raw.split(":")[0]) : 0;
					var time_seconds = time_raw.indexOf(":") != -1 ? parseFloat(time_raw.split(":")[1]) : parseFloat(time_raw);
					if (time_minutes === NaN) {
						time_minutes = 0;
					}
					if (time_seconds === NaN) {
						time_seconds = 0;
					}
					var time = (time_minutes * 60) + time_seconds;
				} else {
					var time = "";
				}
				$.post(
					"scores.php?action=set_score",
					{
						team_id: $("#team").val(),
						event_id: $("#event").val(),
						time,
						errors: $("#score_errors_value").val(),
						_csrf_token: $("#csrf_token").val()
					},
					function () {
						$("#status").text("Saved.");
						$("#status").removeClass("error");
						getScore();
					},
					"text"
				).fail(function () {
					alert("An error occurred setting the score!");
					$("#status").text("An error occurred setting the score!");
					$("#status").addClass("error");
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
						$("#status").text("Saved.");
						$("#status").removeClass("error");
						getScore();
					},
					"text"
				)
			}
			break;
	}
}
