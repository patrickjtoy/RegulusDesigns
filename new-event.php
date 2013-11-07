{source}
<?php
    $display = 'display-none';
    $hide = '';
    // retrieve last session number and increment by 1
    $conn = mysqli_connect('localhost', 'dodson', 'admin123', 'vehu_intranet');
    if($conn) {
        $query = "SELECT id, number FROM vehu_events ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        if(!$result) {
            die(print_r(sqlsrv_errors(), true));
        } else {
            while($row = mysqli_fetch_array($result)) {
                $number = $row['number'];
            }
            //$number = preg_replace("/[^0-9,.]/","",$number);
            $number = substr($number, -3);
            $nextNumber = intval($number) + 1;
            $length = strlen((string) $nextNumber);
            switch ($length) {
                case 1:
                    $nextString = '00'.$nextNumber;
                    break;

                case 2:
                    $nextString = '0'.$nextNumber;
                
                default:
                    $nextString = $nextNumber;
                    break;
            }            
            $today = date('md');
            $thisYear = date('y');
            if($today >= 1001) {
                $fiscalYear = $thisYear + 1;
            } else {
                $fiscalYear = $thisYear;
            }
            $nextSession = $fiscalYear.$nextString;
            // Free result set
            mysqli_free_result($result);
        }
        // close connection
        mysqli_close($conn);
    } else {
        echo "Database not connected: " . mysqli_connect_error();
    }

    if ($_POST['action'] == 'new') {
        // variable declarations
        $conn = mysqli_connect('localhost', 'dodson', 'admin123', 'vehu_intranet');
		$number = mysqli_real_escape_string($conn,$_POST['number']);
        $track = mysqli_real_escape_string($conn,$_POST['track']);
        $title = mysqli_real_escape_string($conn,$_POST['title']);
        $dateString = $_POST['date'];
        $date = date('Y-m-d', strtotime(str_replace('-', '/', $dateString)));
        $startString = trim($_POST['start'], " ET");
        $start = date("H:i", strtotime($startString));
        $endString = trim($_POST['end'], " ET");
        $end = date("H:i", strtotime($endString));
        $dateTime = $date . ' ' . $start;
		$url = 'http://vhahacvehudev/index.php/update-event?action=retrieve&number=' . $number;        
        
        // attempts connection
        if($conn) {
            //successful connection, verify session number is unique
            $query = "SELECT number FROM vehu_events WHERE number = '$number'";
            $result = mysqli_query($conn, $query);
            if(!$result) {
                die(print_r(sqlsrv_errors(), true));
            } else {
                $row = mysqli_fetch_array($result);
                if($row[0] == $number) {
                    $uniqueNum = 0;
                } else {
                    $uniqueNum = 1;
                }
                // Free result set
                mysqli_free_result($result);
            }
            
            //successful connection, verify session title is unique
            $query = "SELECT number, title FROM vehu_events WHERE title = '$title'";
            $result = mysqli_query($conn, $query);
            if(!$result) {
                die(print_r(sqlsrv_errors(), true));
            } else {
                $row = mysqli_fetch_array($result);                
                if($row[1] == $title) {
                    $uniqueTitle = 0;
                    $link = 'http://vhahacvehudev/index.php/update-event?action=retrieve&number=' . $row[0];
                } else {
                    $uniqueTitle = 1;
                }
                // Free result set
                mysqli_free_result($result);
            }            
            if($uniqueNum == 1 && $uniqueTitle == 1) {
                $display = 'display-block';
                $hide = 'display-none';
                // successful connection, write to database
                $query = "INSERT INTO vehu_events (number, track, title, date, start_time, end_time, date_time) VALUES ('$number', '$track', '$title', '$date', '$start', '$end', '$dateTime')";
                $result = mysqli_query($conn, $query);
                // confirm successful write
                if(!$result) {
                    die(print_r(sqlsrv_errors(), true));                
                } else {
                    echo '<span class="submitted">Event '.$number.' has been created with the following details:</span>';
                    // Free result set
                    mysqli_free_result($result);
                }                
            } else {
                if($uniqueNum == 0) {
                    echo '<span class="error">An event with that session number already exists. Did you want to <a href="'.$url.'" target="_blank">update it </a> instead?</span>';
                } elseif($uniqueTitle == 0) {
                    echo '<span class="error">An event with that title already exists. Did you want to <a href="'.$link.'" target="_blank">update it</a> instead?</span>';
                }
            }
            // close connection
            mysqli_close($conn);
        } else {
            echo "Database not connected: ".mysqli_connect_error();
        }
    }
?>
<div id="pre-submit" class="<?php echo $hide; ?>">
    <h1>Create New Event</h1>
	<p>Please enter the following information to create a new event. All form fields are required.</p>
	<form name="create-new-event" id="create-new-event" method="post" action="">
		<span><input type="hidden" name="action" value="new" /></span>
		<span class="display-block input-text-span"><label for="number">Session Number:</label><input type="text" name="number" id="number" maxlength="20" value="<?php echo $nextSession; ?>" required /></span>
		<span class="display-block input-text-span"><label for="track">Track:</label><input type="text" name="track" id="track" maxlength="100" required /></span>
		<span class="display-block input-text-span"><label for="title">Title:</label><input type="text" name="title" id="title" maxlength="200" required /></span>
		<span class="display-block input-text-span"><label for="date">Date:</label><input type="text" name="date" id="date" required /></span>
		<span class="display-block input-text-span"><label for="start">Start Time:</label><input type="text" name="start" id="start" required /></span>        
		<span class="display-block input-text-span"><label for="end">End Time:</label><input type="text" name="end" id="end" required /></span>
		<span class="display-block align-right"><input type="submit" name="submitNew" id="submitNew" value="Submit" /></span>
	</form>
</div>
<div id="post-submit" class="<?php echo $display; ?>">
    <h1>New Event Confirmation</h1>
    <span id="track" class="display-block"><span class="bold label">Track:</span><?php echo $track; ?></span>
    <span id="title" class="display-block"><span class="bold label">Title:</span><?php echo $title; ?></span>
    <span id="date" class="display-block"><span class="bold label">Date:</span><?php echo $date; ?></span>
    <input type="hidden" name="month" id="month" value="<?php echo $month; ?>">
    <span id="start" class="display-block"><span class="bold label">Start Time:</span><?php echo $start; ?></span>
    <span id="end" class="display-block"><span class="bold label">End Time:</span><?php echo $end; ?></span>
    <span id="link" class="display-block"><span class="bold label">Direct Link:</span><a href="<?php echo $url; ?>" target="_blank"><?php echo $url; ?></a></span>
    <div id="createAnotherWrapper" class="display-block">
        <button type="button" class="button" name="createAnother" id="createAnother" onclick="window.location = 'http://vhahacvehudev/index.php/new-event'">Create Another Event</button>
    </div>
</div>
<script type="text/javascript" src="/templates/metroshows/js/jquery.placeholder.js"></script>
<script type="text/javascript" src="/templates/metroshows/js/jquery.timepicker.js"></script>
<script type="text/javascript">
    jQuery(function() {        
        // placholders
        jQuery('input, textarea').placeholder();
        
        // datepicker
        jQuery('#date').datepicker();
        
        // timepicker
        jQuery('#start').timepicker({
            'minTime': '9:00am',
            'maxTime': '5:00pm',
            'timeFormat': 'g:i A ET'            
        });
        jQuery('#end').timepicker({
            'minTime': '9:00am',
            'maxTime': '5:00pm',
            'timeFormat': 'g:i A ET'
        });
        jQuery('#submitNew').click(function(e) {
            e.preventDefault();
            submitNew();
        });
    });
    function submitNew() {
        // AJAX to pass form data to public site
        var data = jQuery("#create-new-event").serialize();
        var xmlhttp;
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari 
            xmlhttp = new XMLHttpRequest(); 
        } 
        else {// code for IE6, IE5 
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); 
        }
        xmlhttp.open("POST","http://vaww.vehu.cfde.webdev.va.gov/ajax/new_event.cfm", false);
        xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded"); 
        xmlhttp.send(data);
        // submit form
        jQuery("#create-new-event").submit();
    }
</script>
{/source}