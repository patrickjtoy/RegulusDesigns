{source}
<?php
    $conn = mysqli_connect('localhost', 'dodson', 'admin123', 'vehu_intranet');
    $url = 'http://vhahacvehudev/index.php/';    
    $display = 'display-none';
    $hide = '';
    if ($_GET['action'] == 'retrieve') {
        // variable declarations
        $display = 'display-block';
        $hide = 'display-none';        
        $number = $_GET['number'];
        $admin = (isset($_GET['admin']) ? $_GET['admin'] : 'no');
        // attempts connection
        if($conn) {
            // successful connection, read from database
            $query = "SELECT * FROM vehu_events WHERE number = '$number'";
            $result = mysqli_query($conn, $query);
            // confirm successful query
            if(!$result) {
                die(print_r(sqlsrv_errors(), true)); 
            } else {
                while($row = mysqli_fetch_array($result)) {
                    $id = $row['id'];
                    $track = $row['track'];
                    $title = $row['title'];
                    $date = $row['date'];
                    $start = $row['start_time'];
                    $end = $row['end_time'];
                    for($i = 1; $i < 6; $i++) {
                        ${'presenter0'.$i} = $row['presenter0'.$i];
                        ${'supervisor0'.$i} = $row['supervisor0'.$i];
                    }
                    $description = $row['description'];
                    $audience = $row['audience'];
                    for($j = 1; $j < 11; $j++) {
                        ${'objective0'.$j} = $row['objective0'.$j];
                    }
                    $regLink = $row['registration_link'];
                    if($row['locked'] == 1) {
                        $locked = true;
                        $markComplete = 1;
                        if($admin == 'yes') {
                            $lockedAttr = '';
                            $lockedClass = 'pointer';
                        } else {                            
                            $lockedAttr = 'readonly="true" unselectable="on"';
                            $lockedClass = 'disabled';
                        }
                    } else {
                        $locked = false;
                        $markComplete = 0;
                        $lockedAttr = '';
                        $lockedClass = 'pointer';
                    }
                    if($row['complete'] != null && $row['complete'] != '') {
                        $complete = $row['complete'];
                    } else {
                        $complete = 0;
                    }                    
                }
                // Free result set
                mysqli_free_result($result);
            }
            // close connection
            mysqli_close($conn); 
        } else {
            echo "Database not connected: " . mysqli_connect_error();
        }
    } else if($_POST['action'] == 'update') {
        
        //includes        
        include('templates/metroshows/lib/class.phpmailer.php');

        // variable declarations        
        $id = $_POST['idNum'];
        $number = $_POST['sessionNum'];
        $admin = $_POST['admin'];
        $track = mysqli_real_escape_string($conn,$_POST['track']);
        $title = mysqli_real_escape_string($conn,$_POST['title']);
        $dateString = $_POST['date'];
        $date = date('Y-m-d', strtotime(str_replace('-', '/', $dateString)));
        $startString = trim($_POST['start'], " ET");
        $start = date("H:i", strtotime($startString));
        $endString = trim($_POST['end'], " ET");
        $end = date("H:i", strtotime($endString));
        $dateTime = $date . ' ' . $start;
        for($i = 1; $i < 6; $i++) {
            ${'presenter0'.$i} = mysqli_real_escape_string($conn, $_POST['presenter0'.$i]);
            ${'supervisor0'.$i} = mysqli_real_escape_string($conn, $_POST['supervisor0'.$i]);
        }
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $audience = mysqli_real_escape_string($conn, $_POST['audience']);
        for($j = 1; $j < 11; $j++) {
            ${'objective0'.$j} = mysqli_real_escape_string($conn, $_POST['objective0'.$j]);
        }
        $regLink = mysqli_real_escape_string($conn, $_POST['regLink']);
        $markComplete = (isset($_POST['mark-complete']) ? $_POST['mark-complete'] : 0);
        $complete = (isset($_POST['complete']) ? $_POST['complete'] : 0);
        
        // attempts connection
        if($conn) {
            // sanitize variables
            for($i = 1; $i < 6; $i++) {
                ${'presenter0'.$i} = mysqli_real_escape_string($conn, ${'presenter0'.$i});
                ${'supervisor0'.$i} = mysqli_real_escape_string($conn, ${'supervisor0'.$i});
            }
            $description = mysqli_real_escape_string($conn, $description);
            $audience = mysqli_real_escape_string($conn, $audience); 
            for($i = 1; $i < 11; $i++) {
                ${'objective0'.$i} = mysqli_real_escape_string($conn, ${'objective0'.$i});
            }
            // successful connection, write to database
            $query = "UPDATE vehu_events SET 
                     track = '$track', title = '$title', date = '$date', start_time = '$start', end_time = '$end', date_time = '$dateTime',
                     presenter01 = '$presenter01', presenter02 = '$presenter02', presenter03 = '$presenter03', presenter04 = '$presenter04', presenter05 = '$presenter05',
                     supervisor01 = '$supervisor01', supervisor02 = '$supervisor02', supervisor03 = '$supervisor03', supervisor04 = '$supervisor04', supervisor05 = '$supervisor05', 
                     description = '$description', audience = '$audience',
                     objective01 = '$objective01', objective02 = '$objective02', objective03 = '$objective03', objective04 = '$objective04', objective05 = '$objective05',
                     objective06 = '$objective06', objective07 = '$objective07', objective08 = '$objective08', objective09 = '$objective09', objective010 = '$objective010',
                     registration_link = '$regLink', locked = $markComplete, complete = $complete 
                     WHERE number = '$number'";
            $result = mysqli_query($conn, $query);
            // confirm successful write
            if(!$result) {
                echo '<span class="error">There was an error submitting the event updates!</span>';
                die(print_r(mysqli_error($conn)));
            } else {
                if($markComplete == 1 && $admin != 'yes') {
                    // send email to Dawnell
                    $email_body = '
                        <p style="font-family: Helvetica Neu, Helvetica, Arial, sans-serif;">A new event has been submitted for approval:</p>'.
                        '<ul>'.
                        '<li style="font-family: Helvetica Neu, Helvetica, Arial, sans-serif;"><strong>Session Number:</strong> '.$number.'</li>'.
                        '<li style="font-family: Helvetica Neu, Helvetica, Arial, sans-serif;"><strong>Track:</strong> '.stripslashes($track).'</li>'.
                        '<li style="font-family: Helvetica Neu, Helvetica, Arial, sans-serif;"><strong>Title:</strong> '.stripslashes($title).'</li>'.
                        '</ul>'.
                        '<p style="font-family: Helvetica Neu, Helvetica, Arial, sans-serif;"><a href="'.$url.'update-event?action=retrieve&number='.$number.'&retrieve=Submit&admin=yes"><strong>Click here</strong></a> to modify, approve, and/or publish this event.</p>
                    ';
                    
                    $mail = new PHPMailer;
                    
                    $mail->IsSMTP(); // Set mailer to use SMTP
                    $mail->Host = 'smtp.va.gov'; // Specify main and backup server
                    $mail->Port = 25; //Set the SMTP port number
                    $mail->SMTPAuth = 'true'; // Enable SMTP authentication                    
                    $mail->Username = "dva\vhacododsom"; //Username to use for SMTP authentication
                    $mail->Password = "Password9"; //Password to use for SMTP authentication

                    $mail->From = 'vehu@va.gov'; //Set who the message is to be sent from
                    $mail->FromName = 'VeHU Event Submission Site';
                    $mail->AddAddress('webteam@cooperthomas.com', 'CT WebTeam'); // Add a recipient
                    $mail->AddReplyTo('webteam@cooperthomas.com', 'VeHU Webmaster');
                    
                    $mail->WordWrap = 50; // Set word wrap to 50 characters
                    $mail->IsHTML(true); // Set email format to HTML
                    
                    $mail->Subject = 'A new event needs approval on the VeHU Event Submission Site';
                    $mail->Body = nl2br($email_body);
                    $mail->AltBody = $email_body;
                    
                    if(!$mail->Send()) {
                        echo '<span class="error">There was an error submitting the event for approval!</span>';
                        $mailError = $mail->ErrorInfo;                        
                    }
                }
                echo '<span class="submitted">Event '.$number.' has been updated successfully!</span>';
                // Free result set
                mysqli_free_result($result);                
            }
            // close connection
            mysqli_close($conn);
        } else {
            echo "Database not connected: " . mysqli_connect_error();
        }
    }
?>
<div>
    <h1>Update Event Info</h1>
    <div class="<?php echo $hide; ?>">
        <p>Enter the Session Number of the event you wish to update:</p>
        <form name="retrieve-event" id="retrieve-event" method="get" action="">
            <span><input type="hidden" name="action" value="retrieve" /></span> 
            <span class="display-block"><label for="number" class="long">Session Number:</label><input type="text" name="number" id="number" required /></span>
            <span class="display-block"><input type="submit" name="retrieve" id="retrieve" value="Submit" /></span>
        </form>
    </div>
    <div class="<?php echo $display; ?>">
        <form name="update-event" id="update-event" method="post" action="update-event">
            <span><input type="hidden" name="action" value="update" /></span> 
            <span><input type="hidden" name="idNum" id="idNum" value="<?php echo $id; ?>" /></span>
            <span><input type="hidden" name="sessionNum" id="sessionNum" value="<?php echo $number; ?>" /></span>
            <span><input type="hidden" name="admin" id="admin" value="<?php echo $admin; ?>" /></span>
            <?php
                if($locked == false || $admin == 'yes') {
                    echo '<p>Verify the details for session '.$number.' below:</p>';
                } else {
                    echo '<p class="notice">The record for session '.$number.' is locked. You may review the record, but no changes can be made.</p>';
                }
            ?>
            <fieldset id="general-info">
                <legend>General Information</legend> 
                <div class="display-block">
                    <span class="display-inline-block"><label for="track">Track:</label><input type="text" name="track" id="track" maxlength="100" value="<?php echo $track; ?>" <?php echo $lockedAttr; ?>></span>
                    <span class="display-inline-block"><label for="title">Title:</label><input type="text" name="title" id="title" maxlength="200" value="<?php echo $title; ?>" <?php echo $lockedAttr; ?> /></span>
                </div>
                <div class="display-block">
                    <span class="display-inline-block" id="date-span"><label for="date">Date:</label><input type="text" name="date" id="date" value="<?php echo $date; ?>" <?php echo $lockedAttr; ?> /></span>
                    <span class="display-inline-block" id="start-span"><label for="start">Start Time:</label><input type="text" name="start" id="start" value="<?php echo $start; ?>" <?php echo $lockedAttr; ?> /></span>
                    <span class="display-inline-block" id="end-span"><label for="end">End Time:</label><input type="text" name="end" id="end" value="<?php echo $end; ?>" <?php echo $lockedAttr; ?> /></span>
                </div>
            </fieldset>
            <p>Fill out and/or update the fields below, then click Update:</p>
            <fieldset id="additional-info">
                <legend>Additional Information</legend>
                <div id="pres-sup-wrapper">
                    <?php
                        if($_GET['action'] == 'retrieve') {
                            echo '<div id="presenters" class="display-inline-block">';
                            echo '<span class="display-block">Presenter(s):</span>';
                            if(isset($presenter01) && $presenter01 !== '') {
                                for($k = 1; $k < 6; $k++) {
                                    if(isset(${'presenter0'.$k}) && ${'presenter0'.$k} !== '') {
                                        echo '<span class="display-block"><label for="presenter0'.$k.'">#'.$k.'</label><input type="text" name="presenter0'.$k.'" id="presenter0'.$k.'" maxlength="50" value="'.htmlspecialchars(${'presenter0'.$k}).'" '.$lockedAttr.'></span>';
                                        $numOfPresenters = $k+1;
                                    } 
                                }
                            } else {
                                    echo '<span class="display-block"><label for="presenter01">#1</label><input type="text" name="presenter01" id="presenter01" maxlength="50" '.$lockedAttr.'></span>';
                                    $numOfPresenters = 2;
                            }
                            echo '</div>';
                            echo '<div id="supervisors" class="display-inline-block">';
                            echo '<span class="display-block">Supervisor(s):</span>';
                            if((isset($supervisor01) && $supervisor01 !== '') || (isset($presenter01) && $presenter01 !== '')) {
                                for($l = 1; $l < 6; $l++) {
                                    if((isset(${'supervisor0'.$l}) && ${'supervisor0'.$l} !== '') || (isset(${'presenter0'.$l}) && ${'presenter0'.$l} !== '')) {
                                        echo '<span class="display-block"><label for="supervisor0'.$l.'">#'.$l.'</label><input type="text" name="supervisor0'.$l.'" id="supervisor0'.$l.'" maxlength="50" value="'.htmlspecialchars(${'supervisor0'.$l}).'" '.$lockedAttr.'></span>';
                                        $numOfSupervisors = $l+1;
                                    } 
                                }
                            } else {
                                    echo '<span class="display-block"><label for="supervisor01">#1</label><input type="text" name="supervisor01" id="supervisor01" maxlength="50" '.$lockedAttr.'></span>';
                                    $numOfSupervisors = 2;
                            }
                            echo '</div>';
                        } 
                    ?>
                    <span class="display-block add-presenter"><a id="addPres" class="<?php echo $lockedClass; ?>">(+) Presenter</a></span>
                </div>
                <span class="display-block padding-10px0"><label for="description">Description:</label></span>
                <span class="display-block padding-10px0"><textarea name="description" id="description" maxlength="4000" <?php echo $lockedAttr; ?>><?php echo htmlspecialchars($description); ?></textarea></span>
                <div id="audience-wrapper" class="display-block">
                    <span class="display-block"><label for="audience">Audience:</label></span>
                    <span id="audience-span" class="display-block"><input type="text" name="audience" id="audience" maxlength="250" value="<?php echo htmlspecialchars($audience); ?>" <?php echo $lockedAttr; ?> /></span>
                </div>
                <div id="objectives-wrapper" class="display-block">
                    <span class="display-block"><label>Objectives:</label></span>
                    <div id="objectives">
                        <?php
                            if($_GET['action'] == 'retrieve') {
                                if(isset($objective01) && $objective01 !== '') {
                                    for($m = 1; $m < 11; $m++) {
                                        if(isset(${'objective0'.$m}) && ${'objective0'.$m} !== '') {
                                            echo '<span class="display-block"><label for="objective0'.$m.'">#'.$m.'</label><input type="text" name="objective0'.$m.'" id="objective0'.$m.'" maxlength="500" value="'.htmlspecialchars(${'objective0'.$m}).'" '.$lockedAttr.'></span>';
                                            $numOfObjectives = $m+1;
                                        }
                                    }
                                } else {
                                    for($n = 1; $n < 4; $n++) {
                                            echo '<span class="display-block"><label for="objective0'.$n.'">#'.$n.'</label><input type="text" name="objective0'.$n.'" id="objective0'.$n.'" maxlength="500" '.$lockedAttr.'></span>';
                                            $numOfObjectives = 4;
                                    }
                                }
                            } 
                        ?>
                    </div>
                    <span class="display-block add-objective"><a id="addObj" class="<?php echo $lockedClass; ?>">(+) Objective</a></span>
                </div>
                <div id="reg-link-wrapper">
                    <span class="display-block"><label for="regLink">Registration Link:</label></span>
                    <span id="reg-link-span" class="display-block"><input type="text" name="regLink" id="regLink" maxlength="2000" value="<?php echo htmlspecialchars($regLink); ?>" <?php echo $lockedAttr; ?> /></span>
                </div>
            </fieldset>            
            <?php
                if($locked == false && $admin == 'yes') {
                    echo '<p class="notice">The record for session '.$number.' is not yet complete.</p>';
                }
                if($locked == false) {
                    ?>
                        <fieldset>
                            <legend>Mark Event Complete</legend>
                            <div id="mark-complete-wrapper">                            
                                <input type="checkbox" name="mark-complete" id="mark-complete" value="1" /><label for="mark-complete">Mark event complete?</label>
                                <span class="display-block gutter-top"><strong>Note: this will lock the event to any future changes and submit it for approval.</strong></span>
                            </div>
                        </fieldset>
                    <?php
                    if($admin != 'yes') {
                        ?>
                            <span class="display-block align-right"><input type="submit" name="update" id="update" value="Update" /></span>
                        <?php
                    }
                } else {
                    echo '<input type="hidden" name="mark-complete" id="mark-complete" value="'.$markComplete.'" />';
                }
                if($admin == 'yes') {
                    ?>
                        <fieldset>
                            <legend>Publish / Unpublish</legend>
                            <div id="display-on-web">
                                <span>Should this event be published to the VeHU Public site?</span>
                                <div class="display-block gutter-top">
                                    <label for="complete">Yes<input type="radio" name="complete" id="complete" value="1" <?php echo ($complete == 1 ? 'checked="checked"' : ''); ?> /></label>
                                    <label for="incomplete">No<input type="radio" name="complete" id="incomplete" value="0" <?php echo ($complete == 0 ? 'checked="checked"' : ''); ?> /></label>
                                </div>
                            </div>
                        </fieldset>
                        <span class="display-block align-right"><input type="submit" name="update" id="update" value="Update" /></span>
                    <?php
                } else {                    
                    echo '<input type="hidden" name="complete" id="complete" value="'.$complete.'" />';
                }
            ?>            
        </form>
    </div>
</div>
<?php
    if($_GET['action'] == 'retrieve' || $_POST['action'] == 'update') {
?>
<script type="text/javascript" src="/templates/metroshows/js/jquery.placeholder.js"></script>
<script type="text/javascript" src="/templates/metroshows/js/jquery.timepicker.js"></script>
<script type="text/javascript">
    jQuery(function() {
        // variable declarations 
        var w = <?php echo $numOfPresenters; ?>;
        var y = <?php echo $numOfObjectives; ?>;
        var minPres = <?php echo $numOfPresenters; ?> +1;
        var minObj = <?php echo $numOfObjectives; ?> + 1;
        var removeLast = jQuery('<span id="conjunction">&nbsp;&nbsp;<u>OR</u>&nbsp;&nbsp;</span><a id="remPres" class="pointer">(&minus;) Presenter</a>').hide();
        removeLast.appendTo(jQuery('.add-presenter'));
        var removeObj = jQuery('<span id="conjunction2">&nbsp;&nbsp;<u>OR</u>&nbsp;&nbsp;</span><a id="remObj" class="pointer">(&minus;) Objective</a>').hide();
        removeObj.appendTo(jQuery('.add-objective'));
        
        // placeholders for IE
        jQuery('input, textarea').placeholder();
        
        // datepicker
        if(jQuery('#date').prop('readonly') == false) {
            jQuery('#date').datepicker();
        }        
        
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
        
        jQuery('#addPres').click(function(e) {
            if(!jQuery(this).hasClass("disabled")) {
                var addPres = jQuery('<span id="presSpan'+w+'" class="display-block"><label for="presenter0'+w+'">#'+w+'</label><input type="text" name="presenter0'+w+'" id="presenter0'+w+'" /></span>').hide();
                var addSup = jQuery('<span id="supSpan'+w+'" class="display-block"><label for="supervisor0'+w+'">#'+w+'</label><input type="text" name="supervisor0'+w+'" id="supervisor0'+w+'"></span>').hide();
                if(w < 5) {
                    addPres.appendTo(jQuery('#presenters')).slideDown();
                    addSup.appendTo(jQuery('#supervisors')).slideDown();
                    jQuery('#conjunction').show();
                    jQuery('#remPres').show();
                    w++;
                } else {
                    w++;
                    addPres.appendTo(jQuery('#presenters')).slideDown();
                    addSup.appendTo(jQuery('#supervisors')).slideDown();
                    jQuery('#addPres').hide();
                    jQuery('#conjunction').hide();
                }
            } else {
                e.preventDefault();
            }
        });
        jQuery('#remPres').click(function(e) {
            if(!jQuery(this).hasClass("disabled")) {
                var x = w-1;
                if(x == w-1) {
                    jQuery('#presSpan'+x).slideUp(function() {
                        jQuery(this).remove();
                    });
                    jQuery('#supSpan'+x).slideUp(function() {
                        jQuery(this).remove();
                    });
                    w--;
                    if(w < minPres) {
                        jQuery('#conjunction').hide();
                        jQuery('#remPres').hide();
                    } else if(w > 2 && w < 6) {
                        jQuery('#addPres').show();
                        jQuery('#conjunction').show();
                    }
                }
            } else {
                e.preventDefault();
            }
        });
        jQuery('#addObj').click(function(e) {
            if(!jQuery(this).hasClass("disabled")) {
                var addObj = jQuery('<span id="objSpan'+y+'" class="display-block"><label for="objective0'+y+'">#'+y+'</label><input type="text" name="objective0'+y+'" id="objective0'+y+'"></span>').hide();
                if(y < 10) { 
                    addObj.appendTo(jQuery('#objectives')).slideDown();
                    jQuery('#conjunction2').show();
                    jQuery('#remObj').show();
                    y++;
                } else {
                    y++
                    addObj.appendTo(jQuery('#objectives')).slideDown();
                    jQuery('#addObj').hide();
                    jQuery('#conjunction2').hide();
                }
            } else {
                e.preventDefault();
            }
        });
        jQuery('#remObj').click(function(e) {            
            if(!jQuery(this).hasClass("disabled")) {
                var z = y-1;
                if(z == y-1) {
                    jQuery('#objSpan'+z).slideUp(function() {
                        jQuery(this).remove();
                    });
                    y--;
                    if(y < minObj) {
                        jQuery('#conjunction2').hide();
                        jQuery('#remObj').hide();
                    } else if(y > 4 && y < 11) {
                        jQuery('#addObj').show();
                        jQuery('#conjunction2').show();
                    }
                }
            } else {
                e.preventDefault();
            }            
        });
        
        jQuery('#update').click(function(e) {
            e.preventDefault();
            var admin = <?php echo '"'.$admin.'"' ?>;
            submitUpdate(admin);
        });               
    });
    function submitUpdate(isAdmin) {
        if(isAdmin == 'yes') {
            // AJAX to pass form data to public site
            var data = jQuery("#update-event").serialize();
            var xmlhttp;
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari 
                xmlhttp = new XMLHttpRequest(); 
            } 
            else {// code for IE6, IE5 
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); 
            }
            xmlhttp.open("POST","http://vaww.vehu.cfde.webdev.va.gov/ajax/update_event.cfm", false);
            xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded"); 
            xmlhttp.send(data);
        }
        // submit form
        jQuery("#update-event").submit();
    }
</script>
<?php
    }
?>
{/source}