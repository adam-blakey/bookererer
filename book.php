<?php include_once($_SERVER['DOCUMENT_ROOT']."/includes/kernel.php"); ?>
<?php $db_connection = db_connect(); ?>

<?php
	$sort_options = [
    "datetime" => "Date",
    "booking_ensemble" => "Ensemble",
    "name" => "Name",
    "location" => "Location",
    "status" => "Status"
  ];

  $sort_directions = [
    "DESC" => "Desc.",
    "ASC" => "Asc."
  ];

  $sort  = isset($_GET["sort"]) ?htmlspecialchars($_GET["sort"]) :$sort_options[0];
  $order = isset($_GET["order"])?htmlspecialchars($_GET["order"]):$sort_directions[0];

  if (!in_array($sort, array_keys($sort_options)))
  {
    $sort = array_keys($sort_options)[0];
  }
  if (!in_array($order, array_keys($sort_directions)))
  {
    $order = array_keys($sort_directions)[0];
  }
?>

<?php

	function output_booking($booking, $db_connection)
	{
    // TODO: I know this is terrible programming. Please shoot me.
    $keiron_logo = "https://keironanderson.co.uk/wp-content/uploads/2020/09/keiron_anderson_24_feb.jpg";

    $booking_datetime = new DateTime();
    $booking_datetime->setTimestamp($booking["datetime"]);
    $booking_datetime->setTimezone(new DateTimeZone("Europe/London"));

    $booking_id = $booking["booking_ID"];
    $booking_name = $booking["name"];
    $status = $booking["status"];
    $booking_location = $booking["location"];
    $last_updated = FindTimeAgo($booking["updated_datetime"]);

    $ensemble_query = $db_connection->prepare("SELECT `name`, `logo` FROM logins WHERE id = ?;");
    $ensemble_query->bind_param("i", $booking["booking_ensemble"]);
    $ensemble_query->execute();
    $ensemble = $ensemble_query->get_result()->fetch_assoc();
    $ensemble_name = $ensemble["name"];
    $ensemble_logo = $ensemble["logo"];

    $first_status_query = $db_connection->prepare("SELECT a.* FROM `bookings` a INNER JOIN (SELECT `booking_ID`, min(`status`) `status` FROM `bookings` WHERE `deleted`=0 GROUP BY `booking_ID`) b USING(`booking_ID`, `status`) WHERE `booking_ID`=? ORDER BY `status` ASC LIMIT 1");
    $first_status_query->bind_param("i", $booking_id);
    $first_status_query->execute();
    $first_created_result = $first_status_query->get_result()->fetch_assoc()["updated_datetime"];

    $first_created_datetime = new DateTime();
    $first_created_datetime->setTimestamp($first_created_result);
    $first_created_datetime->setTimezone(new DateTimeZone("Europe/London"));
    $first_created = $first_created_datetime->format("Y-m-d H:i:s");

    // $first_created = "2020-12-01";

    // $ensemble_name = "NSWO";
    // $ensemble_logo = "https://attendance.nsw.org.uk/uploads/ensemble-logos/nswo/NSWO%20social%20icon%20RGB-16.jpg";

    // Items dependend on status.
		$status_responses = [
			0 => "Ensemble created booking",
			1 => "Ensemble submitted booking to Keiron",
			2 => "Keiron rejected booking",
			3 => "Keiron accepted booking",
			4 => "Ensemble confirmed final details",
			5 => "Ensemble cancelled booking"
		];
	
		$waiting_for = [
			0 => "Ensemble",
			1 => "Keiron",
			2 => "-",
			3 => "Ensemble",
			4 => "-",
			5 => "-"
		];

    $green_option = [
      "",
      "Accept",
      "",
      "",
      "",
      ""
    ];

    $red_option = [
      "",
      "Decline",
      "",
      "",
      "Cancel booking",
      ""
    ];

    $blue_option = [
      "Submit to Keiron",
      "",
      "",
      "Confirm final details",
      "",
      ""
    ];

		?>
		<tr class="<?=booking_viewable($booking["booking_ensemble"])?"opacity-100":"opacity-50";?>">
      <td>
        <?php 
          if (booking_restricted($booking["booking_ensemble"], $booking["status"]) && !($green_option[$status] == "" && $red_option[$status] == "" && $blue_option[$status] == "") && $status != 4)
          {
            ?>
            <div class="badge bg-primary" title="Status code: <?=$status;?>"></div>
            <?php
          }
        ?>
      </td>
			<td>
				<span class="avatar"
					style="background-image: url('<?=$ensemble_logo;?>')"
					title="<?=$ensemble_name;?>"></span>
			</td>
      <td>
				<?=$booking_name;?>
			</td>
			<td>
      <?=get_human_date_range($booking["datetime"], $booking["datetime_end"]);?>
				<div class="mt-n1">
					<a href="#" data-bs-toggle="modal" data-bs-target="#add-to-calendar_<?=$booking_id;?>">Add to calendar</a>
				</div>
			</td>
			<td>
				<?=$booking_location;?>
				<div class="mt-n1">
					<a href="https://www.google.com/maps/dir/?api=1&destination=<?=urlencode($booking_location);?>" target="_blank">Get directions</a>
				</div>
			</td>
			<td>
				<span class="text-muted"><?=$first_created;?></span>
			</td>
			<td>
				<?=$status_responses[$status];?>
				<div class="text-muted mt-nl"><?=$last_updated;?></span>
			</td>
			<td>
				<?=get_steps($status, $ensemble_logo, $keiron_logo);?>
			</td>
      <td>
        <div class="text-muted mt-nl text-center">
          <span class="badge bg-orange badge-pill ms-2" style="font-size: 1.2em;"><?=$status;?></span>
        </div>
      </td>
			<td>
				<span class="text-muted">
					<?=$waiting_for[$status];?>
				</span>
			</td>
      <td>
        <?php
          if (booking_restricted($booking["booking_ensemble"], $booking["status"]))
          {
            if ($green_option[$status] != "")
            {
              ?>
              <a href="#" class="btn btn-success w-40" data-bs-toggle="modal" data-bs-target="#add-booking" data-bs-backdrop="static" onclick="loadBooking(<?=$booking_id;?>, <?=$booking['status'];?>, 1)"><?=$green_option[$status];?></a>
              <?php
            }

            if ($red_option[$status] != "")
            {
              ?>
              <a href="#" class="btn btn-danger w-40" data-bs-toggle="modal" data-bs-target="#add-booking" data-bs-backdrop="static" onclick="loadBooking(<?=$booking_id;?>, <?=$booking['status'];?>, 0)"><?=$red_option[$status];?></a>
              <?php
            }

            if ($blue_option[$status] != "")
            {
              ?>
              <a href="#" class="btn btn-primary w-40" data-bs-toggle="modal" data-bs-target="#add-booking" data-bs-backdrop="static" onclick="loadBooking(<?=$booking_id;?>, <?=$booking['status'];?>, -1)"><?=$blue_option[$status];?></a>
              <?php
            }

            if ($green_option[$status] == "" && $red_option[$status] == "" && $blue_option[$status] == "")
            {
              ?>
              <span class="text-muted">-</span>
              <?php
            }
            
          }
          else
          {
            echo "-";
          }
        ?>
      </td>
		</tr>

    <?php
      $booking_datetime = new DateTime("now", new DateTimeZone("Europe/London"));
      $booking_datetime->setTimestamp($booking["datetime"]);

      $booking_datetime_end = new DateTime("now", new DateTimeZone("Europe/London"));
      $booking_datetime_end->setTimestamp($booking["datetime_end"]);
    ?>

    <div class="modal modal-blur fade" id="add-to-calendar_<?=$booking_id;?>" tabindex="-1" style="display: none;" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <div class="modal-title">Add to calendar</div>
            <div class="row g-2 align-items-center">
              <div class="col-6 col-sm-4 col-md-2 col-xl-auto py-3">
                <a target="_blank" href="https://calendar.google.com/calendar/render?action=TEMPLATE&dates=<?=$booking_datetime->format("Ymd\THisZ");?>%2F<?=$booking_datetime_end->format("Ymd\THisZ");?>&details=Generated%20automatically%20by%20bookings.keironanderson.co.uk.&location=<?=($booking["location"]);?>&text=<?=urlencode($booking["name"]);?>" class="btn w-100 btn-icon" aria-label="Google Calendar" style="color: #ffffff; background-color: #3f7ee8;" onclick="$('#add-to-calendar_<?=$booking_id;?>').modal('hide')">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-google" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M17.788 5.108a9 9 0 1 0 3.212 6.892h-8"></path></svg>
                </a>
              </div>
              <div class="col-6 col-sm-4 col-md-2 col-xl-auto py-3">
                <a target="_blank" href="https://outlook.live.com/calendar/0/deeplink/compose?body=Generated%20automatically%20by%20bookings.keironanderson.co.uk.&enddt=<?=urlencode($booking_datetime_end->format("Y-m-d\TH:i:s+00:00"));?>&location=<?=($booking["location"]);?>&path=%2Fcalendar%2Faction%2Fcompose&rru=addevent&startdt=<?=urlencode($booking_datetime->format("Y-m-d\TH:i:s+00:00"));?>&subject=<?=urlencode($booking["name"]);?>" class="btn w-100 btn-icon" aria-label="Outlook" style="color: #ffffff; background-color: #1175cc;">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mail" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"></path><path d="M3 7l9 6l9 -6"></path></svg>
                </a>
              </div>
              <div class="col-6 col-sm-4 col-md-2 col-xl-auto py-3">
                <a target="_blank" href="https://calendar.yahoo.com/?desc=Generated%20automatically%20by%20bookings.keironanderson.co.uk.&et=<?=urlencode($booking_datetime_end->format("Ymd\THisZ"));?>&in_loc=<?=($booking["location"]);?>&st=<?=urlencode($booking_datetime->format("Ymd\THisZ"));?>&title=<?=urlencode($booking["name"]);?>&v=60" class="btn w-100 btn-icon" aria-label="Yahoo" style="color: #ffffff; background-color: #5b00c8;">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-yahoo" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 6l5 0"></path><path d="M7 18l7 0"></path><path d="M4.5 6l5.5 7v5"></path><path d="M10 13l6 -5"></path><path d="M12.5 8l5 0"></path><path d="M20 11l0 4"></path><path d="M20 18l0 .01"></path></svg>
                </a>
              </div>
              <div class="col-6 col-sm-4 col-md-2 col-xl-auto py-3">
                <a target="_blank" href="https://outlook.office.com/calendar/0/deeplink/compose?body=Generated%20automatically%20by%20bookings.keironanderson.co.uk.&enddt=<?=urlencode($booking_datetime_end->format("Y-m-d\TH:i:s+00:00"));?>&location=<?=($booking["location"]);?>&path=%2Fcalendar%2Faction%2Fcompose&rru=addevent&startdt=<?=urlencode($booking_datetime->format("Y-m-d\TH:i:s+00:00"));?>&subject=<?=urlencode($booking["name"]);?>" class="btn w-100 btn-icon" aria-label="Office365" style="color: #ffffff; background-color: #cc3802;">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-office" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M4 18h9v-12l-5 2v5l-4 2v-8l9 -4l7 2v13l-7 3z"></path></svg>
                </a>
              </div>
              <div class="col-6 col-sm-4 col-md-2 col-xl-auto py-3">
                <a target="_blank" href="data:text/calendar;charset=utf8,BEGIN:VCALENDAR%0AVERSION:2.0%0ABEGIN:VEVENT%0ADTSTART:<?=$booking_datetime->format("Ymd\THisZ");?>%0ADTEND:<?=$booking_datetime_end->format("Ymd\THisZ");?>%0ASUMMARY:<?=$booking["name"];?>%0ADESCRIPTION:Generated%20automatically%20by%20bookings.keironanderson.co.uk.%0ALOCATION:<?=$booking["location"];?>%0AEND:VEVENT%0AEND:VCALENDAR%0A" class="btn w-100 btn-icon bg-success" aria-label="ICS" style="color: #ffffff;">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path><path d="M7 11l5 5l5 -5"></path><path d="M12 4l0 12"></path></svg>
                </a>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
		<?php
	}
?>

<?php
if (login_valid())
{
	?>
<!doctype html>
<html lang="en">

<head>
  <?php include($_SERVER['DOCUMENT_ROOT']."/includes/head.php"); ?>
  <meta name="robots" content="noindex,nofollow">
  <title><?=$title;?></title>
  <script>
    String.prototype.decodeHTML = function() {
      var map = {"gt":">" /* , … */};
      return this.replace(/&(#(?:x[0-9a-f]+|\d+)|[a-z]+);?/gi, function($0, $1) {
          if ($1[0] === "#") {
              return String.fromCharCode($1[1].toLowerCase() === "x" ? parseInt($1.substr(2), 16)  : parseInt($1.substr(1), 10));
          } else {
              return map.hasOwnProperty($1) ? map[$1] : $0;
          }
      });
    };

    var bookingFormSubmitText = [];
    bookingFormSubmitText[-1] = "Create draft booking";
    bookingFormSubmitText[0]  = "Send booking to Keiron";
    bookingFormSubmitText[1]  = "";
    bookingFormSubmitText[2]  = "";
    bookingFormSubmitText[3]  = "Confirm final details";
    bookingFormSubmitText[4]  = "";
    bookingFormSubmitText[5]  = "";

    var bookingFormSubmitIcon = [];
    bookingFormSubmitIcon[-1] = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg>';
    bookingFormSubmitIcon[0]  = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-send" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 14l11 -11"></path><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5"></path></svg>';
    bookingFormSubmitIcon[1]  = "";
    bookingFormSubmitIcon[2]  = "";
    bookingFormSubmitIcon[3]  = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checks" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M7 12l5 5l10 -10"></path><path d="M2 12l5 5m5 -5l5 -5"></path></svg>';
    bookingFormSubmitIcon[4]  = "";
    bookingFormSubmitIcon[5]  = "";

    var acceptIcon  = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M9 12l2 2l4 -4"></path></svg>';
    var rejectIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-ban" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M5.7 5.7l12.6 12.6"></path></svg>';

    function modalUpdate(booking_id, booking_status, booking_date, booking_time, booking_date_end, booking_time_end, booking_name, booking_location, booking_ensemble_id, accept_reject)
    {
      // accept_reject:
      //  -1: neither
      //   0: reject
      //   1: accept

      document.getElementById("add-booking-error").style.display = "none";
      document.getElementById("add-booking-error").innerHTML = "";

      document.getElementById("booking-id").value       = booking_id;
      document.getElementById("booking-status").value   = booking_status;
      document.getElementById("booking-date").value     = booking_date;
      document.getElementById("booking-time").value     = booking_time;
      document.getElementById("booking-date-end").value = booking_date_end;
      document.getElementById("booking-time-end").value = booking_time_end;
      document.getElementById("booking-name").value     = booking_name;
      document.getElementById("booking-location").value = booking_location;
      document.getElementById("clash-agreed").value     = 0;

      document.getElementById("booking-date").disabled     = false;
      document.getElementById("booking-time").disabled     = false;
      document.getElementById("booking-date-end").disabled = false;
      document.getElementById("booking-time-end").disabled = false;
      document.getElementById("booking-name").disabled     = false;
      document.getElementById("booking-location").disabled = false;
      document.getElementById("ensemble-id").disabled      = false;

      document.getElementById("add-booking-info").style.display = "block";

      document.getElementById("ensemble-id").value = booking_ensemble_id;

      document.getElementById("submit-add-booking").classList.remove("disabled");

      if (accept_reject == -1 && booking_status == 3)
      {
        document.getElementById("submit-add-booking").classList.remove("btn-success");
        document.getElementById("submit-add-booking").classList.remove("btn-danger");
        document.getElementById("submit-add-booking").classList.add("btn-primary");

        document.getElementById("add-booking-status").classList.remove("bg-success");
        document.getElementById("add-booking-status").classList.remove("bg-danger");
        document.getElementById("add-booking-status").classList.add("bg-primary");

        document.getElementById("submit-add-booking").innerHTML = bookingFormSubmitIcon[booking_status] + bookingFormSubmitText[booking_status];
        document.getElementById("add-booking-title") .innerHTML = bookingFormSubmitText[booking_status];

        document.getElementById("booking-date").disabled = true;
        document.getElementById("booking-date-end").disabled = true;
        document.getElementById("ensemble-id").disabled  = true;

        document.getElementById("booking-status-new").value = parseInt(booking_status) + 1;
      }
      else if (accept_reject == -1)
      {
        document.getElementById("submit-add-booking").classList.remove("btn-success");
        document.getElementById("submit-add-booking").classList.remove("btn-danger");
        document.getElementById("submit-add-booking").classList.add("btn-primary");

        document.getElementById("add-booking-status").classList.remove("bg-success");
        document.getElementById("add-booking-status").classList.remove("bg-danger");
        document.getElementById("add-booking-status").classList.add("bg-primary");

        document.getElementById("submit-add-booking").innerHTML = bookingFormSubmitIcon[booking_status] + bookingFormSubmitText[booking_status];
        document.getElementById("add-booking-title") .innerHTML = bookingFormSubmitText[booking_status];

        document.getElementById("booking-status-new").value = parseInt(booking_status) + 1;
      }
      else if (accept_reject == 0 && booking_status == 1)
      {
        document.getElementById("submit-add-booking").classList.remove("btn-success");
        document.getElementById("submit-add-booking").classList.remove("btn-primary");
        document.getElementById("submit-add-booking").classList.add("btn-danger");
        
        document.getElementById("add-booking-status").classList.remove("bg-success");
        document.getElementById("add-booking-status").classList.remove("bg-primary");
        document.getElementById("add-booking-status").classList.add("bg-danger");

        document.getElementById("submit-add-booking").innerHTML = rejectIcon + "Decline booking";
        document.getElementById("add-booking-title") .innerHTML = "Decline booking";

        document.getElementById("booking-status-new").value = 2;

        document.getElementById("booking-date").disabled     = true;
        document.getElementById("booking-time").disabled     = true;
        document.getElementById("booking-date-end").disabled = true;
        document.getElementById("booking-time-end").disabled = true;
        document.getElementById("booking-name").disabled     = true;
        document.getElementById("booking-location").disabled = true;
        document.getElementById("ensemble-id").disabled      = true;

        document.getElementById("add-booking-info").style.display = "none";
      }
      else if (accept_reject == 0 && booking_status == 4)
      {
        document.getElementById("submit-add-booking").classList.remove("btn-success");
        document.getElementById("submit-add-booking").classList.remove("btn-primary");
        document.getElementById("submit-add-booking").classList.add("btn-danger");
        
        document.getElementById("add-booking-status").classList.remove("bg-success");
        document.getElementById("add-booking-status").classList.remove("bg-primary");
        document.getElementById("add-booking-status").classList.add("bg-danger");

        document.getElementById("submit-add-booking").innerHTML = rejectIcon + "Cancel booking";
        document.getElementById("add-booking-title") .innerHTML = "Cancel booking";

        document.getElementById("booking-status-new").value = 5;

        document.getElementById("booking-date").disabled     = true;
        document.getElementById("booking-time").disabled     = true;
        document.getElementById("booking-date-end").disabled = true;
        document.getElementById("booking-time-end").disabled = true;
        document.getElementById("booking-name").disabled     = true;
        document.getElementById("booking-location").disabled = true;
        document.getElementById("ensemble-id").disabled      = true;

        document.getElementById("add-booking-info").style.display = "none";
      }
      else if (accept_reject == 1)
      {
        document.getElementById("submit-add-booking").classList.remove("btn-danger");
        document.getElementById("submit-add-booking").classList.remove("btn-primary");
        document.getElementById("submit-add-booking").classList.add("btn-success");

        document.getElementById("add-booking-status").classList.remove("bg-danger");
        document.getElementById("add-booking-status").classList.remove("bg-primary");
        document.getElementById("add-booking-status").classList.add("bg-success");

        document.getElementById("submit-add-booking").innerHTML = acceptIcon + "Accept booking";
        document.getElementById("add-booking-title") .innerHTML = "Accept booking";

        document.getElementById("booking-status-new").value = 3;        

        document.getElementById("booking-date").disabled     = true;
        document.getElementById("booking-time").disabled     = true;
        document.getElementById("booking-date-end").disabled = true;
        document.getElementById("booking-time-end").disabled = true;
        document.getElementById("booking-name").disabled     = true;
        document.getElementById("booking-location").disabled = true;
        document.getElementById("ensemble-id").disabled      = true;

        document.getElementById("add-booking-info").style.display = "none";
      }

      document.getElementById("submit-add-booking").disabled = false;
    }

    function modalSuccess()
    {
      document.getElementById("add-booking-error").style.display = "none";
      document.getElementById("add-booking-error").innerHTML = "";

      document.getElementById("submit-add-booking").classList.remove("btn-primary");
      document.getElementById("submit-add-booking").classList.add("btn-success");
      document.getElementById("submit-add-booking").innerHTML  = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M5 12l5 5l10 -10"></path></svg>';
      document.getElementById("submit-add-booking").innerHTML += 'Created!';

      location.reload();
    }

    function modalError(status, error_message)
    {
      document.getElementById("submit-add-booking").classList.remove("disabled");
      document.getElementById("submit-add-booking").classList.remove("btn-success");
      document.getElementById("submit-add-booking").classList.add("btn-primary");
      document.getElementById("submit-add-booking").innerHTML = bookingFormSubmitIcon[status] + bookingFormSubmitText[status];

      document.getElementById("add-booking-error").style.display = "block";
      document.getElementById("add-booking-error").innerHTML  = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-exclamation-circle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M12 9v4"></path><path d="M12 16v.01"></path></svg>';
      document.getElementById("add-booking-error").innerHTML += 'Error: ' + error_message;
    }

    function submitBooking()
    {
      document.getElementById("submit-add-booking").innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating...';
      document.getElementById("submit-add-booking").classList.add("disabled");
      document.getElementById("submit-add-booking").classList.remove("btn-success");
      document.getElementById("submit-add-booking").classList.add("btn-primary");

      var xhttp = new XMLHttpRequest();

      xhttp.open("POST", "<?=$config['base_url'];?>/api/v1/add_booking.php", true);
      xhttp.timeout = 5000;
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

      xhttp.send(
        "booking-name="        + document.getElementById("booking-name").value +
        "&ensemble-id="        + document.getElementById("ensemble-id").value + 
        "&booking-date="       + document.getElementById("booking-date").value +
        "&booking-time="       + document.getElementById("booking-time").value +
        "&booking-date-end="   + document.getElementById("booking-date-end").value +
        "&booking-time-end="   + document.getElementById("booking-time-end").value +
        "&booking-location="   + document.getElementById("booking-location").value +
        "&session-id="         + document.getElementById("session-id").value + 
        "&booking-id="         + document.getElementById("booking-id").value +
        "&booking-status="     + document.getElementById("booking-status").value +
        "&booking-status-new=" + document.getElementById("booking-status-new").value + 
        "&clash-agreed="       + document.getElementById("clash-agreed").value
      );

      var status = document.getElementById("booking-status").value;

      xhttp.onload = function () {
        try {
          var JSON_response = JSON.parse(this.responseText); 
        } catch (error) {
          var JSON_response = {"status": "error", "error_message": "Invalid JSON response from server."};
        }

        console.log(JSON_response);

        if (JSON_response.status == "warning") {
          modalError(status, "<strong>there is an existing booking on the same day</strong> by " + JSON_response.clash_ensemble_name + " between " + JSON_response.clash_datetime_range + " titled " + JSON_response.clash_name + ".\n<strong>Please submit again if you intend to ignore this warning</strong>.");
          document.getElementById("clash-agreed").value = 1;
        }
        else if (JSON_response.status == "success") {
          modalSuccess();
        }
        else {
          modalError(status, JSON_response.error_message);
        }

      };

      xhttp.onabort = function (e) {
        modalError(status, "Request aborted.");
      };

      xhttp.onerror = function (e) {
        modalError(status, "An unknown error occured.");
      }

      xhttp.ontimeout = function (e) {
        modalError(status, "Creation timed out.");
      };
    }

    function setToNewBooking() {
      modalUpdate('not yet created', -1, '', '', '', '', '', '', '');
    }

    function loadBooking(booking_id, status, accept_reject = -1) {
      session_id = document.getElementById("session-id").value;

      var xhttp = new XMLHttpRequest();

      xhttp.open("POST", "<?=$config['base_url'];?>/api/v1/get_booking.php", true);
      xhttp.timeout = 5000;
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

      xhttp.send(
        "booking-id="  + booking_id + 
        "&session-id=" + session_id
      );

      xhttp.onload = function () {
        try {
          var JSON_response = JSON.parse(this.responseText); 
        } catch (error) {
          var JSON_response = {"status": "error", "error_message": "Invalid JSON response from server."};
        }

        console.log(JSON_response);

        if (JSON_response.status == "success") {
          modalUpdate(booking_id, JSON_response.booking_status, JSON_response.booking_date, JSON_response.booking_time, JSON_response.booking_date_end, JSON_response.booking_time_end, JSON_response.booking_name.decodeHTML(), JSON_response.booking_location.decodeHTML(), JSON_response.booking_ensemble_id, accept_reject);
        }
        else {
          modalError(status, JSON_response.error_message);
        }
      };

      xhttp.onerror = function (e) {
        modalError(status, "An unknown error occured.");
      }

      xhttp.ontimeout = function (e) {
        modalError(status, "Loading timed out. Try again.");
      };
    }
  </script>
</head>

<body>
  <div class="wrapper">
    <?php include($_SERVER['DOCUMENT_ROOT']."/includes/header.php"); ?>
    <?php include($_SERVER['DOCUMENT_ROOT']."/includes/navigation.php"); ?>

    <div class="page-wrapper">
      <div class="page-body">
        <div class="container-xl">

          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Book Keiron for events</h3>
                <div class="card-actions" style="float: right;">
                  <a href="#" data-bs-toggle="modal" data-bs-target="#add-booking" data-bs-backdrop="static" onclick="setToNewBooking()" class="btn btn-primary ms-auto my-2">Add booking</a>
                </div>
              </div>
              <div class="card-body border-bottom py-3 col-form-label">
                <div class="ms-auto text-muted">
                  <form method="get" action="" id="form-sort">
                    <div class="ms-2 d-inline-block">
                      <select class="form-select" name="sort" form="form-sort">
                        <?php
                          foreach ($sort_options as $value => $text)
                          {
                            ?>
                            <option value="<?=$value;?>" <?php if ($value == $sort) { echo "selected"; } ?>><?=$text;?></option>
                            <?php
                          }
                        ?>
                      </select>
                    </div>
                    <div class="ms-2 d-inline-block">
                      <select class="form-select" name="order" form="form-sort">
                        <?php
                          foreach ($sort_directions as $value => $text)
                          {
                            ?>
                            <option value="<?=$value;?>" <?php if ($value == $order) { echo "selected"; } ?>><?=$text;?></option>
                            <?php
                          }
                        ?>
                      </select>
                    </div>
                    <div class="ms-2 d-inline-block">
                      <button type="submit" class="btn btn-warning ms-auto my-2">Change sort</button>
                    </div>
                  </form>
                </div>

                <div class="modal modal-blur fade" id="add-booking" tabindex="-1" style="display: none;" aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div id="add-booking-status" class="modal-status bg-primary"></div>
                      <form id="form-add-booking">
                        <div class="modal-header">
                          <h5 class="modal-title" id="add-booking-title">Add booking</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="add-booking-error" style="display:none;">
                        </div>
                        <div class="modal-body" id="add-booking-info" style="display:block;">
                          Please feel free to amend any details.
                        </div>
                        <div class="modal-body">
                          <div class="row">
                            <div class="mb-3">
                              <label class="form-label">Booking ID</label>
                              <input type="text" class="form-control" name="booking-id" id="booking-id" value="" disabled>
                            </div>
                          </div>
                          <div class="row">
                            <div class="mb-3">
                              <label class="form-label required">Booking name</label>
                              <input type="text" class="form-control" name="booking-name" id="booking-name" placeholder="Your booking name" required>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-lg-12">
                              <div class="mb-3">
                                <label class="form-label required">Ensemble</label>
                                <select class="form-select" name="ensemble-id" id="ensemble-id" required>
                                  <?php
                                    $ensembles_query = $db_connection->prepare("SELECT * FROM logins WHERE `is_ensemble` = 1 OR `user_level` = 2 ORDER BY `name` ASC");
                                    $ensembles_query->execute();
                                    $ensembles_result = $ensembles_query->get_result();

                                    $user_level_and_id = get_user_level_and_id();

                                    // TODO: This needs fixing!!! Selecting wrong ensemble currently.
                                    while($ensemble = $ensembles_result->fetch_assoc())
                                    {
                                      ?>
                                      <option value="<?=$ensemble["ID"];?>" <?=($user_level_and_id["user_id"] == $ensemble["ID"])?"selected":"";?> <?=(($user_level_and_id["user_id"] != $ensemble["ID"]) && ($user_level_and_id["user_level"] == 1))?"disabled":"";?>><?=$ensemble['name'];?></option>
                                      <?php
                                    }

                                  ?>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-lg-6">
                              <div class="mb-3">
                                <label class="form-label required">Start event date</label>
                                <div class="input-icon">
                                  <input type="text" name="booking-date" id="booking-date" class="form-control" placeholder="Select a date" value="" style="min-width: 150px;" required>
                                  <span class="input-icon-addon"><!-- Download SVG icon from http://tabler-icons.io/i/calendar -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><rect x="4" y="5" width="16" height="16" rx="2"></rect><line x1="16" y1="3" x2="16" y2="7"></line><line x1="8" y1="3" x2="8" y2="7"></line><line x1="4" y1="11" x2="20" y2="11"></line><line x1="11" y1="15" x2="12" y2="15"></line><line x1="12" y1="15" x2="12" y2="18"></line></svg>
                                  </span>
                                </div>
                              </div>
                            </div>
                            <div class="col-lg-6">
                              <div class="mb-3">
                                <label class="form-label required">Start event time</label>
                                <input type="time" name="booking-time" id="booking-time" class="form-control" autocomplete="off" value="" required>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-lg-6">
                              <div class="mb-3">
                                <label class="form-label required">End event date</label>
                                <div class="input-icon">
                                  <input type="text" name="booking-date-end" id="booking-date-end" class="form-control" placeholder="Select a date" value="" style="min-width: 150px;" required>
                                  <span class="input-icon-addon"><!-- Download SVG icon from http://tabler-icons.io/i/calendar -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><rect x="4" y="5" width="16" height="16" rx="2"></rect><line x1="16" y1="3" x2="16" y2="7"></line><line x1="8" y1="3" x2="8" y2="7"></line><line x1="4" y1="11" x2="20" y2="11"></line><line x1="11" y1="15" x2="12" y2="15"></line><line x1="12" y1="15" x2="12" y2="18"></line></svg>
                                  </span>
                                </div>
                              </div>
                            </div>
                            <div class="col-lg-6">
                              <div class="mb-3">
                                <label class="form-label required">End event time</label>
                                <input type="time" name="booking-time-end" id="booking-time-end" class="form-control" autocomplete="off" value="" required>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-lg-12">
                              <label class="form-label required">Location</label>
                              <input type="text" name="booking-location" id="booking-location" class="form-control" placeholder="Location" value="" required>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            Cancel
                          </a>
                          <button type="button" class="btn btn-primary ms-auto" id="submit-add-booking" onclick="submitBooking()" disabled>
                            <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg>
                            Create draft booking
                          </button>
                        </div>
                        <input id="session-id" name="session-id" type="hidden" value="<?=$_COOKIE["session_ID"];?>">
                        <input id="booking-status" name="booking-status" type="hidden" value="">
                        <input id="booking-status-new" name="booking-status-new" type="hidden" value="">
                        <input id="clash-agreed" name="clash-agreed" type="hidden" value="0">
                      </form>
                    </div>
                  </div>
                </div>

                <div class="table-responsive" id="main-content" style="display: block;">
                  <form id="update_bookings">
                    <table id="bookings-table" class="table card-table table-vcenter text-nowrap datatable">
                      <thead>
                        <tr>
                          <th class="sticky-top">
                            
                          </th>
                          <th class="sticky-top">
                            Ensemble
                          </th>
                          <th class="sticky-top">
                            Name
                          </th>
                          <th class="sticky-top">
                            Booking date
                          </th>
                          <th class="sticky-top">
                            Booking location
                          </th>
                          <th class="sticky-top">
                            First created
                          </th>
                          <th class="sticky-top">
                            Last updated
                          </th>
                          <th class="sticky-top">
                            Approval status
                          </th>
                          <th class="sticky-top">
                            Status code
                          </th>
													<th class="sticky-top">
                            Waiting for
                          </th>
                          <th class="sticky-top">
                            Action
                          </th>
                        </tr>
                      </thead>
                      <tbody>

												<?php
                          $all_bookings_query = $db_connection->prepare("SELECT a.* FROM `bookings` a INNER JOIN (SELECT `booking_ID`, max(`status`) `status` FROM `bookings` WHERE `deleted`=0 GROUP BY `booking_ID`) b USING(`booking_ID`, `status`) ORDER BY `".$sort."` ".$order);
                          $all_bookings_query->execute();
                          $all_bookings_result = $all_bookings_query->get_result();

                          while($booking = $all_bookings_result->fetch_array(MYSQLI_ASSOC))
                          {
                            output_booking($booking, $db_connection);
                          }
												?>

                      </tbody>
                    </table>
                  </form>
                </div>

                <div class="card" id="placeholder-loading" style="display: none;">
                  <ul class="list-group list-group-flush placeholder-glow">
                    <li class="list-group-item opacity-100">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="avatar avatar-rounded placeholder"></div>
                        </div>
                        <div class="col-7">
                          <div class="placeholder placeholder-xs col-9"></div>
                          <div class="placeholder placeholder-xs col-7"></div>
                        </div>
                        <div class="col-2 ms-auto text-end">
                          <div class="placeholder placeholder-xs col-8"></div>
                          <div class="placeholder placeholder-xs col-10"></div>
                        </div>
                      </div>
                    </li>
                    <li class="list-group-item opacity-80">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="avatar avatar-rounded placeholder"></div>
                        </div>
                        <div class="col-7">
                          <div class="placeholder placeholder-xs col-9"></div>
                          <div class="placeholder placeholder-xs col-7"></div>
                        </div>
                        <div class="col-2 ms-auto text-end">
                          <div class="placeholder placeholder-xs col-8"></div>
                          <div class="placeholder placeholder-xs col-10"></div>
                        </div>
                      </div>
                    </li>
                    <li class="list-group-item opacity-60">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="avatar avatar-rounded placeholder"></div>
                        </div>
                        <div class="col-7">
                          <div class="placeholder placeholder-xs col-9"></div>
                          <div class="placeholder placeholder-xs col-7"></div>
                        </div>
                        <div class="col-2 ms-auto text-end">
                          <div class="placeholder placeholder-xs col-8"></div>
                          <div class="placeholder placeholder-xs col-10"></div>
                        </div>
                      </div>
                    </li>
                    <li class="list-group-item opacity-40">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="avatar avatar-rounded placeholder"></div>
                        </div>
                        <div class="col-7">
                          <div class="placeholder placeholder-xs col-9"></div>
                          <div class="placeholder placeholder-xs col-7"></div>
                        </div>
                        <div class="col-2 ms-auto text-end">
                          <div class="placeholder placeholder-xs col-8"></div>
                          <div class="placeholder placeholder-xs col-10"></div>
                        </div>
                      </div>
                    </li>
                    <li class="list-group-item opacity-20">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="avatar avatar-rounded placeholder"></div>
                        </div>
                        <div class="col-7">
                          <div class="placeholder placeholder-xs col-9"></div>
                          <div class="placeholder placeholder-xs col-7"></div>
                        </div>
                        <div class="col-2 ms-auto text-end">
                          <div class="placeholder placeholder-xs col-8"></div>
                          <div class="placeholder placeholder-xs col-10"></div>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>

              </div>
            </div>
          </div>

					<!-- <div class="col-12">
						<div class="card ">
							<div class="card-body">
								<h3 class="card-title">Process?</h3>
								<p class="text-muted">
									Easy as 1, 2, 3:
									<ol class="text-muted">
										<li>You send out provisional details</li>
										<li>Keiron confirms</li>
										<li>You confirm the final details</li>
									</ol>
								</p>
								<p class="text-muted">
									Stuck? Email Adam.
								</p>
							</div>
							<div class="card-footer">
								<a href="#" class="btn btn-primary">Email Adam</a>
							</div>
						</div>
					</div> -->

        </div>
      </div>
    </div>

    <?php include($_SERVER['DOCUMENT_ROOT']."/includes/footer.php"); ?>

    <script src="<?=$config['base_url'];?>/dist/js/tabler.min.js"></script>
    <script src="./dist/libs/list.js/dist/list.min.js"></script>
    <script src="./dist/libs/litepicker/dist/litepicker.js"></script>
    <script>
      // @formatter:off
      document.addEventListener("DOMContentLoaded", function() {
        window.Litepicker && (new Litepicker({
          element: document.getElementById('booking-date'),
          buttonText: {
            previousMonth: `<!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="15 6 9 12 15 18" /></svg>`,
            nextMonth: `<!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>`,
          },
        }));
      });

      document.addEventListener("DOMContentLoaded", function() {
        window.Litepicker && (new Litepicker({
          element: document.getElementById('booking-date-end'),
          buttonText: {
            previousMonth: `<!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="15 6 9 12 15 18" /></svg>`,
            nextMonth: `<!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>`,
          },
        }));
      });
      // @formatter:on

      const addNewBookingForm = document.getElementById("form-add-booking");
      addNewBookingForm.addEventListener("change", () => {
        document.getElementById("submit-add-booking").disabled = !addNewBookingForm.checkValidity();
      });
    </script>
</body>

</html>
<?php
}
else
{
	output_restricted_page();
}
?>

<?php db_disconnect($db_connection); ?>